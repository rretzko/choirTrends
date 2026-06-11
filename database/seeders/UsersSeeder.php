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

        User::create([
            'name' => 'Grace Gardner',
            'email' => strtolower('ggardner@carteretschools.org'),
            'email_verified_at' => '2026-01-24 10:59:59',
            'password' => Hash::make('password'),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        User::create([
            'name' => 'Jason Allen',
            'email' => strtolower('jallen@ewrsd.k12.nj.us'),
            'email_verified_at' => '2026-01-24 11:18:18',
            'password' => Hash::make('password'),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        User::create([
            'name' => 'Kyle Casem',
            'email' => strtolower('kyle.casem@woodbridge.k12.nj.us'),
            'email_verified_at' => '2026-01-24 11:24:24',
            'password' => Hash::make('password'),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        User::create([
            'name' => 'Riley Aviles',
            'email' => strtolower('riley.aviles@monroe.k12.nj.us'),
            'email_verified_at' => '2026-01-24 11:56:56',
            'password' => Hash::make('password'),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }
}
