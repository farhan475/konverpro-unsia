<?php

namespace App\Services\Admin;

use App\Models\Conversion;
use App\Models\ConversionDetail;

class ConversionReviewService
{
    public function reviewDetail(string $detailId, array $validated): void
    {
        $detail = ConversionDetail::findOrFail($detailId);

        $detail->update([
            'status' => $validated['status'] === 'approved' ? 'manual_accepted' : 'rejected',
            'admin_notes' => $validated['admin_notes'] ?? null,
        ]);

        $this->recalculateTotalSks((string) $detail->conversion_id);
    }

    public function finalize(string $conversionId, array $validated): void
    {
        $conversion = Conversion::findOrFail($conversionId);
        $conversion->update([
            'status' => 'approved',
            'admin_notes' => $validated['notes'] ?? null,
        ]);
    }

    private function recalculateTotalSks(string $conversionId): void
    {
        $total = ConversionDetail::where('conversion_id', $conversionId)
            ->whereIn('status', ['auto_accepted', 'manual_accepted'])
            ->with('targetCourse')
            ->get()
            ->sum(fn (ConversionDetail $detail) => $detail->targetCourse?->sks ?? 0);

        Conversion::where('id', $conversionId)->update(['total_sks_accepted' => $total]);
    }
}
