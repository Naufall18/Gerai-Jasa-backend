<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Salon', 'slug' => 'salon', 'description' => 'Hair salon & beauty services', 'icon_url' => null, 'sort_order' => 1],
            ['name' => 'Klinik', 'slug' => 'klinik', 'description' => 'Health clinics & medical services', 'icon_url' => null, 'sort_order' => 2],
            ['name' => 'Bengkel', 'slug' => 'bengkel', 'description' => 'Auto repair & workshop', 'icon_url' => null, 'sort_order' => 3],
            ['name' => 'Spa', 'slug' => 'spa', 'description' => 'Spa & wellness center', 'icon_url' => null, 'sort_order' => 4],
            ['name' => 'Gym', 'slug' => 'gym', 'description' => 'Fitness & gym', 'icon_url' => null, 'sort_order' => 5],
            ['name' => 'Laundry', 'slug' => 'laundry', 'description' => 'Laundry & dry cleaning', 'icon_url' => null, 'sort_order' => 6],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['slug' => $cat['slug']],
                array_merge($cat, ['is_active' => true])
            );
        }
    }
}