<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Feedback;

class FeedbackSeeder extends Seeder
{
    public function run(): void
    {
        $reviews = [
            [
                'customer_name' => 'Aisha M.',
                'rating' => 5,
                'comment' => 'Absolutely obsessed with the Aura Linen Dress. The quality is unmatched and delivery was so fast. EVORAA is my new go-to label!',
                'approved' => true
            ],
            [
                'customer_name' => 'Dilini R.',
                'rating' => 5,
                'comment' => 'The silk bias cut skirt is everything I dreamed of. Luxury feel at a really great price point.',
                'approved' => true
            ],
            [
                'customer_name' => 'Priya S.',
                'rating' => 4,
                'comment' => 'Love the packaging and the attention to detail. The knit set fits perfectly. Will definitely order again.',
                'approved' => true
            ],
        ];

        foreach ($reviews as $r) {
            Feedback::updateOrCreate(['customer_name' => $r['customer_name']], $r);
        }
    }
}
