<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

it('redirects to login with message when a 419 response occurs', function (): void {
    Route::get('/test-419', fn () => abort(419));

    $response = $this->get('/test-419');

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status', 'Your session has expired. Please log in again.');
});
