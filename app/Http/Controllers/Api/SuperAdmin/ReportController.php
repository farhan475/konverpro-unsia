<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\Conversion;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function getAuditLogs()
    {
        $logs = AuditLog::with('user:id,name,role')->orderBy('created_at', 'desc')->paginate(20);
        return response()->json(['data' => $logs]);
    }

    public function getRevenueChart()
    {
        // Simple grouped data for frontend charts
        // grouping by month
        $transactions = Transaction::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, type, SUM(amount) as total')
            ->where('status', 'success') // Or other status indicating revenue
            ->groupBy('year', 'month', 'type')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit(24)
            ->get();
            
        // Formatting for easier frontend consumption
        $chartData = $transactions->map(function ($t) {
            return [
                'period' => $t->year . '-' . str_pad($t->month, 2, '0', STR_PAD_LEFT),
                'type' => $t->type,
                'total' => $t->total
            ];
        });

        // Get basic counts
        $summary = [
            'total_conversions' => Conversion::count(),
            'total_internal' => Conversion::whereHas('university', function($q) { $q->where('billing_mode', 'subsidy'); })->count(),
            'total_lead' => Conversion::whereHas('university', function($q) { $q->where('billing_mode', 'independent'); })->count(),
        ];

        return response()->json([
            'chart' => $chartData,
            'summary' => $summary
        ]);
    }
}
