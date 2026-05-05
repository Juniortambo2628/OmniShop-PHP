<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\StorefrontSetting;
use Illuminate\Http\Request;

class StorefrontCmsController extends Controller
{
    public function index()
    {
        return response()->json(StorefrontSetting::all());
    }

    public function store(Request $request)
    {
        $settings = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable|string',
            'settings.*.type' => 'required|string',
        ]);

        foreach ($settings['settings'] as $item) {
            StorefrontSetting::updateOrCreate(
                ['key' => $item['key']],
                ['value' => $item['value'], 'type' => $item['type']]
            );
        }

        return response()->json(['message' => 'Settings updated successfully']);
    }
}
