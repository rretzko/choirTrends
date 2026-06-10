<?php

use App\Http\Controllers\AddProgramController;
use App\Http\Controllers\DigitalProgramPublicController;
use App\Http\Controllers\DigitalProgramQrController;
use App\Http\Controllers\FounderAddProgramController;
use App\Http\Controllers\QuickTipUnsubscribeController;
use App\Http\Controllers\SheetMusicController;
use App\Http\Controllers\StopImpersonationController;
use App\Http\Controllers\UserGuidePdfController;
use App\Http\Controllers\VideoController;
use App\Http\Middleware\EnsureUserIsFounder;
use App\Http\Middleware\EnsureUserIsFounderOrImpersonating;
use App\Http\Middleware\RestrictAssistantToDigitalPrograms;
use App\Livewire\Catalog\Index;
use App\Livewire\DigitalPrograms\Configure;
use App\Livewire\DigitalPrograms\GuidedWizard;
use App\Livewire\DigitalPrograms\PowerUserForm;
use App\Livewire\Ensembles\Edit;
use App\Livewire\Founder\ChangeUserPassword;
use App\Livewire\Founder\CreateProgram;
use App\Livewire\Founder\Dashboard;
use App\Livewire\Founder\Duplicates;
use App\Livewire\Founder\ImpersonateUser;
use App\Livewire\Founder\Issues;
use App\Livewire\Founder\Newsletter;
use App\Livewire\Founder\QuickTipForm;
use App\Livewire\Founder\QuickTips;
use App\Livewire\Founder\SongTitleConflicts;
use App\Livewire\Founder\UserGuideEditor;
use App\Livewire\Founder\Users;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use App\Livewire\Survey\Show;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('catalog/{token}', Index::class)->name('catalog.show');
Route::get('catalog/{token}/video/program/{program}', [VideoController::class, 'catalogProgramVideo'])->name('catalog.videos.program');
Route::get('catalog/{token}/video/song/{program}/{songTitle}', [VideoController::class, 'catalogSongVideo'])->name('catalog.videos.song');

Route::get('quick-tips/unsubscribe', QuickTipUnsubscribeController::class)->name('quick-tips.unsubscribe');

Route::get('survey/{user}', Show::class)
    ->name('survey.show')
    ->middleware('signed');

// Public digital program view (no auth — slug-based)
Route::get('p/{slug}', DigitalProgramPublicController::class)
    ->name('program.public');

// Standalone QR code SVG for large-format display (projectors, print materials)
Route::get('p/{slug}/qr.svg', DigitalProgramQrController::class)
    ->name('program.qr');

// Booklet imposition view (duplex landscape → fold in half)
Route::get('p/{slug}/booklet', [DigitalProgramPublicController::class, 'booklet'])
    ->name('program.booklet');

Route::middleware(['auth', 'verified', RestrictAssistantToDigitalPrograms::class])->group(function () {

    Route::get('videos/program/{program}', [VideoController::class, 'programVideo'])->name('videos.program');
    Route::get('videos/song/{program}/{songTitle}', [VideoController::class, 'songVideo'])->name('videos.song');

    Route::get('media/sheet-music/{file}', [SheetMusicController::class, 'show'])->name('media.sheet-music.show');

    Route::get('add-program/status', [AddProgramController::class, 'status'])->name('addProgram.status');

    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::get('artists', App\Livewire\Artists\Index::class)->name('artists.index');
    Route::get('ensembles', App\Livewire\Ensembles\Index::class)->name('ensembles.index');
    Route::get('ensembles/{ensemble}/edit', Edit::class)->name('ensembles.edit');
    Route::get('programs', App\Livewire\Programs\Index::class)->name('programs.index');
    Route::get('programs/{program}/edit', App\Livewire\Programs\Edit::class)->name('programs.edit');
    Route::get('schools', App\Livewire\Schools\Index::class)->name('schools.index');
    Route::get('song-titles', App\Livewire\SongTitles\Index::class)->name('song-titles.index');

    Route::get('user-guide/{section?}', App\Livewire\UserGuide\Index::class)->name('user-guide.index');
    Route::get('user-guide-pdf', UserGuidePdfController::class)->name('user-guide.pdf');

    Route::get('feedback', App\Livewire\Feedback\Index::class)->name('feedback.index');
    Route::redirect('feedback/create', '/feedback?tab=report');

    Route::get('quick-tips', App\Livewire\QuickTips\Index::class)->name('quick-tips.index');

    Route::view('documentation/site-guide', 'documentation.site-guide')->name('documentation.site-guide');
    Route::view('documentation/add-program-guide', 'documentation.add-program-guide')->name('documentation.add-program-guide');
    Route::view('documentation/programs-guide', 'documentation.programs-guide')->name('documentation.programs-guide');
    Route::view('documentation/composers-arrangers-guide', 'documentation.composers-arrangers-guide')->name('documentation.composers-arrangers-guide');
    Route::view('documentation/ensembles-guide', 'documentation.ensembles-guide')->name('documentation.ensembles-guide');
    Route::view('documentation/schools-guide', 'documentation.schools-guide')->name('documentation.schools-guide');
    Route::view('documentation/song-titles-guide', 'documentation.song-titles-guide')->name('documentation.song-titles-guide');
    Route::view('documentation/orientation-email', 'documentation.orientation-email')->name('documentation.orientation-email');

    // Digital Programs
    Route::prefix('digital-programs')->name('digital-programs.')->group(function () {
        Route::get('/', App\Livewire\DigitalPrograms\Index::class)->name('index');
        Route::get('create/guided', GuidedWizard::class)->name('create.guided');
        Route::get('create/pro/{digitalProgram?}', PowerUserForm::class)->name('create.pro');
        Route::get('/{digitalProgram}/configure', Configure::class)->name('configure');
    });

    Route::get('add-program', [AddProgramController::class, 'index'])->name('addProgram');

    Route::post('add-program', [AddProgramController::class, 'store'])->name('addProgram.store');

    Route::post('add-program/confirm', [AddProgramController::class, 'confirm'])->name('addProgram.confirm');
    Route::post('add-program/reset', [AddProgramController::class, 'reset'])->name('addProgram.reset');

    // Founder-only routes
    Route::middleware(EnsureUserIsFounder::class)->prefix('founder')->group(function () {
        Route::get('dashboard', Dashboard::class)->name('founder.dashboard');
        Route::get('add-program', [FounderAddProgramController::class, 'index'])->name('founder.addProgram');
        Route::post('add-program', [FounderAddProgramController::class, 'store'])->name('founder.addProgram.store');
        Route::get('add-program/status', [FounderAddProgramController::class, 'status'])->name('founder.addProgram.status');
        Route::post('add-program/confirm', [FounderAddProgramController::class, 'confirm'])->name('founder.addProgram.confirm');
        Route::post('add-program/reset', [FounderAddProgramController::class, 'reset'])->name('founder.addProgram.reset');
        Route::get('change-user-password', ChangeUserPassword::class)->name('founder.changeUserPassword');
        Route::get('impersonate', ImpersonateUser::class)->name('founder.impersonate');
        Route::get('duplicates', Duplicates::class)->name('founder.duplicates');
        Route::get('issues', Issues::class)->name('founder.issues');
        Route::get('song-title-conflicts', SongTitleConflicts::class)->name('founder.songTitleConflicts');
        Route::get('users', Users::class)->name('founder.users');
        Route::get('quick-tips', QuickTips::class)->name('founder.quickTips');
        Route::get('quick-tips/create', QuickTipForm::class)->name('founder.quickTips.create');
        Route::get('quick-tips/{quickTip}/edit', QuickTipForm::class)->name('founder.quickTips.edit');
        Route::get('newsletter', Newsletter::class)->name('founder.newsletter');
        Route::get('user-guide', UserGuideEditor::class)->name('founder.userGuide');
        Route::get('create-program', CreateProgram::class)->name('founder.createProgram');
    });

    // Stop impersonation (must be accessible while impersonating)
    Route::middleware(EnsureUserIsFounderOrImpersonating::class)
        ->post('founder/impersonate/stop', StopImpersonationController::class)
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
