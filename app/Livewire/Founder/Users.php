<?php

declare(strict_types=1);

namespace App\Livewire\Founder;

use App\Models\User;
use App\Models\UserLogin;
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

    public function applySort(string $value): void
    {
        [$column, $direction] = explode(':', $value);

        if (in_array($column, ['alpha_name', 'email', 'programs_count', 'schools_count', 'last_login_at', 'created_at'])
            && in_array($direction, ['asc', 'desc'])) {
            $this->sortBy = $column;
            $this->sortDirection = $direction;
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
            ->addSelect(['last_login_at' => UserLogin::query()
                ->selectRaw('MAX(created_at)')
                ->whereColumn('user_id', 'users.id'),
            ])
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

        if (in_array($this->sortBy, ['alpha_name', 'email', 'programs_count', 'schools_count', 'last_login_at', 'created_at'])) {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

        $users = $query->get();

        return view('livewire.founder.users', [
            'users' => $users,
            'totalCount' => User::query()->count(),
        ])->layout('components.layouts.app', ['title' => __('Users')]);
    }
}
