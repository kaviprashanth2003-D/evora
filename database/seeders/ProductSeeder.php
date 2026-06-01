<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'product_code' => 'prod-001',
                'name' => 'Aura Linen Halter Maxi Dress',
                'category' => 'MAXI DRESSES',
                'description' => 'An elegant, flowing maxi dress crafted from premium organic Sri Lankan linen. Features a sophisticated halter neckline, low open back, and dual side slits for liquid-like movement. Perfect for sunset soirées.',
                'image1' => 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?q=80&w=600&auto=format&fit=crop',
                'image2' => 'https://images.unsplash.com/photo-1539008835657-9e8e9680fe0a?q=80&w=600&auto=format&fit=crop',
                'original_price' => 12500.00,
                'discount_price' => 10625.00,
                'discount_active' => true,
                'offer_badge' => '15% OFF',
                'stock_xs' => 5,
                'stock_s' => 8,
                'stock_m' => 12,
                'stock_l' => 6,
                'stock_xl' => 4,
            ],
            [
                'product_code' => 'prod-002',
                'name' => 'Luxe Crepe Pleated Pants',
                'category' => 'PANTS',
                'description' => 'Tailored to high-fashion perfection. These high-waisted crepe pants feature structural front pleats, a wide-leg silhouette, and concealed pocket enclosures. Offers both structure and luxury drape.',
                'image1' => 'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?q=80&w=600&auto=format&fit=crop',
                'image2' => 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?q=80&w=600&auto=format&fit=crop',
                'original_price' => 8900.00,
                'discount_price' => 8900.00,
                'discount_active' => false,
                'offer_badge' => 'NEW DROP',
                'stock_xs' => 4,
                'stock_s' => 10,
                'stock_m' => 15,
                'stock_l' => 8,
                'stock_xl' => 2,
            ],
            [
                'product_code' => 'prod-003',
                'name' => 'Silk Satin Bias Cut Skirt',
                'category' => 'SKIRTS',
                'description' => 'Cut on the bias to drape effortlessly over curves. Crafted from liquid-finish silk satin with a delicate elasticated waistband for maximum comfort. Features a soft cream palette that matches any premium capsule closet.',
                'image1' => 'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?q=80&w=600&auto=format&fit=crop',
                'image2' => 'https://images.unsplash.com/photo-1609357518652-6cf0416f0cbe?q=80&w=600&auto=format&fit=crop',
                'original_price' => 9500.00,
                'discount_price' => 7500.00,
                'discount_active' => true,
                'offer_badge' => 'SALE',
                'stock_xs' => 3,
                'stock_s' => 6,
                'stock_m' => 8,
                'stock_l' => 5,
                'stock_xl' => 3,
            ],
            [
                'product_code' => 'prod-004',
                'name' => 'Ethereal Knit Wrap Top',
                'category' => 'TOPS',
                'description' => 'Meticulously spun from fine gauge cotton-silk yarn. Designed with elongated wraps that cinch the waist and a soft plunge ribbed neckline. Lightweight, breathable, and highly premium.',
                'image1' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?q=80&w=600&auto=format&fit=crop',
                'image2' => 'https://images.unsplash.com/photo-1509631179647-0177331693ae?q=80&w=600&auto=format&fit=crop',
                'original_price' => 6200.00,
                'discount_price' => 6200.00,
                'discount_active' => false,
                'offer_badge' => '',
                'stock_xs' => 8,
                'stock_s' => 12,
                'stock_m' => 12,
                'stock_l' => 10,
                'stock_xl' => 5,
            ],
            [
                'product_code' => 'prod-005',
                'name' => 'Rosmead Linen Classic Shirt',
                'category' => 'SHIRTS',
                'description' => 'The ultimate relaxed tailored shirt. Features structural horn buttons, a classic drop-shoulder style, and oversized utility breast pockets. Extremely versatile, double-hemmed for longevity.',
                'image1' => 'https://images.unsplash.com/photo-1607345366928-199ea26cfe3e?q=80&w=600&auto=format&fit=crop',
                'image2' => 'https://images.unsplash.com/photo-1548624149-f7b3be6894fd?q=80&w=600&auto=format&fit=crop',
                'original_price' => 7900.00,
                'discount_price' => 7900.00,
                'discount_active' => false,
                'offer_badge' => 'BUY 1 GET 1',
                'stock_xs' => 6,
                'stock_s' => 10,
                'stock_m' => 15,
                'stock_l' => 12,
                'stock_xl' => 6,
            ],
            [
                'product_code' => 'prod-006',
                'name' => 'Structured Denim Trench',
                'category' => 'DENIM',
                'description' => 'A showstopping minimalist trench jacket. Features mid-weight premium raw Japanese denim, a double-breasted button panel, and a coordinated wrap belt. Tailored to wow at first glance.',
                'image1' => 'https://images.unsplash.com/photo-1611312449412-6cefac5dc3e4?q=80&w=600&auto=format&fit=crop',
                'image2' => 'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?q=80&w=600&auto=format&fit=crop',
                'original_price' => 18500.00,
                'discount_price' => 18500.00,
                'discount_active' => false,
                'offer_badge' => 'CLASSIC',
                'stock_xs' => 2,
                'stock_s' => 5,
                'stock_m' => 6,
                'stock_l' => 4,
                'stock_xl' => 2,
            ],
            [
                'product_code' => 'prod-007',
                'name' => 'Atelier Ribbed Knit Set',
                'category' => 'SETS',
                'description' => 'A coordinated two-piece top and skirt set crafted from a heavy ribbed viscose blend. Fits seamlessly, offering a luxurious weight that retains its shape and moves elegantly.',
                'image1' => 'https://images.unsplash.com/photo-1496747611176-843222e1e57c?q=80&w=600&auto=format&fit=crop',
                'image2' => 'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?q=80&w=600&auto=format&fit=crop',
                'original_price' => 16900.00,
                'discount_price' => 13520.00,
                'discount_active' => true,
                'offer_badge' => '20% OFF',
                'stock_xs' => 3,
                'stock_s' => 8,
                'stock_m' => 10,
                'stock_l' => 6,
                'stock_xl' => 4,
            ]
        ];

        foreach ($products as $p) {
            Product::updateOrCreate(['product_code' => $p['product_code']], $p);
        }
    }
}
