<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('user can login with correct credentials', function () {
    $user = User::factory()->withoutTwoFactor()->create([
        'email' => 'rick@mfrholdings.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->post('/login', [
        'email' => 'rick@mfrholdings.com',
        'password' => 'password',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});

test('user with two factor enabled is redirected to two factor challenge', function () {
    $user = User::factory()->create([
        'email' => 'rick@mfrholdings.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->post('/login', [
        'email' => 'rick@mfrholdings.com',
        'password' => 'password',
    ]);

    $response->assertRedirect('/two-factor-challenge');
});

test('user cannot login with incorrect password', function () {
    User::factory()->withoutTwoFactor()->create([
        'email' => 'rick@mfrholdings.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->post('/login', [
        'email' => 'rick@mfrholdings.com',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors();
    $this->assertGuest();
});

test('user cannot login with non-existent email', function () {
    $response = $this->post('/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors();
    $this->assertGuest();
});
