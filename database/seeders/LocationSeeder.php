<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run()
    {
        Location::create([
            'province' => 'Isabela',
            'city' => 'Echague',
            'latitude' => 16.7051,
            'longitude' => 121.6763
        ]);

        Location::create([
            'province' => 'Isabela',
            'city' => 'Santiago City',
            'latitude' => 16.7150,
            'longitude' => 121.5537
        ]);        
    }
}
