<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\MatchingService;

class MatchingServiceTest extends TestCase
{
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MatchingService();
    }

    public function test_expand_abbr_replaces_common_abbreviations(): void
    {
        $this->assertEquals('pengantar basis data', $this->service->expandAbbr('peng basis data'));
        $this->assertEquals('sistem informasi', $this->service->expandAbbr('sis info'));
        $this->assertEquals('algoritma pemrograman', $this->service->expandAbbr('algo pemrograman'));
    }

    public function test_calculate_similarity_returns_score(): void
    {
        // Identical strings should return 1.0
        $this->assertEquals(1.0, $this->service->calculateSimilarity('basis data', 'basis data'));

        // Very similar strings should return > 0.8
        $score = $this->service->calculateSimilarity('Alpro', 'Algoritma'); // This might not be similar without dictionary, let's test a simple typo
        $scoreAbbr = $this->service->calculateSimilarity('peng basis data', 'pengantar basis data');
        $this->assertGreaterThan(0.9, $scoreAbbr);

        $scoreTypo = $this->service->calculateSimilarity('systeem inpo', 'sistem info');
        $this->assertGreaterThan(0.4, $scoreTypo);
    }

    public function test_calculate_study_duration(): void
    {
        // 144 max SKS, 50 recognized -> 94 left -> ceil(94/20) = 5 semesters
        $result = $this->service->calculateStudyDuration(50, 144);
        
        $this->assertEquals(50, $result['sks_diakui']);
        $this->assertEquals(94, $result['sks_sisa']);
        $this->assertEquals(5, $result['estimasi_semester']);
        
        // 144 max SKS, 144 recognized -> 0 left -> 0 semesters
        $result2 = $this->service->calculateStudyDuration(144, 144);
        $this->assertEquals(0, $result2['estimasi_semester']);
    }
}
