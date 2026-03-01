<div>
    <div class="mx-auto max-w-5xl space-y-4">
        <flux:heading size="xl">{{ __('Users') }}</flux:heading>

        <flux:text>{{ __('All registered users.') }} ({{ $totalCount }})</flux:text>

        {{-- Search --}}
        <div class="max-w-sm">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name or email...') }}" icon="magnifying-glass" clearable />
        </div>

        {{-- Mobile sort --}}
        <div class="md:hidden">
            <select
                wire:change="applySort($event.target.value)"
                class="rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100"
            >
                <option value="alpha_name:asc" @selected($sortBy === 'alpha_name' && $sortDirection === 'asc')>{{ __('Name (A-Z)') }}</option>
                <option value="alpha_name:desc" @selected($sortBy === 'alpha_name' && $sortDirection === 'desc')>{{ __('Name (Z-A)') }}</option>
                <option value="email:asc" @selected($sortBy === 'email' && $sortDirection === 'asc')>{{ __('Email (A-Z)') }}</option>
                <option value="email:desc" @selected($sortBy === 'email' && $sortDirection === 'desc')>{{ __('Email (Z-A)') }}</option>
                <option value="schools_count:desc" @selected($sortBy === 'schools_count' && $sortDirection === 'desc')>{{ __('Schools (Most)') }}</option>
                <option value="schools_count:asc" @selected($sortBy === 'schools_count' && $sortDirection === 'asc')>{{ __('Schools (Fewest)') }}</option>
                <option value="programs_count:desc" @selected($sortBy === 'programs_count' && $sortDirection === 'desc')>{{ __('Programs (Most)') }}</option>
                <option value="programs_count:asc" @selected($sortBy === 'programs_count' && $sortDirection === 'asc')>{{ __('Programs (Fewest)') }}</option>
                <option value="last_login_at:desc" @selected($sortBy === 'last_login_at' && $sortDirection === 'desc')>{{ __('Last Login (Latest)') }}</option>
                <option value="last_login_at:asc" @selected($sortBy === 'last_login_at' && $sortDirection === 'asc')>{{ __('Last Login (Earliest)') }}</option>
                <option value="created_at:desc" @selected($sortBy === 'created_at' && $sortDirection === 'desc')>{{ __('Registered (Newest)') }}</option>
                <option value="created_at:asc" @selected($sortBy === 'created_at' && $sortDirection === 'asc')>{{ __('Registered (Oldest)') }}</option>
            </select>
        </div>

        {{-- Mobile card layout --}}
        <div class="space-y-2 md:hidden">
            @forelse ($users as $user)
                <div wire:key="user-card-{{ $user->id }}" class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-900">
                    <div class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                        {{ $user->alpha_name }}
                    </div>
                    <div class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                        {{ $user->email }}
                    </div>
                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-neutral-500 dark:text-neutral-400">
                        <span>{{ __('Schools') }}: {{ $user->schools_count }}</span>
                        <span>{{ __('Programs') }}: {{ $user->programs_count }}</span>
                        <span>{{ __('Last Login') }}: {{ $user->userLogins->first()?->created_at?->diffForHumans() ?? __('Never') }}</span>
                        <span>{{ __('Registered') }}: {{ $user->created_at->format('M j, Y') }}</span>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-neutral-200 px-6 py-12 text-center dark:border-neutral-700">
                    <flux:icon name="users" class="mx-auto mb-3 size-10 text-neutral-300 dark:text-neutral-600" />
                    <p class="mb-1 text-sm font-medium text-neutral-700 dark:text-neutral-300">{{ __('No users found') }}</p>
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Try adjusting your search criteria.') }}</p>
                </div>
            @endforelse
        </div>

        {{-- Desktop table layout --}}
        <div class="hidden overflow-hidden rounded-xl border border-neutral-200 md:block dark:border-neutral-700">
            <table class="min-w-full table-fixed divide-y divide-neutral-200 dark:divide-neutral-700">
                <colgroup>
                    <col />
                    <col />
                    <col class="w-24" />
                    <col class="w-24" />
                    <col class="w-32" />
                    <col class="w-28" />
                </colgroup>
                <thead class="bg-neutral-50 dark:bg-neutral-800">
                    <tr>
                        <th wire:click="sort('alpha_name')" class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                            <div class="flex items-center gap-1">
                                {{ __('Name') }}
                                @if ($sortBy === 'alpha_name')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                                @else
                                    <flux:icon name="arrows-up-down" class="size-4 opacity-30" />
                                @endif
                            </div>
                        </th>
                        <th wire:click="sort('email')" class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                            <div class="flex items-center gap-1">
                                {{ __('Email') }}
                                @if ($sortBy === 'email')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                                @else
                                    <flux:icon name="arrows-up-down" class="size-4 opacity-30" />
                                @endif
                            </div>
                        </th>
                        <th wire:click="sort('schools_count')" class="cursor-pointer px-3 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                            <div class="flex items-center justify-center gap-1">
                                {{ __('Schools') }}
                                @if ($sortBy === 'schools_count')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                                @else
                                    <flux:icon name="arrows-up-down" class="size-4 opacity-30" />
                                @endif
                            </div>
                        </th>
                        <th wire:click="sort('programs_count')" class="cursor-pointer px-3 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                            <div class="flex items-center justify-center gap-1">
                                {{ __('Programs') }}
                                @if ($sortBy === 'programs_count')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                                @else
                                    <flux:icon name="arrows-up-down" class="size-4 opacity-30" />
                                @endif
                            </div>
                        </th>
                        <th wire:click="sort('last_login_at')" class="cursor-pointer px-3 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                            <div class="flex items-center justify-center gap-1">
                                {{ __('Last Login') }}
                                @if ($sortBy === 'last_login_at')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                                @else
                                    <flux:icon name="arrows-up-down" class="size-4 opacity-30" />
                                @endif
                            </div>
                        </th>
                        <th wire:click="sort('created_at')" class="cursor-pointer px-3 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                            <div class="flex items-center justify-center gap-1">
                                {{ __('Registered') }}
                                @if ($sortBy === 'created_at')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                                @else
                                    <flux:icon name="arrows-up-down" class="size-4 opacity-30" />
                                @endif
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td class="whitespace-nowrap px-6 py-2 text-sm text-neutral-900 dark:text-neutral-100">
                                {{ $user->alpha_name }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                                {{ $user->email }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-center text-sm text-neutral-900 dark:text-neutral-100">
                                {{ $user->schools_count }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-center text-sm text-neutral-900 dark:text-neutral-100">
                                {{ $user->programs_count }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-center text-sm text-neutral-500 dark:text-neutral-400">
                                {{ $user->userLogins->first()?->created_at?->diffForHumans() ?? __('Never') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-center text-sm text-neutral-500 dark:text-neutral-400">
                                {{ $user->created_at->format('M j, Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="mx-auto max-w-sm">
                                    <flux:icon name="users" class="mx-auto mb-3 size-10 text-neutral-300 dark:text-neutral-600" />
                                    <p class="mb-1 text-sm font-medium text-neutral-700 dark:text-neutral-300">{{ __('No users found') }}</p>
                                    <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Try adjusting your search criteria.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
