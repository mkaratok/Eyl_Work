<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Electronics & Technology
            ['name' => 'Elektronik', 'merchant_id' => '166', 'parent_id' => null, 'level' => 0],
            ['name' => 'Bilgisayar & Tablet', 'merchant_id' => '325', 'parent_id' => 1, 'level' => 1],
            ['name' => 'Cep Telefonu & Aksesuarları', 'merchant_id' => '267', 'parent_id' => 1, 'level' => 1],
            ['name' => 'TV & Ses Sistemleri', 'merchant_id' => '1420', 'parent_id' => 1, 'level' => 1],
            ['name' => 'Kamera & Fotoğraf', 'merchant_id' => '147', 'parent_id' => 1, 'level' => 1],
            
            // Fashion & Clothing
            ['name' => 'Giyim & Moda', 'merchant_id' => '1604', 'parent_id' => null, 'level' => 0],
            ['name' => 'Kadın Giyim', 'merchant_id' => '1604', 'parent_id' => 6, 'level' => 1],
            ['name' => 'Erkek Giyim', 'merchant_id' => '1604', 'parent_id' => 6, 'level' => 1],
            ['name' => 'Çocuk Giyim', 'merchant_id' => '5322', 'parent_id' => 6, 'level' => 1],
            ['name' => 'Ayakkabı', 'merchant_id' => '187', 'parent_id' => 6, 'level' => 1],
            
            // Home & Garden
            ['name' => 'Ev & Bahçe', 'merchant_id' => '2901', 'parent_id' => null, 'level' => 0],
            ['name' => 'Mobilya', 'merchant_id' => '436', 'parent_id' => 11, 'level' => 1],
            ['name' => 'Ev Dekorasyonu', 'merchant_id' => '2901', 'parent_id' => 11, 'level' => 1],
            ['name' => 'Mutfak & Yemek', 'merchant_id' => '668', 'parent_id' => 11, 'level' => 1],
            ['name' => 'Bahçe & Dış Mekan', 'merchant_id' => '2962', 'parent_id' => 11, 'level' => 1],
            
            // Health & Beauty
            ['name' => 'Sağlık & Güzellik', 'merchant_id' => '469', 'parent_id' => null, 'level' => 0],
            ['name' => 'Kozmetik & Kişisel Bakım', 'merchant_id' => '469', 'parent_id' => 16, 'level' => 1],
            ['name' => 'Parfüm & Deodorant', 'merchant_id' => '2915', 'parent_id' => 16, 'level' => 1],
            ['name' => 'Sağlık Ürünleri', 'merchant_id' => '5060', 'parent_id' => 16, 'level' => 1],
            
            // Sports & Outdoor
            ['name' => 'Spor & Outdoor', 'merchant_id' => '499', 'parent_id' => null, 'level' => 0],
            ['name' => 'Spor Giyim', 'merchant_id' => '5322', 'parent_id' => 20, 'level' => 1],
            ['name' => 'Fitness & Kondisyon', 'merchant_id' => '990', 'parent_id' => 20, 'level' => 1],
            ['name' => 'Outdoor & Kamp', 'merchant_id' => '988', 'parent_id' => 20, 'level' => 1],
            
            // Automotive
            ['name' => 'Otomotiv', 'merchant_id' => '888', 'parent_id' => null, 'level' => 0],
            ['name' => 'Oto Aksesuar', 'merchant_id' => '913', 'parent_id' => 24, 'level' => 1],
            ['name' => 'Oto Yedek Parça', 'merchant_id' => '6324', 'parent_id' => 24, 'level' => 1],
            
            // Books & Media
            ['name' => 'Kitap & Medya', 'merchant_id' => '784', 'parent_id' => null, 'level' => 0],
            ['name' => 'Kitaplar', 'merchant_id' => '784', 'parent_id' => 27, 'level' => 1],
            ['name' => 'Müzik & Film', 'merchant_id' => '55', 'parent_id' => 27, 'level' => 1],
            
            // Toys & Games
            ['name' => 'Oyuncak & Oyun', 'merchant_id' => '237', 'parent_id' => null, 'level' => 0],
            ['name' => 'Çocuk Oyuncakları', 'merchant_id' => '237', 'parent_id' => 30, 'level' => 1],
            ['name' => 'Video Oyunları', 'merchant_id' => '1279', 'parent_id' => 30, 'level' => 1],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['slug' => Str::slug($category['name'])], // Check for existing record by slug
                [
                    'name' => $category['name'],
                    'merchant_id' => $category['merchant_id'],
                    'parent_id' => $category['parent_id'],
                    'level' => $category['level'],
                    'is_active' => true,
                    'updated_at' => now(),
                ]
            );
        }
    }
}