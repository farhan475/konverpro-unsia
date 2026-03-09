<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudyProgram;
use App\Services\MatchingService;

class SimulationController extends Controller
{
    protected $matchingService;

    public function __construct(MatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    /**
     * Simulate a conversion transcript against a study program
     */
    public function simulate(Request $request)
    {
        $request->validate([
            'study_program_id' => 'required|exists:study_programs,id',
            'transcript' => 'required|array',
            'transcript.*.name' => 'required|string',
            'transcript.*.grade' => 'required|string',
            'transcript.*.sks' => 'required|integer',
        ]);

        $studyProgram = StudyProgram::findOrFail($request->study_program_id);
        
        $result = $this->matchingService->simulateConversion(
            $request->transcript,
            $studyProgram
        );

        return response()->json([
            'status' => 'success',
            'data' => $result
        ]);
    }
}
