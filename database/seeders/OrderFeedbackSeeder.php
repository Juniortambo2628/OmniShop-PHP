<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrderFeedback;

class OrderFeedbackSeeder extends Seeder
{
    public function run(): void
    {
        $feedback = [
            [
                'order_id' => 'GFT24-0001',
                'rating' => 5,
                'comment' => 'Excellent service! The furniture was delivered exactly as requested and looked stunning at our booth.',
                'created_at' => now()->subDays(2),
            ],
            [
                'order_id' => 'GFT24-0005',
                'rating' => 4,
                'comment' => 'Very professional team. One of the chairs had a minor scratch, but they replaced it within an hour.',
                'created_at' => now()->subDays(1),
            ],
            [
                'order_id' => 'GFT24-0012',
                'rating' => 5,
                'comment' => 'The online catalog is very easy to use. Highly recommended for any exhibition needs!',
                'created_at' => now()->subHours(5),
            ],
            [
                'order_id' => 'GFT24-0015',
                'rating' => 3,
                'comment' => 'Good selection but delivery was slightly delayed due to traffic. Furniture quality is great though.',
                'created_at' => now()->subHours(2),
            ],
        ];

        foreach ($feedback as $f) {
            OrderFeedback::create($f);
        }
    }
}
