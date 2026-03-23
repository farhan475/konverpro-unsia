<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogSupport
{
    public static function record(
        ?Request $request,
        string $event,
        Model $auditable,
        array $oldValues = [],
        array $newValues = [],
    ): void {
        try {
            AuditLog::create([
                'user_id' => $request?->user()?->id,
                'event' => $event,
                'auditable_type' => $auditable::class,
                'auditable_id' => (string) $auditable->getKey(),
                'old_values' => $oldValues ?: null,
                'new_values' => $newValues ?: null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
