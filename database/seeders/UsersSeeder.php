<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Rick Retzko',
            'email' => strtolower('rick@mfrholdings.com'),
            'email_verified_at' => '2026-01-10 09:33:33',
            'password' => Hash::make('password'),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        User::create([
            'name' => 'Lisa Rotondi',
            'email' => strtolower('lrotondi@brrsd.k12.nj.us'),
            'email_verified_at' => '2026-01-10 09:33:33',
            'password' => Hash::make('password'),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }
}
