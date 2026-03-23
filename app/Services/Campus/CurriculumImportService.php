<?php

namespace App\Services\Campus;

use App\Models\Course;
use App\Models\StudyProgram;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Facades\Excel;

class CurriculumImportService
{
    public function import(StudyProgram $studyProgram, UploadedFile $file): int
    {
        $data = Excel::toArray(new class implements ToArray {
            public function array(array $array): array
            {
                return $array;
            }
        }, $file);

        $sheet = $data[0] ?? [];

        return DB::transaction(function () use ($sheet, $studyProgram) {
            $count = 0;

            foreach ($sheet as $index => $row) {
                if ($index === 0) {
                    continue;
                }

                $code = $row[1] ?? null;
                $name = $row[2] ?? null;

                if (!$name || !$code) {
                    continue;
                }

                Course::updateOrCreate(
                    [
                        'study_program_id' => $studyProgram->id,
                        'code' => $code,
                    ],
                    [
                        'name' => $name,
                        'sks' => (int) ($row[3] ?? 0),
                        'semester' => (int) ($row[4] ?? 1),
                        'is_mandatory' => strtoupper((string) ($row[5] ?? 'N')) === 'Y',
                        'level' => $studyProgram->level,
                        'keywords' => isset($row[6]) ? explode(',', (string) $row[6]) : [],
                    ],
                );

                $count++;
            }

            return $count;
        });
    }
}
