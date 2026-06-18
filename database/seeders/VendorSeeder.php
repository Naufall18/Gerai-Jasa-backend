<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        $salonCategory = Category::where('slug', 'salon')->first();
        $klinikCategory = Category::where('slug', 'klinik')->first();
        $bengkelCategory = Category::where('slug', 'bengkel')->first();

        $vendors = [
            [
                'user' => ['name' => 'Salon Cantika', 'email' => 'salon.cantika@geraijasa.id', 'phone' => '+6281200000001'],
                'vendor' => ['name' => 'Salon Cantika', 'slug' => 'salon-cantika', 'description' => 'Salon kecantikan terbaik di Jakarta', 'address' => 'Jl. Sudirman No. 10, Jakarta Selatan', 'city' => 'Jakarta', 'lat' => -6.2088, 'lng' => 106.8456, 'category_id' => $salonCategory?->id, 'status' => 'active', 'commission_rate' => 10.00, 'rating_avg' => 4.50, 'rating_count' => 120, 'is_featured' => true],
            ],
            [
                'user' => ['name' => 'Klinik Sehat Sentosa', 'email' => 'klinik.sehat@geraijasa.id', 'phone' => '+6281200000002'],
                'vendor' => ['name' => 'Klinik Sehat Sentosa', 'slug' => 'klinik-sehat-sentosa', 'description' => 'Klinik kesehatan umum dan gigi', 'address' => 'Jl. Gatot Subroto No. 25, Jakarta Selatan', 'city' => 'Jakarta', 'lat' => -6.2350, 'lng' => 106.8270, 'category_id' => $klinikCategory?->id, 'status' => 'active', 'commission_rate' => 8.00, 'rating_avg' => 4.70, 'rating_count' => 85, 'is_featured' => true],
            ],
            [
                'user' => ['name' => 'Bengkel Jaya Motor', 'email' => 'bengkel.jaya@geraijasa.id', 'phone' => '+6281200000003'],
                'vendor' => ['name' => 'Bengkel Jaya Motor', 'slug' => 'bengkel-jaya-motor', 'description' => 'Bengkel mobil terpercaya sejak 2005', 'address' => 'Jl. Fatmawati No. 33, Jakarta Selatan', 'city' => 'Jakarta', 'lat' => -6.2900, 'lng' => 106.7970, 'category_id' => $bengkelCategory?->id, 'status' => 'active', 'commission_rate' => 12.00, 'rating_avg' => 4.20, 'rating_count' => 60, 'is_featured' => false],
            ],
        ];

        foreach ($vendors as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['user']['email']],
                array_merge($data['user'], [
                    'role' => 'vendor',
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                    'phone_verified_at' => now(),
                    'is_active' => true,
                ])
            );

            $vendor = Vendor::updateOrCreate(
                ['slug' => $data['vendor']['slug']],
                array_merge($data['vendor'], ['user_id' => $user->id])
            );

            // Create default schedules (Mon-Sat 09:00-17:00, Sun closed)
            for ($day = 0; $day <= 6; $day++) {
                Schedule::updateOrCreate(
                    ['vendor_id' => $vendor->id, 'day_of_week' => $day],
                    [
                        'open_time' => '09:00',
                        'close_time' => '17:00',
                        'is_closed' => $day === 0, // Sunday closed
                    ]
                );
            }
        }
    }
}