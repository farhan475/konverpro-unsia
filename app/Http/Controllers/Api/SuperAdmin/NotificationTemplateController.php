<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;

class NotificationTemplateController extends Controller
{
    public function index()
    {
        $templates = NotificationTemplate::orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $templates]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'trigger' => 'required|string|unique:notification_templates,trigger',
            'subject' => 'required|string',
            'body' => 'required|string',
        ]);

        $template = NotificationTemplate::create($request->all());

        return response()->json(['message' => 'Template berhasil dibuat', 'data' => $template]);
    }

    public function update(Request $request, $id)
    {
        $template = NotificationTemplate::findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'trigger' => 'required|string|unique:notification_templates,trigger,' . $template->id,
            'subject' => 'required|string',
            'body' => 'required|string',
        ]);

        $template->update($request->all());

        return response()->json(['message' => 'Template berhasil diupdate', 'data' => $template]);
    }

    public function destroy($id)
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->delete();
        return response()->json(['message' => 'Template berhasil dihapus']);
    }
}
