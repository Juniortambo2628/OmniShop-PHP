<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = \App\Models\Setting::all()->pluck('value', 'key');
        $events = config('events');
        
        return view('admin.settings.index', compact('settings', 'events'));
    }

    public function update(Request $request)
    {
        $data = $request->except('_token');
        
        foreach ($data as $key => $value) {
            if ($value === null) continue;
            
            // Skip empty password if not being changed
            if ($key === 'smtp_password' && empty($value)) continue;

            \App\Models\Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return back()->with('success', 'Settings saved successfully.');
    }
}
