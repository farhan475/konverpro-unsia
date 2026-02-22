<?php

namespace App\Services;

use App\Models\Conversion;
use App\Models\University;
use App\Models\Transaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ProcessTranscriptJob;
use Exception;

class ConversionService
{
    /**
     * Handle proses submit transkrip dari mahasiswa
     */
    public function submitTranscript(array $data, UploadedFile $file)
    {
        return DB::transaction(function () use ($data, $file) {
            // 1. Ambil Data Kampus Tujuan
            $university = University::findOrFail($data['university_id']);
            
            // 2. Logic Pembayaran (Hybrid)
            $paymentStatus = 'pending';
            $amount = 0;
            
            if ($university->billing_mode === 'subsidy') {
                // Skenario A: Subsidi (Kampus Bayar)
                if (!$university->canSubsidyCheck()) {
                    throw new Exception("Kuota kampus habis atau saldo tidak mencukupi.");
                }

                // Potong Saldo Kampus (Atomic)
                $university->decrement('balance', $university->cost_per_check);
                
                // Catat Transaksi Kampus (Agar tercatat di Audit)
                $this->recordTransaction(
                    $university, 
                    null, 
                    'subsidy_deduction', 
                    $university->cost_per_check
                );

                $paymentStatus = 'free'; // Mahasiswa gratis
                $status = 'processing'; // Langsung diproses
            } else {
                // Skenario B: Mandiri (Mahasiswa Bayar)
                $paymentStatus = 'pending';
                $status = 'draft'; // Tunggu bayar dulu baru diproses
                $amount = $university->student_fee;
                
                // TODO: Di sini nanti kita generate Midtrans Token
                // $snapToken = MidtransService::getSnapToken($amount);
            }

            // 3. Simpan File (Secure Storage)
            // Disimpan di folder 'private', bukan 'public' agar tidak bisa diakses via URL langsung
            $path = $file->store('transcripts/' . date('Y-m'), 'local');

            // 4. Buat Record Konversi
            $conversion = Conversion::create([
                'trx_id' => 'TRX-' . strtoupper(Str::random(8)),
                'student_id' => $data['student_id'],
                'university_id' => $university->id,
                'study_program_id' => $data['study_program_id'],
                'original_file_path' => $path,
                'status' => $status,
                'payment_status' => $paymentStatus,
                'snapshot_data' => [
                    'university_name' => $university->name,
                    'prodi_id' => $data['study_program_id'],
                    'cost' => $amount
                ]
            ]);

            // 5. Trigger Job Parsing Excel (Jika status processing)
            if ($status === 'processing') {
                // TODO: Kita akan buat Job ini di langkah berikutnya
                ProcessTranscriptJob::dispatch($conversion);
            }

            return $conversion;
        });
    }

    /**
     * Helper privat untuk mencatat mutasi saldo
     */
    private function recordTransaction($university, $user, $type, $amount)
    {
        Transaction::create([
            'invoice_number' => 'INV/' . time() . '/' . Str::random(4),
            'university_id' => $university?->id,
            'user_id' => $user?->id,
            'type' => $type,
            'amount' => $amount,
            'status' => 'success'
        ]);
    }
}