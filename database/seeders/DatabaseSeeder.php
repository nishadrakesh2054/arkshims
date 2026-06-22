<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'webdeveloper@arkshgroup.com',
            'password' => 'arksh12345',
        ]);

        if (Unit::query()->doesntExist()) {
            Unit::query()->insert([
                [
                    'name' => 'Kilogram',
                    'symbol' => 'kg',
                    'type' => 'weight',
                    'conversion_factor' => 1,
                    'is_base' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Gram',
                    'symbol' => 'gm',
                    'type' => 'weight',
                    'conversion_factor' => 0.001,
                    'is_base' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Liter',
                    'symbol' => 'L',
                    'type' => 'volume',
                    'conversion_factor' => 1,
                    'is_base' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Milliliter',
                    'symbol' => 'ml',
                    'type' => 'volume',
                    'conversion_factor' => 0.001,
                    'is_base' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Piece',
                    'symbol' => 'pcs',
                    'type' => 'count',
                    'conversion_factor' => 1,
                    'is_base' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        $this->call(ImsSampleDataSeeder::class);
    }
}
