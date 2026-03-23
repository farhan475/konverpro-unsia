<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Conversion;
use App\Models\Transaction;
use App\Models\University;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function getAuditLogs()
    {
        $logs = AuditLog::with('user:id,name,role')->orderBy('created_at', 'desc')->paginate(20);
        $logs->setCollection(
            $logs->getCollection()->map(function (AuditLog $log) {
                return [
                    'id' => $log->id,
                    'action' => $this->formatAuditAction($log->event),
                    'event' => $log->event,
                    'actor_name' => $log->user?->name,
                    'actor_role' => $log->user?->role,
                    'description' => $this->formatAuditDescription($log),
                    'created_at' => $log->created_at,
                    'user' => $log->user
                        ? [
                            'id' => $log->user->id,
                            'name' => $log->user->name,
                            'role' => $log->user->role,
                        ]
                        : null,
                ];
            }),
        );

        return ApiResponse::success($logs);
    }

    public function getRevenueChart()
    {
        $transactions = Transaction::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, type, SUM(amount) as total')
            ->where('status', 'success')
            ->groupBy('year', 'month', 'type')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit(24)
            ->get();

        // Formatting for easier frontend consumption
        $chartData = $transactions->map(function ($t) {
            return [
                'period' => $t->year.'-'.str_pad($t->month, 2, '0', STR_PAD_LEFT),
                'type' => $t->type,
                'total' => $t->total,
            ];
        });

        // Get basic counts
        $summary = [
            'total_conversions' => Conversion::count(),
            'total_internal' => Conversion::whereHas('university', function ($q) {
                $q->where('billing_mode', 'subsidy');
            })->count(),
            'total_lead' => Conversion::whereHas('university', function ($q) {
                $q->where('billing_mode', 'independent');
            })->count(),
            'total_revenue' => (float) $transactions->sum('total'),
        ];

        return ApiResponse::success([
            'chart' => $chartData,
            'summary' => $summary,
        ]);
    }

    public function getOverview()
    {
        $campuses = University::withCount([
            'conversions',
            'users as active_users_count' => fn ($query) => $query->where('is_active', true),
        ])
            ->orderBy('created_at', 'desc')
            ->get();

        $topups = Transaction::with('university:id,name')
            ->where('type', 'topup')
            ->orderBy('created_at', 'desc')
            ->get();

        $recentTopups = $topups
            ->take(8)
            ->map(function (Transaction $transaction) {
                return [
                    'id' => $transaction->id,
                    'trx_id' => $transaction->invoice_number,
                    'amount' => (float) $transaction->amount,
                    'status' => $this->mapTransactionStatus($transaction->status),
                    'created_at' => $transaction->created_at,
                    'university' => [
                        'id' => $transaction->university?->id,
                        'name' => $transaction->university?->name,
                    ],
                ];
            })
            ->values();

        $latestConversions = Conversion::with([
            'university:id,name',
            'studyProgram:id,name,level',
            'student:id,name',
        ])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get()
            ->map(function (Conversion $conversion) {
                return [
                    'id' => $conversion->id,
                    'trx_id' => $conversion->trx_id,
                    'status' => $conversion->status,
                    'payment_status' => $conversion->payment_status,
                    'created_at' => $conversion->created_at,
                    'student_name' => $conversion->student?->name,
                    'university_name' => $conversion->university?->name,
                    'study_program_name' => $conversion->studyProgram?->name,
                    'total_sks_accepted' => (int) ($conversion->total_sks_accepted ?? 0),
                ];
            })
            ->values();

        $growth = $this->buildGrowthSeries();
        $campusHeatmap = $this->buildCampusHeatmap($campuses, $topups);
        $needsAttention = $this->buildAttentionList($campuses, $topups);

        return ApiResponse::success([
            'stats' => [
                'totalCampuses' => University::count(),
                'activeCampuses' => University::where('is_active', true)->count(),
                'partnerCampuses' => University::where('is_partner', true)->count(),
                'totalUsers' => User::count(),
                'activeUsers' => User::where('is_active', true)->count(),
                'pendingTopups' => Transaction::where('type', 'topup')->where('status', 'pending')->count(),
                'totalConversions' => Conversion::count(),
                'approvedConversions' => Conversion::where('status', 'approved')->count(),
                'totalRevenue' => (float) Transaction::where('status', 'success')->sum('amount'),
            ],
            'growth' => $growth,
            'campus_heatmap' => $campusHeatmap,
            'insights' => [
                'needs_attention' => $needsAttention,
                'recent_topups' => $recentTopups,
                'latest_conversions' => $latestConversions,
            ],
        ]);
    }

    private function buildGrowthSeries(): array
    {
        $periods = collect(range(5, 0))->map(function (int $offset) {
            return now()->startOfMonth()->subMonths($offset)->format('Y-m');
        })->values();

        $revenueByPeriod = Transaction::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as period, SUM(amount) as total")
            ->where('status', 'success')
            ->groupBy('period')
            ->pluck('total', 'period');

        $conversionsByPeriod = Conversion::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as period, COUNT(*) as total")
            ->groupBy('period')
            ->pluck('total', 'period');

        return $periods
            ->map(function (string $period) use ($revenueByPeriod, $conversionsByPeriod) {
                return [
                    'period' => $period,
                    'revenue' => (float) ($revenueByPeriod[$period] ?? 0),
                    'conversions' => (int) ($conversionsByPeriod[$period] ?? 0),
                ];
            })
            ->all();
    }

    private function buildCampusHeatmap(Collection $campuses, Collection $topups): array
    {
        $pendingTopups = $topups
            ->where('status', 'pending')
            ->countBy('university_id');

        return $campuses
            ->map(function (University $campus) use ($pendingTopups) {
                $pendingCount = (int) ($pendingTopups[$campus->id] ?? 0);
                $balance = (float) ($campus->balance ?? 0);
                $conversions = (int) ($campus->conversions_count ?? 0);

                $score = max(
                    18,
                    min(
                        98,
                        $conversions * 12 +
                        ($campus->is_partner ? 22 : 8) +
                        ($campus->is_active ? 16 : 4) +
                        min((int) round($balance / 1000000) * 4, 22) -
                        ($pendingCount * 6),
                    ),
                );

                return [
                    'id' => $campus->id,
                    'name' => $campus->name,
                    'score' => $score,
                    'statusLabel' => $score >= 75
                        ? 'Sangat Aktif'
                        : ($score >= 55 ? 'Stabil' : ($score >= 35 ? 'Perlu Optimasi' : 'Atensi')),
                    'conversions' => $conversions,
                    'balance' => $balance,
                    'pendingTopups' => $pendingCount,
                    'isPartner' => (bool) $campus->is_partner,
                    'isActive' => (bool) $campus->is_active,
                    'activeUsers' => (int) ($campus->active_users_count ?? 0),
                ];
            })
            ->sortByDesc('score')
            ->take(8)
            ->values()
            ->all();
    }

    private function buildAttentionList(Collection $campuses, Collection $topups): array
    {
        $pendingTopups = $topups
            ->where('status', 'pending')
            ->countBy('university_id');

        return $campuses
            ->map(function (University $campus) use ($pendingTopups) {
                $pendingCount = (int) ($pendingTopups[$campus->id] ?? 0);
                $reasons = [];
                $priority = 0;

                if (! $campus->is_active) {
                    $reasons[] = 'Status kampus belum aktif';
                    $priority += 3;
                }

                if ((float) $campus->balance <= 1000000) {
                    $reasons[] = 'Saldo kampus menipis';
                    $priority += 2;
                }

                if ($pendingCount > 0) {
                    $reasons[] = $pendingCount.' top up menunggu proses';
                    $priority += 1;
                }

                if ($campus->conversions_count === 0) {
                    $reasons[] = 'Belum ada aktivitas konversi';
                    $priority += 1;
                }

                return [
                    'id' => $campus->id,
                    'name' => $campus->name,
                    'priority' => $priority,
                    'reasons' => $reasons,
                ];
            })
            ->filter(fn (array $campus) => $campus['priority'] > 0)
            ->sortByDesc('priority')
            ->take(6)
            ->values()
            ->all();
    }

    private function mapTransactionStatus(string $status): string
    {
        return $status === 'success'
            ? 'approved'
            : ($status === 'failed' ? 'rejected' : 'pending');
    }

    private function formatAuditAction(string $event): string
    {
        return Str::of($event)->replace('.', ' ')->headline()->toString();
    }

    private function formatAuditDescription(AuditLog $log): string
    {
        $oldValues = is_array($log->old_values) ? $log->old_values : [];
        $newValues = is_array($log->new_values) ? $log->new_values : [];
        $target = $newValues['name']
            ?? $oldValues['name']
            ?? $newValues['email']
            ?? $oldValues['email']
            ?? $newValues['trx_id']
            ?? $oldValues['trx_id']
            ?? class_basename($log->auditable_type);

        if (str_ends_with($log->event, '.created')) {
            return sprintf('%s dibuat di sistem.', $target);
        }

        if (str_ends_with($log->event, '.updated')) {
            return sprintf('%s diperbarui di sistem.', $target);
        }

        if (str_ends_with($log->event, '.deleted')) {
            return sprintf('%s dihapus dari sistem.', $target);
        }

        if (str_ends_with($log->event, '.approved')) {
            return sprintf('%s berhasil disetujui.', $target);
        }

        if (str_ends_with($log->event, '.rejected')) {
            return sprintf('%s ditolak oleh operator.', $target);
        }

        if (str_ends_with($log->event, '.balance_adjusted')) {
            $amount = $newValues['amount'] ?? null;

            return $amount !== null
                ? sprintf('%s disesuaikan dengan nominal %s.', $target, number_format((float) $amount, 0, ',', '.'))
                : sprintf('Saldo %s disesuaikan.', $target);
        }

        return sprintf('Aktivitas tercatat untuk %s.', $target);
    }
}
