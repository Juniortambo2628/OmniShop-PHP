<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Event;
use Illuminate\Support\Facades\Hash;

class AdminAndEventsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin
        User::updateOrCreate(
            ['email' => 'admin@omnishop.com'],
            [
                'name' => 'OmniShop Admin',
                'password' => Hash::make('password123'),
            ]
        );

        // Create Events from config
        $events = config('events');

        if ($events) {
            foreach ($events as $slug => $data) {
                Event::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $data['name'],
                        'dates' => $data['dates'] ?? '',
                        'venue' => $data['venue'] ?? '',
                        'logo' => $data['logo'] ?? '',
                        'contact_email' => $data['contact_email'] ?? '',
                        'catalog_password_default' => $data['catalog_password_default'] ?? '',
                        'order_prefix' => $data['order_prefix'] ?? '',
                        'deadlines' => $data['deadlines'] ?? [],
                    ]
                );
            }
        }
    }
}
