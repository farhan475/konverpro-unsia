<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\University;
use App\Models\StudyProgram;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CampusSettingsController extends Controller
{
    // ==========================================
    // 1. PROFIL KAMPUS
    // ==========================================
    public function getProfile()
    {
        $university = University::findOrFail(Auth::user()->university_id);
        return response()->json(['data' => $university]);
    }

    public function updateProfile(Request $request)
    {
        $university = University::findOrFail(Auth::user()->university_id);
        
        $data = $request->only(['name', 'website']);
        
        // Handle upload logo jika ada
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo_path'] = $path;
        }

        $university->update($data);
        return response()->json(['message' => 'Profil berhasil diperbarui', 'data' => $university]);
    }

    // ==========================================
    // 2. PROGRAM STUDI (CRUD)
    // ==========================================
    public function getProdis()
    {
        $prodis = StudyProgram::where('university_id', Auth::user()->university_id)->get();
        return response()->json(['data' => $prodis]);
    }

    public function storeProdi(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'name' => 'required|string',
            'level' => 'required|in:D3,D4,S1,S2'
        ]);

        $prodi = StudyProgram::create([
            'university_id' => Auth::user()->university_id,
            'code' => $request->code,
            'name' => $request->name,
            'level' => $request->level,
            'is_active' => true
        ]);

        return response()->json(['message' => 'Prodi berhasil ditambahkan', 'data' => $prodi]);
    }

    public function destroyProdi($id)
    {
        $prodi = StudyProgram::where('id', $id)
            ->where('university_id', Auth::user()->university_id)
            ->firstOrFail();
            
        $prodi->delete(); // Soft delete bekerja di sini
        return response()->json(['message' => 'Prodi berhasil dihapus']);
    }

    // ==========================================
    // 3. KAMUS SINONIM AI (Mengelola keywords di courses)
    // ==========================================
    public function getDictionary()
    {
        // Ambil semua mata kuliah milik kampus ini
        $courses = Course::whereHas('studyProgram', function($q) {
            $q->where('university_id', Auth::user()->university_id);
        })->get(['id', 'name', 'keywords']);

        return response()->json(['data' => $courses]);
    }

    public function updateDictionary(Request $request, $courseId)
    {
        $request->validate([
            'keywords' => 'nullable|array'
        ]);

        $course = Course::whereHas('studyProgram', function($q) {
            $q->where('university_id', Auth::user()->university_id);
        })->findOrFail($courseId);

        $course->update([
            'keywords' => $request->keywords
        ]);

        return response()->json(['message' => 'Kamus berhasil diperbarui']);
    }
}