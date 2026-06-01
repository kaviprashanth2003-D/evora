<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Announcement;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $announcements = [
            'FREE ISLANDWIDE DELIVERY ON ORDERS OVER 15,000 LKR',
            'NEW ARRIVALS: EXPLORE THE DROP OF CURATED MINIMALISM',
            'SUBSCRIBE TODAY FOR AN IMMEDIATE 10% COUPON CODE'
        ];

        foreach ($announcements as $text) {
            Announcement::updateOrCreate(['text' => $text], ['text' => $text]);
        }
    }
}
