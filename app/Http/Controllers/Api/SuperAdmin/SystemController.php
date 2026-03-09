<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\University;
use App\Models\StudyProgram;
use App\Models\Course;
use App\Models\User;
use App\Models\Conversion;
use App\Models\Transaction;
use App\Models\GlobalSetting;
use App\Models\NotificationTemplate;

class SystemController extends Controller
{
    public function exportData()
    {
        $data = [
            'universities' => University::all(),
            'study_programs' => StudyProgram::all(),
            'courses' => Course::all(),
            'users' => User::all(),
            'conversions' => Conversion::all(),
            'transactions' => Transaction::all(),
            'global_settings' => GlobalSetting::all(),
            'notification_templates' => NotificationTemplate::all(),
            'timestamp' => now()->toDateTimeString()
        ];

        return response()->json($data)->header('Content-Disposition', 'attachment; filename="backup_konverpro_'.time().'.json"');
    }

    public function importData(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimetypes:application/json'
        ]);

        try {
            DB::beginTransaction();
            
            $content = file_get_contents($request->file('backup_file')->getRealPath());
            $data = json_decode($content, true);

            if (!$data) {
                return response()->json(['message' => 'Format file tidak valid'], 400);
            }

            // A very simple demonstration of restore (In a real system, you'd truncate and insert)
            // Warning: Truncating tables here will wipe out current session data, handle with care.
            
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            if (isset($data['global_settings'])) {
                GlobalSetting::truncate();
                GlobalSetting::insert($data['global_settings']);
            }
            if (isset($data['notification_templates'])) {
                NotificationTemplate::truncate();
                NotificationTemplate::insert($data['notification_templates']);
            }

            // Note: For actual restore, it usually involves checking model relations, etc.
            // For the sake of this iteration, we do partial insert
            
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::commit();

            return response()->json(['message' => 'Restorasi data (parsial) berhasil disimulasikan']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Backup Import Gagal: ' . $e->getMessage()], 500);
        }
    }
}
