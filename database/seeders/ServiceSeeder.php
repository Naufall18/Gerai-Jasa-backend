<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $vendorServices = [
            'salon-cantika' => [
                ['name' => 'Potong Rambut', 'description' => 'Haircut pria/wanita', 'price' => 75000, 'duration_minutes' => 30],
                ['name' => 'Creambath', 'description' => 'Hair spa & creambath treatment', 'price' => 150000, 'duration_minutes' => 60],
                ['name' => 'Hair Coloring', 'description' => 'Pewarnaan rambut premium', 'price' => 350000, 'duration_minutes' => 120],
                ['name' => 'Manicure & Pedicure', 'description' => 'Perawatan kuku tangan & kaki', 'price' => 120000, 'duration_minutes' => 45],
            ],
            'klinik-sehat-sentosa' => [
                ['name' => 'Konsultasi Umum', 'description' => 'Konsultasi dokter umum', 'price' => 100000, 'duration_minutes' => 15],
                ['name' => 'Pembersihan Karang Gigi', 'description' => 'Scaling gigi profesional', 'price' => 250000, 'duration_minutes' => 30],
                ['name' => 'Facial Treatment', 'description' => 'Perawatan wajah medis', 'price' => 300000, 'duration_minutes' => 45],
            ],
            'bengkel-jaya-motor' => [
                ['name' => 'Ganti Oli', 'description' => 'Ganti oli mesin + filter', 'price' => 200000, 'duration_minutes' => 30],
                ['name' => 'Servis Berkala', 'description' => 'Service rutin 10.000 km', 'price' => 500000, 'duration_minutes' => 120],
                ['name' => 'Tune Up', 'description' => 'Tune up mesin lengkap', 'price' => 350000, 'duration_minutes' => 60],
            ],
        ];

        foreach ($vendorServices as $slug => $services) {
            $vendor = Vendor::where('slug', $slug)->first();
            if (!$vendor) continue;

            foreach ($services as $svc) {
                Service::updateOrCreate(
                    ['vendor_id' => $vendor->id, 'name' => $svc['name']],
                    array_merge($svc, ['is_active' => true, 'max_advance_days' => 30])
                );
            }
        }
    }
}