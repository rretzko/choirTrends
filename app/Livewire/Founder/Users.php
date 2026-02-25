<?php

declare(strict_types=1);

namespace App\Livewire\Founder;

use App\Models\User;
use Illuminate\View\View;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

class Users extends Component
{
    public string $search = '';

    public string $sortBy = 'alpha_name';

    public string $sortDirection = 'asc';

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function resetPage(): void
    {
        // placeholder for future pagination
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        $query = User::query()
            ->withCount(['programs', 'schools'])
            ->with(['userLogins' => function ($q) {
                $q->latest('created_at')->limit(1);
            }]);

        if ($this->search !== '') {
            $searchTerm = '%'.$this->search.'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm)
                    ->orWhere('alpha_name', 'like', $searchTerm);
            });
        }

        if (in_array($this->sortBy, ['alpha_name', 'email', 'programs_count', 'schools_count', 'created_at'])) {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

        $users = $query->get();

        return view('livewire.founder.users', [
            'users' => $users,
            'totalCount' => User::query()->count(),
        ])->layout('components.layouts.app', ['title' => __('Users')]);
    }
}
