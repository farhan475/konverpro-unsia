<?php

namespace App\Services\SuperAdmin;

use App\Models\Transaction;
use App\Models\University;
use App\Support\AuditLogSupport;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TopupManagementService
{
    public function list(): Collection
    {
        return Transaction::query()
            ->select([
                'id',
                'invoice_number',
                'university_id',
                'amount',
                'status',
                'created_at',
            ])
            ->with('university:id,name')
            ->where('type', 'topup')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Transaction $transaction) => $this->serialize($transaction))
            ->values();
    }

    public function process(string $id, string $status, Request $request): void
    {
        DB::transaction(function () use ($id, $status, $request) {
            $transaction = Transaction::query()
                ->lockForUpdate()
                ->findOrFail($id);

            if ($transaction->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Transaksi sudah diproses sebelumnya.',
                ]);
            }

            if ($status === 'approved') {
                $transaction->update(['status' => 'success']);

                $university = University::query()
                    ->lockForUpdate()
                    ->findOrFail($transaction->university_id);
                $university->increment('balance', $transaction->amount);

                AuditLogSupport::record(
                    $request,
                    'topup.approved',
                    $transaction,
                    ['status' => 'pending'],
                    [
                        'trx_id' => $transaction->invoice_number,
                        'status' => 'approved',
                        'amount' => (float) $transaction->amount,
                        'university_id' => $transaction->university_id,
                    ],
                );

                return;
            }

            $transaction->update(['status' => 'failed']);

            AuditLogSupport::record(
                $request,
                'topup.rejected',
                $transaction,
                ['status' => 'pending'],
                [
                    'trx_id' => $transaction->invoice_number,
                    'status' => 'rejected',
                    'amount' => (float) $transaction->amount,
                    'university_id' => $transaction->university_id,
                ],
            );
        });
    }

    private function serialize(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'trx_id' => $transaction->invoice_number,
            'campusId' => $transaction->university_id,
            'campusName' => $transaction->university->name ?? 'Unknown',
            'amount' => $transaction->amount,
            'status' => $transaction->status === 'success'
                ? 'approved'
                : ($transaction->status === 'failed' ? 'rejected' : 'pending'),
            'created_at' => $transaction->created_at,
            'university_id' => $transaction->university_id,
            'university_name' => $transaction->university->name ?? 'Unknown',
        ];
    }
}
