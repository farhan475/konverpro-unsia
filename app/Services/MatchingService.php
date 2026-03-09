<?php

namespace App\Services;

use App\Models\StudyProgram;
use Illuminate\Support\Str;

class MatchingService
{
    /**
     * Common courses that are usually mandatory and not easily matched by string similarity alone.
     */
    const COMMON_COURSES = [
        "Pendidikan Agama", "Pancasila", "Kewarganegaraan", "Bahasa Indonesia", 
        "Bahasa Inggris Dasar", "Technopreneurship", "Skripsi / Tugas Akhir", 
        "Kuliah Kerja Nyata (KKN)", "Magang Industri", "Metodologi Penelitian", 
        "Etika Profesi", "Kecerdasan Buatan Dasar", "Wawasan Nusantara"
    ];

    /**
     * Expand common abbreviations in course names
     */
    public function expandAbbr(string $str): string
    {
        if (empty($str)) return "";
        
        $str = preg_replace('/[^a-z0-9\s]/', ' ', strtolower(trim($str)));
        
        $mappings = [
            "peng" => "pengantar", 
            "tek" => "teknologi", 
            "sis" => "sistem", 
            "info" => "informasi", 
            "algo" => "algoritma", 
            "bhs" => "bahasa", 
            "ing" => "inggris"
        ];
        
        $words = explode(" ", $str);
        $words = array_map(function($word) use ($mappings) {
            return $mappings[$word] ?? $word;
        }, $words);
        
        return implode(" ", $words);
    }

    /**
     * Calculate Levenshtein distance based similarity
     */
    public function calculateSimilarity(string $source, string $target): float
    {
        $s = strtolower($this->expandAbbr($source));
        $t = strtolower($this->expandAbbr($target));

        if ($s === $t) return 1.0;

        $distance = levenshtein($s, $t);
        $maxLength = max(strlen($s), strlen($t));
        
        if ($maxLength === 0) return 0.0;
        
        return 1.0 - ($distance / $maxLength);
    }

    /**
     * Calculate study duration estimation based on recognized credits
     */
    public function calculateStudyDuration(int $sksDiakui, int $maxSks = 144): array
    {
        $sksSisa = max(0, $maxSks - $sksDiakui);
        
        // Asumsi rata-rata 20 SKS per semester
        $rataSksPerSemester = 20;
        $estimasiSemester = ceil($sksSisa / $rataSksPerSemester);

        return [
            'sks_diakui' => $sksDiakui,
            'sks_sisa' => $sksSisa,
            'estimasi_semester' => $estimasiSemester
        ];
    }

    /**
     * Simulate conversion from a transcript against a study program's curriculum
     */
    public function simulateConversion(array $transcript, StudyProgram $studyProgram): array
    {
        // 1. Get Curriculum
        $curriculum = $studyProgram->curriculum_data ?? [];
        if (empty($curriculum)) {
            // If no curriculum, use empty match
            return [
                'recognized_courses' => [],
                'unrecognized_courses' => $transcript,
                'total_sks_diakui' => 0,
                'stats' => $this->calculateStudyDuration(0)
            ];
        }

        // Parse curriculum flat list
        $targetCourses = [];
        foreach ($curriculum as $row) {
            // Find code, name, sks, sem
            $name = $row['name'] ?? $row['nama'] ?? $row['mata_kuliah'] ?? null;
            $sks = (int) ($row['sks'] ?? $row['kredit'] ?? 0);
            $sem = (int) ($row['semester'] ?? $row['sem'] ?? 1);
            $type = $row['type'] ?? $row['tipe'] ?? 'Wajib';

            if ($name) {
                $targetCourses[] = [
                    'name' => $name,
                    'sks' => $sks,
                    'semester' => $sem,
                    'type' => $type
                ];
            }
        }

        // 2. Setup Match Tracking
        $recognized = [];
        $unrecognized = [];
        $matchedTargetIndices = [];
        $totalSksDiakui = 0;

        // Valid grades for conversion
        $validGrades = ['A', 'A-', 'B+', 'B', 'B-', 'C+', 'C'];

        // 3. Process Transcript
        foreach ($transcript as $sourceItem) {
            $sourceName = $sourceItem['name'] ?? '';
            $sourceGrade = strtoupper(trim($sourceItem['grade'] ?? ''));
            $sourceSks = (int) ($sourceItem['sks'] ?? 0);

            if (empty($sourceName) || !in_array($sourceGrade, $validGrades)) {
                $unrecognized[] = cloneArray($sourceItem, ['reason' => 'Invalid grade or empty name']);
                continue;
            }

            $bestMatch = null;
            $bestScore = 0.0;
            $bestTargetIdx = -1;

            foreach ($targetCourses as $idx => $target) {
                // Skip if already matched
                if (in_array($idx, $matchedTargetIndices)) continue;

                $score = $this->calculateSimilarity($sourceName, $target['name']);
                if ($score > 0.65 && $score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = $target;
                    $bestTargetIdx = $idx;
                }
            }

            if ($bestMatch) {
                $matchedTargetIndices[] = $bestTargetIdx;
                $sksDiakui = $bestMatch['sks']; // Typically, we use the target's SKS
                
                $recognized[] = [
                    'source_name' => $sourceName,
                    'source_grade' => $sourceGrade,
                    'source_sks' => $sourceSks,
                    'target_name' => $bestMatch['name'],
                    'target_sks' => $sksDiakui,
                    'target_semester' => $bestMatch['semester'],
                    'similarity_score' => round($bestScore, 2)
                ];
                $totalSksDiakui += $sksDiakui;
            } else {
                $unrecognized[] = cloneArray($sourceItem, ['reason' => 'No matching course found']);
            }
        }

        // 4. Calculate Stats
        $stats = $this->calculateStudyDuration($totalSksDiakui);

        return [
            'recognized_courses' => $recognized,
            'unrecognized_courses' => $unrecognized,
            'total_sks_diakui' => $totalSksDiakui,
            'stats' => $stats
        ];
    }
}

/**
 * Helper fn
 */
function cloneArray(array $original, array $additional = []): array {
    return array_merge($original, $additional);
}
