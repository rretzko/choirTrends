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
    Route::get('ensembles/{ensemble}/edit', App\Livewire\Ensembles\Edit::class)->name('ensembles.edit');
    Route::get('programs', App\Livewire\Programs\Index::class)->name('programs.index');
    Route::get('programs/{program}/edit', App\Livewire\Programs\Edit::class)->name('programs.edit');
    Route::get('schools', App\Livewire\Schools\Index::class)->name('schools.index');
    Route::get('song-titles', App\Livewire\SongTitles\Index::class)->name('song-titles.index');

    Route::get('feedback/create', App\Livewire\Feedback\Create::class)->name('feedback.create');
    Route::get('feedback', App\Livewire\Feedback\Index::class)->name('feedback.index');

    Route::view('documentation/site-guide', 'documentation.site-guide')->name('documentation.site-guide');
    Route::view('documentation/orientation-email', 'documentation.orientation-email')->name('documentation.orientation-email');

    Route::get('add-program', [App\Http\Controllers\AddProgramController::class, 'index'])->name('addProgram');

    Route::post('add-program', [App\Http\Controllers\AddProgramController::class, 'store'])->name('addProgram.store');

    Route::post('add-program/confirm', [App\Http\Controllers\AddProgramController::class, 'confirm'])->name('addProgram.confirm');

    // Founder-only routes
    Route::middleware(App\Http\Middleware\EnsureUserIsFounder::class)->prefix('founder')->group(function () {
        Route::get('dashboard', App\Livewire\Founder\Dashboard::class)->name('founder.dashboard');
        Route::get('add-program', [App\Http\Controllers\FounderAddProgramController::class, 'index'])->name('founder.addProgram');
        Route::post('add-program', [App\Http\Controllers\FounderAddProgramController::class, 'store'])->name('founder.addProgram.store');
        Route::get('add-program/status', [App\Http\Controllers\FounderAddProgramController::class, 'status'])->name('founder.addProgram.status');
        Route::post('add-program/confirm', [App\Http\Controllers\FounderAddProgramController::class, 'confirm'])->name('founder.addProgram.confirm');
        Route::get('impersonate', App\Livewire\Founder\ImpersonateUser::class)->name('founder.impersonate');
        Route::get('duplicates', App\Livewire\Founder\Duplicates::class)->name('founder.duplicates');
    });

    // Stop impersonation (must be accessible while impersonating)
    Route::middleware(App\Http\Middleware\EnsureUserIsFounderOrImpersonating::class)
        ->post('founder/impersonate/stop', App\Http\Controllers\StopImpersonationController::class)
        ->name('founder.impersonate.stop');

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
