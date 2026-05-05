<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StorefrontSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['key' => 'hero_title', 'value' => 'FURNITURE PARTNER', 'type' => 'string'],
            ['key' => 'hero_subtitle', 'value' => 'Elevating your brand presence with premium furniture solutions.', 'type' => 'string'],
            ['key' => 'primary_color', 'value' => '#0d2e2e', 'type' => 'color'],
            ['key' => 'delivery_enabled', 'value' => 'false', 'type' => 'boolean'],
            ['key' => 'delivery_rates', 'value' => json_encode([
                'sofas' => 50,
                'chairs' => 15,
                'tables' => 30,
                'stools' => 10,
                'outdoor' => 25,
                'packages' => 100,
                'displays' => 40,
                'cabinets' => 35,
                'default' => 20
            ]), 'type' => 'json'],
            ['key' => 'chat_widget_script', 'value' => '', 'type' => 'text'],
        ];

        foreach ($settings as $s) {
            \App\Models\StorefrontSetting::updateOrCreate(['key' => $s['key']], $s);
        }
    }
}
