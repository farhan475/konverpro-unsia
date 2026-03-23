<?php

namespace App\Support;

use App\Models\Conversion;
use App\Models\Course;
use App\Models\StudyProgram;
use Illuminate\Support\Collection;

class AcademicSettingsSupport
{
    public static function defaults(StudyProgram $studyProgram, ?Collection $courses = null): array
    {
        $courses = $courses ?? self::getCourses($studyProgram);

        $requiredCourses = $courses
            ->filter(fn (Course $course) => (bool) $course->is_mandatory)
            ->take(8)
            ->map(fn (Course $course) => self::mapCourse($course))
            ->values()
            ->all();

        $semesterRules = $courses
            ->groupBy(fn (Course $course) => (int) $course->semester)
            ->sortKeys()
            ->map(function (Collection $semesterCourses, int $semester) {
                $totalSks = (int) $semesterCourses->sum('sks');

                return [
                    'semester' => $semester,
                    'maxSks' => max(18, min($totalSks > 0 ? $totalSks : 20, 24)),
                ];
            })
            ->values()
            ->all();

        if ($semesterRules === []) {
            $semesterRules = [
                ['semester' => 1, 'maxSks' => 20],
                ['semester' => 2, 'maxSks' => 24],
            ];
        }

        return [
            'prodiId' => $studyProgram->id,
            'prodiName' => $studyProgram->name,
            'kaprodiName' => 'Ketua Prodi ' . $studyProgram->name,
            'kaprodiTitle' => 'Ketua Program Studi',
            'letterFormat' => 'BA/{YEAR}/{NO}/{PRODI}',
            'minPassingGrade' => 'C',
            'maxAcceptedSks' => 72,
            'maxStudyYears' => 5,
            'semesterRules' => $semesterRules,
            'requiredCourses' => $requiredCourses,
            'notes' => 'Mahasiswa wajib menyelesaikan seluruh mata kuliah inti yang belum dapat dikonversi sebelum yudisium.',
            'signatureDataUrl' => null,
        ];
    }

    public static function resolve(StudyProgram $studyProgram, ?Collection $courses = null): array
    {
        $courses = $courses ?? self::getCourses($studyProgram);
        $defaults = self::defaults($studyProgram, $courses);
        $settings = is_array($studyProgram->settings) ? $studyProgram->settings : [];
        $storedAcademic = self::extractAcademicSettings($settings);

        return [
            ...$defaults,
            ...$storedAcademic,
            'prodiId' => $studyProgram->id,
            'prodiName' => $studyProgram->name,
            'semesterRules' => self::normalizeSemesterRules($storedAcademic['semesterRules'] ?? $defaults['semesterRules']),
            'requiredCourses' => self::normalizeRequiredCourses(
                $courses,
                $storedAcademic['requiredCourses'] ?? $defaults['requiredCourses'],
            ),
            'updatedAt' => $storedAcademic['updatedAt'] ?? null,
        ];
    }

    public static function save(StudyProgram $studyProgram, array $payload, ?Collection $courses = null): array
    {
        $courses = $courses ?? self::getCourses($studyProgram);
        $defaults = self::defaults($studyProgram, $courses);

        $academicSettings = [
            'prodiId' => $studyProgram->id,
            'prodiName' => $studyProgram->name,
            'kaprodiName' => trim((string) ($payload['kaprodiName'] ?? $defaults['kaprodiName'])),
            'kaprodiTitle' => trim((string) ($payload['kaprodiTitle'] ?? $defaults['kaprodiTitle'])),
            'letterFormat' => trim((string) ($payload['letterFormat'] ?? $defaults['letterFormat'])),
            'minPassingGrade' => (string) ($payload['minPassingGrade'] ?? $defaults['minPassingGrade']),
            'maxAcceptedSks' => (int) ($payload['maxAcceptedSks'] ?? $defaults['maxAcceptedSks']),
            'maxStudyYears' => (int) ($payload['maxStudyYears'] ?? $defaults['maxStudyYears']),
            'semesterRules' => self::normalizeSemesterRules($payload['semesterRules'] ?? $defaults['semesterRules']),
            'requiredCourses' => self::normalizeRequiredCourses(
                $courses,
                $payload['requiredCourses'] ?? $defaults['requiredCourses'],
            ),
            'notes' => trim((string) ($payload['notes'] ?? $defaults['notes'])),
            'signatureDataUrl' => $payload['signatureDataUrl'] ?? null,
            'updatedAt' => now()->toISOString(),
        ];

        $settings = is_array($studyProgram->settings) ? $studyProgram->settings : [];
        $settings['academic'] = $academicSettings;

        $studyProgram->update([
            'settings' => $settings,
        ]);

        return $academicSettings;
    }

    public static function buildLetterNumber(array $settings, ?string $prodiCode = null, string|int|null $sequenceSeed = null): string
    {
        $format = (string) ($settings['letterFormat'] ?? 'BA/{YEAR}/{NO}/{PRODI}');
        $year = now()->format('Y');
        $sequence = str_pad(substr((string) ($sequenceSeed ?? now()->timestamp), -4), 4, '0', STR_PAD_LEFT);
        $prodiToken = $prodiCode ?: strtoupper(substr((string) ($settings['prodiName'] ?? 'PRODI'), 0, 6));

        return str_replace(
            ['{YEAR}', '{NO}', '{PRODI}'],
            [$year, $sequence, strtoupper($prodiToken)],
            $format,
        );
    }

    public static function buildOfficialDocumentPayload(Conversion $conversion): array
    {
        $conversion->loadMissing([
            'student:id,name,email,profile_data',
            'university:id,name,logo_path,settings',
            'studyProgram:id,code,name,level,settings',
            'studyProgram.courses:id,study_program_id,code,name,sks,semester,is_mandatory',
            'details.targetCourse:id,code,name,sks',
        ]);

        $studyProgram = $conversion->studyProgram;
        $courses = $studyProgram ? $studyProgram->courses : collect();
        $academicSettings = $studyProgram
            ? self::resolve($studyProgram, $courses)
            : [];

        $requiredSks = (int) $courses->sum('sks');
        $acceptedSks = (int) ($conversion->total_sks_accepted ?? 0);
        $remainingSks = max($requiredSks - $acceptedSks, 0);

        $acceptedCount = 0;
        $pendingCount = 0;
        $rejectedCount = 0;

        $details = $conversion->details->map(function ($detail) use (&$acceptedCount, &$pendingCount, &$rejectedCount) {
            $status = (string) $detail->status;

            if (in_array($status, ['approved', 'accepted', 'auto_accepted', 'manual_accepted'], true)) {
                $acceptedCount++;
            } elseif ($status === 'rejected') {
                $rejectedCount++;
            } else {
                $pendingCount++;
            }

            return [
                'id' => $detail->id,
                'status' => $status,
                'src_name' => $detail->src_name,
                'src_sks' => (int) ($detail->src_sks ?? 0),
                'src_grade' => $detail->src_grade,
                'target_course' => $detail->targetCourse
                    ? [
                        'id' => $detail->targetCourse->id,
                        'code' => $detail->targetCourse->code,
                        'name' => $detail->targetCourse->name,
                        'sks' => (int) ($detail->targetCourse->sks ?? 0),
                    ]
                    : null,
            ];
        })->values();

        $universitySettings = is_array($conversion->university?->settings) ? $conversion->university->settings : [];

        return [
            'conversion' => [
                'id' => $conversion->id,
                'trx_id' => $conversion->trx_id,
                'status' => $conversion->status,
                'payment_status' => $conversion->payment_status,
                'created_at' => $conversion->created_at,
                'admin_notes' => $conversion->admin_notes,
            ],
            'student' => [
                'id' => $conversion->student?->id,
                'name' => $conversion->student?->name,
                'email' => $conversion->student?->email,
                'phone' => $conversion->student?->profile_data['phone'] ?? null,
                'source_campus' => $conversion->student?->profile_data['source_campus'] ?? null,
            ],
            'university' => [
                'id' => $conversion->university?->id,
                'name' => $conversion->university?->name,
                'logo_path' => $conversion->university?->logo_path,
                'city' => $universitySettings['city'] ?? null,
                'province' => $universitySettings['province'] ?? null,
            ],
            'study_program' => [
                'id' => $studyProgram?->id,
                'code' => $studyProgram?->code,
                'name' => $studyProgram?->name,
                'level' => $studyProgram?->level,
            ],
            'summary' => [
                'acceptedSks' => $acceptedSks,
                'requiredSks' => $requiredSks,
                'remainingSks' => $remainingSks,
                'acceptedCourses' => $acceptedCount,
                'pendingCourses' => $pendingCount,
                'rejectedCourses' => $rejectedCount,
            ],
            'academic_settings' => $academicSettings,
            'official_document_meta' => [
                'campusName' => $conversion->university?->name,
                'documentNumber' => self::buildLetterNumber(
                    $academicSettings,
                    $studyProgram?->code,
                    $conversion->trx_id ?: $conversion->id,
                ),
                'signatoryName' => $academicSettings['kaprodiName'] ?? null,
                'signatoryTitle' => $academicSettings['kaprodiTitle'] ?? null,
                'notes' => $academicSettings['notes'] ?? null,
                'signatureDataUrl' => $academicSettings['signatureDataUrl'] ?? null,
                'issuedAt' => optional($conversion->updated_at ?? $conversion->created_at)?->toISOString(),
            ],
            'files' => [
                'originalTranscriptPath' => $conversion->original_file_path,
                'generatedResultPath' => $conversion->generated_result_path,
            ],
            'details' => $details,
        ];
    }

    protected static function getCourses(StudyProgram $studyProgram): Collection
    {
        return $studyProgram->courses()
            ->get(['id', 'study_program_id', 'code', 'name', 'sks', 'semester', 'is_mandatory']);
    }

    protected static function extractAcademicSettings(array $settings): array
    {
        if (isset($settings['academic']) && is_array($settings['academic'])) {
            return $settings['academic'];
        }

        $legacyKeys = [
            'kaprodiName',
            'kaprodiTitle',
            'letterFormat',
            'minPassingGrade',
            'maxAcceptedSks',
            'maxStudyYears',
            'semesterRules',
            'requiredCourses',
            'notes',
            'signatureDataUrl',
            'updatedAt',
        ];

        $legacy = [];
        foreach ($legacyKeys as $key) {
            if (array_key_exists($key, $settings)) {
                $legacy[$key] = $settings[$key];
            }
        }

        return $legacy;
    }

    protected static function normalizeSemesterRules(mixed $rules): array
    {
        if (!is_array($rules)) {
            return [];
        }

        return collect($rules)
            ->filter(fn ($rule) => is_array($rule))
            ->map(function (array $rule) {
                return [
                    'semester' => max(1, (int) ($rule['semester'] ?? 1)),
                    'maxSks' => max(1, (int) ($rule['maxSks'] ?? 20)),
                ];
            })
            ->sortBy('semester')
            ->values()
            ->all();
    }

    protected static function normalizeRequiredCourses(Collection $courses, mixed $selectedCourses): array
    {
        $courseMap = $courses->keyBy('id');

        if (!is_array($selectedCourses)) {
            return [];
        }

        return collect($selectedCourses)
            ->map(function ($course) use ($courseMap) {
                $courseId = is_array($course) ? ($course['id'] ?? null) : $course;
                $matchedCourse = $courseId ? $courseMap->get($courseId) : null;

                if ($matchedCourse instanceof Course) {
                    return self::mapCourse($matchedCourse);
                }

                if (!is_array($course)) {
                    return null;
                }

                return [
                    'id' => (string) ($course['id'] ?? ''),
                    'code' => (string) ($course['code'] ?? ''),
                    'name' => (string) ($course['name'] ?? ''),
                    'semester' => (int) ($course['semester'] ?? 1),
                    'sks' => (int) ($course['sks'] ?? 0),
                ];
            })
            ->filter(fn (?array $course) => $course !== null && $course['id'] !== '')
            ->unique('id')
            ->sortBy([
                ['semester', 'asc'],
                ['name', 'asc'],
            ])
            ->values()
            ->all();
    }

    protected static function mapCourse(Course $course): array
    {
        return [
            'id' => $course->id,
            'code' => $course->code,
            'name' => $course->name,
            'semester' => (int) ($course->semester ?? 1),
            'sks' => (int) ($course->sks ?? 0),
        ];
    }
}
