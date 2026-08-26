<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class ProgramComplianceService
{
    private const INITIAL_UPLOAD_DAYS = 14;

    private const ANNUAL_REQUIRED_COUNT = 2;

    /**
     * Get the full compliance result for a user.
     *
     * @return array{
     *     canViewAll: bool,
     *     isExempt: bool,
     *     initialUpload: array{status: string, message: string, deadline: string|null},
     *     annualRequirements: array<int, array{status: string, message: string, count: int}>,
     *     currentYearAdvisory: array{status: string, message: string, count: int}|null,
     * }
     */
    public function getCompliance(User $user): array
    {
        if ($user->isFounder()) {
            return $this->exemptResult();
        }

        $initialUpload = $this->checkInitialUpload($user);
        $annualRequirements = $this->checkAnnualRequirements($user);
        $currentYearAdvisory = $this->checkCurrentYearAdvisory($user);

        $canViewAll = $initialUpload['status'] !== 'red'
            && ! collect($annualRequirements)->contains('status', 'red');

        return [
            'canViewAll' => $canViewAll,
            'isExempt' => false,
            'initialUpload' => $initialUpload,
            'annualRequirements' => $annualRequirements,
            'currentYearAdvisory' => $currentYearAdvisory,
        ];
    }

    /**
     * @return array{
     *     canViewAll: bool,
     *     isExempt: bool,
     *     initialUpload: array{status: string, message: string, deadline: string|null},
     *     annualRequirements: array<int, array{status: string, message: string, count: int}>,
     *     currentYearAdvisory: array{status: string, message: string, count: int}|null,
     * }
     */
    private function exemptResult(): array
    {
        return [
            'canViewAll' => true,
            'isExempt' => true,
            'initialUpload' => ['status' => 'green', 'message' => 'Exempt', 'deadline' => null],
            'annualRequirements' => [],
            'currentYearAdvisory' => null,
        ];
    }

    /**
     * Gate 1: User must upload at least 1 program within 14 days of account creation.
     *
     * @return array{status: string, message: string, deadline: string|null}
     */
    private function checkInitialUpload(User $user): array
    {
        $programCount = $user->programs()->count();
        $deadline = Carbon::parse($user->created_at)->addDays(self::INITIAL_UPLOAD_DAYS);

        if ($programCount >= 1) {
            return [
                'status' => 'green',
                'message' => 'You have uploaded at least one program.',
                'deadline' => $deadline->toDateString(),
            ];
        }

        if (Carbon::now()->lte($deadline)) {
            return [
                'status' => 'amber',
                'message' => 'Please upload your first program by '.$deadline->format('M j, Y').'.',
                'deadline' => $deadline->toDateString(),
            ];
        }

        return [
            'status' => 'red',
            'message' => 'Your first program was due by '.$deadline->format('M j, Y').'. Please upload a program to access community data.',
            'deadline' => $deadline->toDateString(),
        ];
    }

    /**
     * Gate 2: For each completed calendar year after registration year, user needs 2+ programs by event_date year.
     *
     * @return array<int, array{status: string, message: string, count: int}>
     */
    private function checkAnnualRequirements(User $user): array
    {
        $registrationYear = (int) Carbon::parse($user->created_at)->format('Y');
        $currentYear = (int) Carbon::now()->format('Y');
        $requirements = [];

        // Check completed years (from registration year through last completed year)
        for ($year = $registrationYear; $year < $currentYear; $year++) {
            $count = $user->programs()->whereYear('event_date', $year)->count();

            if ($count >= self::ANNUAL_REQUIRED_COUNT) {
                $requirements[$year] = [
                    'status' => 'green',
                    'message' => "You uploaded {$count} program(s) for {$year}.",
                    'count' => $count,
                ];
            } else {
                $requirements[$year] = [
                    'status' => 'red',
                    'message' => 'You need at least '.self::ANNUAL_REQUIRED_COUNT." programs for {$year}. You have {$count}.",
                    'count' => $count,
                ];
            }
        }

        return $requirements;
    }

    /**
     * Whether the user has uploaded a program within the last $months months (by upload
     * timestamp, not the concert's event_date). Used to gate unlimited AI repertoire search
     * access on the Dashboard widget — independent of the canViewAll() data-visibility gate.
     *
     * @return array{isRecent: bool, lastUploadDate: Carbon|null}
     */
    public function checkRecentUpload(User $user, int $months = 6): array
    {
        if ($user->isFounder()) {
            return ['isRecent' => true, 'lastUploadDate' => null];
        }

        $lastUploadDate = $user->programs()->latest('created_at')->value('created_at');

        if ($lastUploadDate === null) {
            return ['isRecent' => false, 'lastUploadDate' => null];
        }

        $lastUploadDate = Carbon::parse($lastUploadDate);

        return [
            'isRecent' => $lastUploadDate->gte(Carbon::now()->subMonths($months)),
            'lastUploadDate' => $lastUploadDate,
        ];
    }

    /**
     * Current year advisory: amber if < 2 programs, green if >= 2. Not enforced.
     *
     * @return array{status: string, message: string, count: int}|null
     */
    private function checkCurrentYearAdvisory(User $user): ?array
    {
        $registrationYear = (int) Carbon::parse($user->created_at)->format('Y');
        $currentYear = (int) Carbon::now()->format('Y');

        // No advisory if user registered this year
        if ($currentYear <= $registrationYear) {
            return null;
        }

        $count = $user->programs()->whereYear('event_date', $currentYear)->count();

        if ($count >= self::ANNUAL_REQUIRED_COUNT) {
            return [
                'status' => 'green',
                'message' => "You have uploaded {$count} program(s) for {$currentYear}.",
                'count' => $count,
            ];
        }

        return [
            'status' => 'amber',
            'message' => "You have {$count} of ".self::ANNUAL_REQUIRED_COUNT." recommended programs for {$currentYear}.",
            'count' => $count,
        ];
    }
}
