<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Services\ProgramComplianceService;
use Illuminate\Support\Facades\Auth;

trait ChecksProgramCompliance
{
    /** @var array<string, mixed> */
    public array $compliance = [];

    public function bootChecksProgramCompliance(): void
    {
        $user = Auth::user();

        if ($user) {
            $this->compliance = app(ProgramComplianceService::class)->getCompliance($user);
        }
    }

    public function canViewAll(): bool
    {
        return (bool) ($this->compliance['canViewAll'] ?? false);
    }
}
