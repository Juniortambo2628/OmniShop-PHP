<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MockOrderSeeder extends Seeder
{
    public function run()
    {
        $events = ['GFT24', 'FSH25', 'IND24', 'LIF25'];
        $statuses = ['Pending', 'Approved', 'Invoiced', 'Fulfilled', 'Cancelled'];
        
        $companies = [
            'TechSolutions Ltd', 'Creative Designs', 'Eco-Build Group', 'Global Logistics',
            'Prime Real Estate', 'Visionary Media', 'Aqua Pure Systems', 'Heritage Furnishings',
            'Summit Outdoors', 'Urban Living', 'North Star Exports', 'Zentech Industries'
        ];

        $contacts = [
            'John Doe', 'Sarah Smith', 'Michael Chen', 'Elena Rodriguez',
            'David Kim', 'Lisa Wong', 'Marcus Thorne', 'Sophie Bennett'
        ];

        $products = [
            ['code' => 'SOF09A', 'name' => 'Signature 3-Seater Sofa', 'price' => 1250.00],
            ['code' => 'TBL02', 'name' => 'Minimalist Coffee Table', 'price' => 450.00],
            ['code' => 'CHR05', 'name' => 'Ergonomic Office Chair', 'price' => 320.00],
            ['code' => 'LMP01', 'name' => 'Modern Floor Lamp', 'price' => 180.00],
            ['code' => 'DNR04', 'name' => 'Walnut Dining Table', 'price' => 2100.00],
            ['code' => 'DSK02', 'name' => 'Standing Desk (Electric)', 'price' => 850.00],
        ];

        for ($i = 0; $i < 50; $i++) {
            $event = $events[array_rand($events)];
            $status = $statuses[array_rand($statuses)];
            $company = $companies[array_rand($companies)];
            $contact = $contacts[array_rand($contacts)];
            
            $orderId = Carbon::now()->format('y') . '-' . Str::upper(Str::random(6));
            $createdAt = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23));

            $order = [
                'order_id'     => $orderId,
                'event_slug'   => $event,
                'company_name' => $company,
                'contact_name' => $contact,
                'email'        => strtolower(str_replace(' ', '.', $contact)) . '@' . strtolower(str_replace(' ', '', $company)) . '.com',
                'phone'        => '+254 ' . rand(700, 799) . ' ' . rand(100, 999) . ' ' . rand(100, 999),
                'booth_number' => Str::upper(Str::random(1)) . rand(1, 50),
                'status'       => $status,
                'notes'        => rand(0, 1) ? 'Please deliver before 9 AM.' : null,
                'created_at'   => $createdAt,
                'updated_at'   => $createdAt,
                'total'        => 0, // placeholder
            ];

            DB::table('orders')->insert($order);

            $itemCount = rand(1, 4);
            $total = 0;
            
            for ($j = 0; $j < $itemCount; $j++) {
                $p = $products[array_rand($products)];
                $qty = rand(1, 5);
                $subtotal = $p['price'] * $qty;
                $total += $subtotal;

                DB::table('order_items')->insert([
                    'order_id'     => $orderId,
                    'product_code' => $p['code'],
                    'product_name' => $p['name'],
                    'quantity'     => $qty,
                    'unit_price'   => $p['price'],
                    'total_price'  => $subtotal,
                    'created_at'   => $createdAt,
                    'updated_at'   => $createdAt,
                ]);
            }

            DB::table('orders')->where('order_id', $orderId)->update(['total' => $total]);
        }
    }
}
