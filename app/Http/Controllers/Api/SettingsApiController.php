<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsApiController extends Controller
{
    public function index()
    {
        $settings = DB::table('settings')->pluck('value', 'key')->toArray();
        $events = config('events', []);
        $categories = config('catalog.categories', []);

        return response()->json([
            'settings'   => $settings,
            'events'     => $events,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request)
    {
        $settings = $request->input('settings', []);

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => (string)($value ?? ''), 'updated_at' => now()]
            );
        }

        return response()->json(['message' => 'Settings saved.']);
    }
}
