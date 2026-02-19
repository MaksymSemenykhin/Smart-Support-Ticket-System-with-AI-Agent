<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Technical', 'slug' => 'technical', 'description' => 'Technical issues and bugs'],
            ['name' => 'Billing', 'slug' => 'billing', 'description' => 'Billing and payment related issues'],
            ['name' => 'General', 'slug' => 'general', 'description' => 'General inquiries'],
            ['name' => 'Account', 'slug' => 'account', 'description' => 'Account related issues'],
            ['name' => 'Feature Request', 'slug' => 'feature-request', 'description' => 'Feature requests and suggestions'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
