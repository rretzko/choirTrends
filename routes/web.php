<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {

    Route::get('add-program/status', [App\Http\Controllers\AddProgramController::class, 'status'])->name('addProgram.status');

    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::get('artists', App\Livewire\Artists\Index::class)->name('artists.index');
    Route::get('ensembles', App\Livewire\Ensembles\Index::class)->name('ensembles.index');
    Route::get('programs', App\Livewire\Programs\Index::class)->name('programs.index');
    Route::get('schools', App\Livewire\Schools\Index::class)->name('schools.index');
    Route::get('song-titles', App\Livewire\SongTitles\Index::class)->name('song-titles.index');

    Route::get('add-program', [App\Http\Controllers\AddProgramController::class, 'index'])->name('addProgram');

    Route::post('add-program', [App\Http\Controllers\AddProgramController::class, 'store'])->name('addProgram.store');

    Route::post('add-program/confirm', [App\Http\Controllers\AddProgramController::class, 'confirm'])->name('addProgram.confirm');

    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
