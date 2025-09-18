<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'iPhone 15 Pro',
                'description' => 'Apple iPhone 15 Pro with A17 Pro chip',
                'category_id' => 3, // Cep Telefonu & Aksesuarları
                'brand' => 'Apple',
                'model' => 'iPhone 15 Pro',
                'status' => 'published',
                'admin_approved' => true,
                'created_by' => 1,
                'price' => 999.99,
                'availability' => 'in_stock',
                'condition' => 'new',
                'sku' => 'IP15P-001',
                'is_active' => true,
            ],
            [
                'name' => 'Samsung Galaxy S24',
                'description' => 'Samsung Galaxy S24 with Snapdragon 8 Gen 3',
                'category_id' => 3, // Cep Telefonu & Aksesuarları
                'brand' => 'Samsung',
                'model' => 'Galaxy S24',
                'status' => 'published',
                'admin_approved' => true,
                'created_by' => 1,
                'price' => 899.99,
                'availability' => 'in_stock',
                'condition' => 'new',
                'sku' => 'GS24-001',
                'is_active' => true,
            ],
            [
                'name' => 'MacBook Pro 16"',
                'description' => 'Apple MacBook Pro 16" with M3 Pro chip',
                'category_id' => 2, // Bilgisayar & Tablet
                'brand' => 'Apple',
                'model' => 'MacBook Pro 16"',
                'status' => 'published',
                'admin_approved' => true,
                'created_by' => 1,
                'price' => 2499.99,
                'availability' => 'in_stock',
                'condition' => 'new',
                'sku' => 'MBP16-001',
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            DB::table('products')->updateOrInsert(
                ['sku' => $product['sku']], // Check for existing record by SKU
                [
                    'name' => $product['name'],
                    'slug' => Str::slug($product['name']),
                    'description' => $product['description'],
                    'category_id' => $product['category_id'],
                    'brand' => $product['brand'],
                    'model' => $product['model'],
                    'status' => $product['status'],
                    'admin_approved' => $product['admin_approved'],
                    'created_by' => $product['created_by'],
                    'price' => $product['price'],
                    'availability' => $product['availability'],
                    'condition' => $product['condition'],
                    'is_active' => $product['is_active'],
                    'updated_at' => now(),
                ]
            );
        }
    }
}