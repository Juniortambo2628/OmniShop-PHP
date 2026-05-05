<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin User
        User::updateOrCreate(
            ['email' => 'admin@omnispace3d.com'],
            [
                'name' => 'Susan Mboya',
                'password' => Hash::make('OmniAdmin2026!'),
            ]
        );

        // Default Settings
        $settings = [
            'company_name'    => 'OmniSpace 3D Events Ltd',
            'company_address' => 'P.O. Box 00200, Nairobi, Kenya',
            'company_phone'   => '+254 731 001 723 | +254 769 361 804',
            'company_whatsapp'=> '+254731001723',
            'company_email'   => 'info@omnispace3d.com',
            'company_website' => 'www.omnispace3d.com',
            'company_pin'     => 'P051469673L',
            'invoice_terms'   => "1. Location and event date as specified in this quotation/invoice.\n2. Set-up/set-down timeline — Set up to be complete 24 hours before handover; set down will begin within 12 hours of end of event.\n3. Quotation subject to agreed layout at a specific location. Subject to change if layout changes.\n4. Quotation may be part of a package offering inclusive of furniture.\n5. Client to book venue in advance to allow adequate time for set-up and set-down.\n6. The above quotation covers rental of our equipment for the specified period only.\n7. Following handover the client is responsible for safety and security of the products.",
            'invoice_payment_note' => "Payment: Bank transfer to OmniSpace 3D Events Ltd. Acc No: 1234567890, Bank: Equity Bank, Branch: Westlands.\nWhatsApp: +254731001723",
            'smtp_host'           => 'smtp.gmail.com',
            'smtp_port'           => '587',
            'smtp_user'           => 'orders@omnispace3d.com',
            'smtp_password'       => '',
            'smtp_from_email'     => 'orders@omnispace3d.com',
            'smtp_from_name'      => 'OmniSpace 3D Events — Orders',
            'notifications_to'    => 'orders@omnispace3d.com',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
