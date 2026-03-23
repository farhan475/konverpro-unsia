<?php

namespace App\Services\SuperAdmin;

use App\Models\NotificationTemplate;
use Illuminate\Support\Collection;

class NotificationTemplateService
{
    public function list(): Collection
    {
        return NotificationTemplate::query()
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(array $validated): NotificationTemplate
    {
        return NotificationTemplate::create($validated);
    }

    public function update(string $id, array $validated): NotificationTemplate
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->update($validated);

        return $template->fresh();
    }

    public function delete(string $id): void
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->delete();
    }
}
