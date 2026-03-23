<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\University;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    /**
     * Mengambil daftar kampus yang aktif beserta prodi-nya
     */
    public function getActiveCampuses()
    {
        $campuses = University::query()
            ->select(['id', 'name', 'logo_path', 'is_partner', 'settings'])
            ->where('is_active', true)
            ->with(['studyPrograms' => function ($query) {
                $query->select(['id', 'university_id', 'name', 'level'])
                    ->where('is_active', true);
            }])
            ->get(['id', 'name', 'logo_path', 'is_partner', 'settings']);

        return ApiResponse::success($campuses, 'Daftar kampus berhasil diambil');
    }

    public function getMarketplaceData(Request $request)
    {
        $query = University::query()
            ->select([
                'id',
                'name',
                'logo_path',
                'student_fee',
                'student_registration_fee',
                'is_partner',
                'settings',
            ])
            ->where('is_active', true)
            ->with(['studyPrograms' => function ($q) use ($request) {
                $q->select(['id', 'university_id', 'name', 'level'])
                    ->where('is_active', true);

                if ($request->filled('prodi_name')) {
                    $q->where('name', 'like', '%'.$request->string('prodi_name')->toString().'%');
                }

                $q->with(['courses' => function ($q2) {
                    $q2->select('id', 'study_program_id', 'code', 'name', 'sks', 'keywords', 'is_mandatory');
                }]);
            }]);

        if ($request->filled('campus_name')) {
            $query->where('name', 'like', '%'.$request->string('campus_name')->toString().'%');
        }

        if ($request->filled('province') && strtolower($request->string('province')->toString()) !== 'semua') {
            $this->applyJsonSettingFilter($query, 'province', $request->string('province')->toString());
        }

        if ($request->filled('type') && strtolower($request->string('type')->toString()) !== 'semua') {
            $this->applyJsonSettingFilter($query, 'type', $request->string('type')->toString());
        }

        if ($request->filled('lecture') && strtolower($request->string('lecture')->toString()) !== 'semua') {
            $this->applyJsonSettingFilter($query, 'lecture', $request->string('lecture')->toString());
        }

        if ($request->filled('prodi_name')) {
            $query->whereHas('studyPrograms', function (Builder $q) use ($request) {
                $q->where('is_active', true)
                    ->where('name', 'like', '%'.$request->string('prodi_name')->toString().'%');
            });
        }

        $universities = $query->get();

        $formattedData = [];

        foreach ($universities as $univ) {
            $settings = $univ->settings ?? [];
            $province = $settings['province'] ?? 'Umum';
            $type = $settings['type'] ?? 'PTS';
            $lecture = $settings['lecture'] ?? 'Online/Offline';
            $city = $settings['city'] ?? '';

            foreach ($univ->studyPrograms as $prodi) {
                $formattedData[] = [
                    'id' => $univ->id,
                    'study_program_id' => $prodi->id,
                    'campus' => $univ->name,
                    'isOfficial' => $univ->is_partner,
                    'logoPath' => $univ->logo_path, // Uses accessor getLogoUrlAttribute if mapped
                    'province' => $province,
                    'city' => $city,
                    'type' => $type,
                    'lecture' => $lecture,
                    'prodiName' => $prodi->name,
                    'strata' => $prodi->level,
                    'tuition' => $univ->student_fee ?? 0,
                    'registrationFee' => $univ->student_registration_fee ?? 0,
                    'courses' => $prodi->courses,
                ];
            }
        }

        // Handle simple sorting if requested
        if ($request->has('sort_by')) {
            $sortBy = $request->sort_by;
            usort($formattedData, function ($a, $b) use ($sortBy) {
                if ($sortBy === 'fee_asc') {
                    return $a['tuition'] <=> $b['tuition'];
                }

                return 0;
            });
        }

        return ApiResponse::success($formattedData, 'Data Marketplace Ready');
    }

    private function applyJsonSettingFilter(Builder $query, string $key, string $value): void
    {
        $query->whereRaw(
            'LOWER(JSON_UNQUOTE(JSON_EXTRACT(settings, ?))) = ?',
            ['$."'.$key.'"', strtolower($value)],
        );
    }

    public function downloadTemplate()
    {
        // For simplicity when not using Maatwebsite Excel, we return a CSV
        $filename = 'Template_Transkrip_KonverPro.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');

            // Kolom dummy sesuai yg dibaca frontend
            fputcsv($file, ['Nama', ':', 'Mahasiswa Contoh']);
            fputcsv($file, ['Universitas', ':', 'Universitas Asal']);
            fputcsv($file, ['Email', ':', 'contoh@email.com']);
            fputcsv($file, []);
            fputcsv($file, ['Kode MK', 'Nama Mata Kuliah', 'SKS', 'Nilai Huruf']);
            fputcsv($file, ['MK001', 'Algoritma Pemrograman', '3', 'A']);
            fputcsv($file, ['MK002', 'Basis Data', '3', 'B+']);
            fputcsv($file, ['MK003', 'Struktur Data', '3', 'A-']);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
