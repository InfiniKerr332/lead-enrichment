<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SalesRep;

class SalesRepSeeder extends Seeder
{
    public function run(): void
    {
        $reps = [
            [
                'name' => 'John Smith',
                'email' => 'john@example.com',
                'capacity' => 50,
                'current_load' => 0,
                'specialties' => [
                    'industries' => ['Technology', 'Software', 'SaaS'],
                    'regions' => ['San Francisco', 'New York', 'Los Angeles']
                ],
                'active' => true
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah@example.com',
                'capacity' => 50,
                'current_load' => 0,
                'specialties' => [
                    'industries' => ['Finance', 'Banking', 'Financial Services'],
                    'regions' => ['New York', 'Chicago', 'Boston']
                ],
                'active' => true
            ],
            [
                'name' => 'Mike Chen',
                'email' => 'mike@example.com',
                'capacity' => 50,
                'current_load' => 0,
                'specialties' => [
                    'industries' => ['Healthcare', 'E-commerce', 'Retail'],
                    'regions' => ['Seattle', 'Portland', 'Denver']
                ],
                'active' => true
            ],
        ];

        foreach ($reps as $rep) {
            SalesRep::create($rep);
        }
    }
}