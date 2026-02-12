<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\School;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class ProfileSchools extends Component
{
    /** @var Collection<int, School> */
    public Collection $schools;

    public string $schoolName = '';

    public string $abbreviation = '';

    public string $postalCode = '';

    public string $geoState = '';

    public string $country = '';

    public ?int $editingSchoolId = null;

    public bool $isEditing = false;

    public function mount(): void
    {
        $this->loadSchools();
    }

    public function openAddModal(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->modal('school-form')->show();
    }

    public function openEditModal(int $schoolId): void
    {
        /** @var School|null $school */
        $school = Auth::user()->schools()->find($schoolId);

        if (! $school) {
            return;
        }

        $this->editingSchoolId = $school->id;
        $this->schoolName = $school->school_name;
        $this->abbreviation = $school->abbreviation ?? '';
        $this->postalCode = $school->postal_code ?? '';
        $this->geoState = $school->geo_state ?? '';
        $this->country = $school->country ?? '';
        $this->isEditing = true;

        $this->modal('school-form')->show();
    }

    public function updatedSchoolName(): void
    {
        if ($this->schoolName !== '') {
            $this->abbreviation = School::guessAbbreviation($this->schoolName);
        }
    }

    public function updatedPostalCode(): void
    {
        $zip = trim($this->postalCode);

        if (strlen($zip) < 5) {
            $this->geoState = '';
            $this->country = '';

            return;
        }

        try {
            $response = Http::timeout(5)->get("https://api.zippopotam.us/us/{$zip}");

            if ($response->successful()) {
                $data = $response->json();
                $this->geoState = $data['places'][0]['state abbreviation'] ?? '';
                $this->country = $data['country abbreviation'] ?? '';
            } else {
                $this->geoState = '';
                $this->country = '';
            }
        } catch (\Exception $e) {
            $this->geoState = '';
            $this->country = '';
        }
    }

    public function saveSchool(): void
    {
        $validated = $this->validate([
            'schoolName' => ['required', 'string', 'max:255'],
            'abbreviation' => ['nullable', 'string', 'max:20'],
            'postalCode' => ['nullable', 'string', 'max:20'],
            'geoState' => ['nullable', 'string', 'max:10'],
            'country' => ['nullable', 'string', 'max:2'],
        ]);

        $user = Auth::user();

        if ($this->isEditing && $this->editingSchoolId) {
            $school = $user->schools()->find($this->editingSchoolId);

            if ($school) {
                $school->update([
                    'school_name' => $validated['schoolName'],
                    'abbreviation' => $validated['abbreviation'],
                    'postal_code' => $validated['postalCode'],
                    'geo_state' => $validated['geoState'],
                    'country' => $validated['country'],
                ]);
            }
        } else {
            $school = School::query()
                ->where('school_name', $validated['schoolName'])
                ->where('postal_code', $validated['postalCode'])
                ->first();

            if (! $school) {
                $school = School::create([
                    'school_name' => $validated['schoolName'],
                    'abbreviation' => $validated['abbreviation'],
                    'postal_code' => $validated['postalCode'],
                    'geo_state' => $validated['geoState'],
                    'country' => $validated['country'],
                ]);
            }

            if (! $user->schools()->where('school_id', $school->id)->exists()) {
                $user->schools()->attach($school);
            }
        }

        $this->modal('school-form')->close();
        $this->resetForm();
        $this->loadSchools();
    }

    public function removeSchool(int $schoolId): void
    {
        Auth::user()->schools()->detach($schoolId);
        $this->loadSchools();
    }

    private function loadSchools(): void
    {
        /** @var Collection<int, School> $schools */
        $schools = Auth::user()->schools()->get();
        $this->schools = $schools;
    }

    private function resetForm(): void
    {
        $this->reset(['schoolName', 'abbreviation', 'postalCode', 'geoState', 'country', 'editingSchoolId', 'isEditing']);
        $this->resetValidation();
    }
}
