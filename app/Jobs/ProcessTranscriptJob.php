<?php

namespace App\Jobs;

use App\Models\Conversion;
use App\Models\ConversionDetail;
use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log; // Tambahkan ini
use Maatwebsite\Excel\Facades\Excel;

class ProcessTranscriptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $conversion;

    public function __construct(Conversion $conversion)
    {
        $this->conversion = $conversion;
    }

    public function handle(): void
    {
        // 1. Debugging Path
        $relativePath = $this->conversion->original_file_path;
        $fullPath = storage_path('app/private/' . $relativePath); 
        
        // Cek path alternatif jika 'private' tidak ada (tergantung config filesystems.php)
        if (!file_exists($fullPath)) {
            $fullPath = storage_path('app/' . $relativePath);
        }

        echo "Processing File: " . $fullPath . "\n"; // Output ke Terminal

        if (!file_exists($fullPath)) {
            $msg = "File tidak ditemukan di path: " . $fullPath;
            Log::error($msg);
            $this->fail($msg);
            return;
        }

        try {
            // 2. Load Data Target
            $targetCourses = Course::where('study_program_id', $this->conversion->study_program_id)->get();
            if ($targetCourses->isEmpty()) {
                echo "Warning: Tidak ada Mata Kuliah Target di Database untuk Prodi ini.\n";
            }

            // 3. Baca File Excel (Ubah ke Array agar lebih ringan)
            // Menggunakan class anonim sederhana
            $data = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
                public function array(array $array) { return $array; }
            }, $fullPath);

            if (empty($data)) {
                throw new \Exception("File Excel kosong atau tidak terbaca.");
            }

            $sheet = $data[0]; // Ambil sheet pertama
            
            // 4. Cari Header
            $headerRowIndex = -1;
            foreach ($sheet as $index => $row) {
                // Ubah array row jadi string lowercase buat pencarian
                $rowString = strtolower(json_encode($row));
                // Cari kata kunci template
                if (str_contains($rowString, 'nama_matakuliah') || str_contains($rowString, 'nama mata kuliah')) {
                    $headerRowIndex = $index;
                    break;
                }
            }

            if ($headerRowIndex === -1) {
                throw new \Exception("Format Header Excel tidak dikenali. Pastikan ada kolom 'NAMA_MATAKULIAH'.");
            }

            echo "Header ditemukan di baris: " . ($headerRowIndex + 1) . "\n";

            DB::beginTransaction();

            $totalSksAccepted = 0;
            $countProcessed = 0;

            // Loop data mulai dari baris setelah header
            for ($i = $headerRowIndex + 1; $i < count($sheet); $i++) {
                $row = $sheet[$i];
                
                // Mapping Kolom (Index 0 = A, 1 = B, dst)
                // Pastikan ini sesuai screenshot LibreOffice Anda
                $srcCode  = $row[1] ?? null; 
                $srcName  = $row[2] ?? null;
                $srcSks   = (int) ($row[3] ?? 0);
                $srcGrade = strtoupper(trim($row[4] ?? ''));

                if (!$srcName) continue; // Skip baris kosong

                $countProcessed++;

                // AI MATCHING untuk konversi mata kuliah 
                $bestMatch = null;
                $highestScore = 0;

                foreach ($targetCourses as $target) {
                    similar_text(strtolower($srcName), strtolower($target->name), $percent);
                    
                    if ($target->keywords && is_array($target->keywords)) {
                        foreach ($target->keywords as $keyword) {
                            if (str_contains(strtolower($srcName), strtolower($keyword))) {
                                $percent = 100; break;
                            }
                        }
                    }

                    if ($percent > $highestScore) {
                        $highestScore = $percent;
                        $bestMatch = $target;
                    }
                }

                $status = 'rejected';
                $matchScore = $highestScore / 100;
                $goodGrades = ['A', 'A-', 'B+', 'B'];

                if ($matchScore >= 0.8 && in_array($srcGrade, $goodGrades)) {
                    $status = 'auto_accepted';
                    $totalSksAccepted += ($bestMatch ? $bestMatch->sks : $srcSks);
                } elseif ($matchScore >= 0.5) {
                    $status = 'manual_accepted';
                }

                ConversionDetail::create([
                    'conversion_id'    => $this->conversion->id,
                    'src_code'         => substr((string)$srcCode, 0, 20), // Safety trim
                    'src_name'         => $srcName,
                    'src_sks'          => $srcSks,
                    'src_grade'        => $srcGrade,
                    'target_course_id' => $bestMatch ? $bestMatch->id : null,
                    'match_score'      => $matchScore,
                    'status'           => $status
                ]);
            }

            // Update Header
            $this->conversion->update([
                'status' => 'review',
                'total_sks_accepted' => $totalSksAccepted,
                'total_sks_target' => $targetCourses->sum('sks')
            ]);

            DB::commit();
            
            echo "Sukses memproses $countProcessed mata kuliah.\n";

        } catch (\Exception $e) {
            DB::rollBack();
            $errorMsg = "ERROR Job: " . $e->getMessage() . " | Line: " . $e->getLine();
            echo $errorMsg . "\n"; // Print ke terminal worker
            Log::error($errorMsg); // Simpan ke log file
            $this->fail($errorMsg);
        }
    }
}