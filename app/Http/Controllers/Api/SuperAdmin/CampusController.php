<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\University;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CampusController extends Controller
{
    public function index()
    {
        $campuses = University::withCount('conversions')->orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $campuses]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'plan' => 'required|string',
        ]);

        $university = University::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name . '-' . time()),
            'billing_mode' => 'subsidy', // default
            'student_fee' => 0,
            'cost_per_check' => 0,
            'balance' => 0,
            'is_active' => true,
            'is_partner' => $request->plan === 'Enterprise',
            'settings' => [
                'email' => $request->email,
                'plan' => $request->plan,
            ]
        ]);

        return response()->json(['message' => 'Kampus berhasil didaftarkan', 'data' => $university]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'status' => 'required|in:active,pending,suspended',
            'plan' => 'required|string',
            'is_partner' => 'required|boolean',
        ]);

        $university = University::findOrFail($id);
        
        $settings = $university->settings ?? [];
        $settings['email'] = $request->email;
        $settings['plan'] = $request->plan;
        if ($request->has('custom_rates')) {
            $settings['custom_rates'] = $request->custom_rates;
        } else {
            unset($settings['custom_rates']);
        }

        $university->update([
            'name' => $request->name,
            'is_active' => $request->status === 'active',
            'is_partner' => $request->is_partner,
            'student_registration_fee' => $request->regFee ?? $university->student_registration_fee,
            'student_fee' => $request->tuitionFee ?? $university->student_fee,
            'settings' => $settings,
        ]);

        return response()->json(['message' => 'Kampus berhasil diupdate', 'data' => $university]);
    }

    public function destroy($id)
    {
        $university = University::findOrFail($id);
        $university->delete();
        return response()->json(['message' => 'Kampus berhasil dihapus']);
    }

    // Optional: adjust balance manual
    public function adjustBalance(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric'
        ]);

        $university = University::findOrFail($id);
        $university->increment('balance', $request->amount);

        return response()->json(['message' => 'Saldo berhasil diupdate', 'balance' => $university->balance]);
    }
}
