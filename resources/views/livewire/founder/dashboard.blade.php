<div>
    <div class="mx-auto max-w-5xl space-y-6">
        <flux:heading size="xl">{{ __('Founder Dashboard') }}</flux:heading>

        <flux:text>{{ __('Login activity across all users.') }}</flux:text>

        {{-- Summary cards --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:text class="text-sm">{{ __('Total Logins') }}</flux:text>
                <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($totalLogins) }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:text class="text-sm">{{ __('Unique Users') }}</flux:text>
                <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($uniqueUsers) }}</div>
            </div>
        </div>

        {{-- Breakdowns --}}
        <div class="grid gap-4 sm:grid-cols-3">
            {{-- By OS --}}
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm" class="mb-2">{{ __('By OS') }}</flux:heading>
                @forelse($byOs as $row)
                    <div class="flex items-center justify-between py-1 text-sm">
                        <span class="text-zinc-700 dark:text-zinc-300">{{ $row->os ?? __('Unknown') }}</span>
                        <flux:badge size="sm">{{ $row->total }}</flux:badge>
                    </div>
                @empty
                    <flux:text class="text-sm">{{ __('No data yet.') }}</flux:text>
                @endforelse
            </div>

            {{-- By Browser --}}
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm" class="mb-2">{{ __('By Browser') }}</flux:heading>
                @forelse($byBrowser as $row)
                    <div class="flex items-center justify-between py-1 text-sm">
                        <span class="text-zinc-700 dark:text-zinc-300">{{ $row->browser ?? __('Unknown') }}</span>
                        <flux:badge size="sm">{{ $row->total }}</flux:badge>
                    </div>
                @empty
                    <flux:text class="text-sm">{{ __('No data yet.') }}</flux:text>
                @endforelse
            </div>

            {{-- By Device --}}
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm" class="mb-2">{{ __('By Device') }}</flux:heading>
                @forelse($byDevice as $row)
                    <div class="flex items-center justify-between py-1 text-sm">
                        <span class="text-zinc-700 dark:text-zinc-300">{{ ucfirst($row->device ?? __('unknown')) }}</span>
                        <flux:badge size="sm">{{ $row->total }}</flux:badge>
                    </div>
                @empty
                    <flux:text class="text-sm">{{ __('No data yet.') }}</flux:text>
                @endforelse
            </div>
        </div>

        {{-- Program Distribution --}}
        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <div class="mb-2 flex items-center justify-between">
                <flux:heading size="sm">{{ __('Users by Program Count') }} (n={{ $this->userProgramDetails->count() }})</flux:heading>
                <flux:button variant="subtle" size="sm" wire:click="$set('showProgramModal', true)">
                    <flux:icon.information-circle class="size-5" />
                </flux:button>
            </div>
            <div class="grid grid-cols-3 gap-3 sm:grid-cols-6">
                @foreach($this->programDistribution as $bucket)
                    <div class="text-center">
                        <flux:text class="text-xs">{{ $bucket->label }} @if($bucket->label === "0")Programs @endif</flux:text>
                        <div class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $bucket->count }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Program Detail Modal --}}
        <flux:modal wire:model="showProgramModal" class="max-w-3xl">
            <flux:heading size="lg">{{ __('User Program Details') }}</flux:heading>
            <flux:text class="mb-4">{{ __('Ordered by program count and latest program update.') }}</flux:text>

            <div class="max-h-96 overflow-y-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="sticky top-0 bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-2 text-start text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('User') }}</th>
                            <th class="px-4 py-2 text-start text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Last Login') }}</th>
                            <th class="px-4 py-2 text-end text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Programs') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($this->userProgramDetails as $user)
                            <tr wire:key="prog-user-{{ $user->id }}" class="text-sm">
                                <td class="whitespace-nowrap px-4 py-2 text-zinc-900 dark:text-zinc-100">{{ $user->alpha_name }} ({{ $user->id }})</td>
                                <td class="whitespace-nowrap px-4 py-2 text-zinc-600 dark:text-zinc-400">
                                    {{ $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->diffForHumans() : __('Never') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-2 text-end text-zinc-600 dark:text-zinc-400">{{ $user->programs_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:modal>

        {{-- Recent Logins Table --}}
        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-2 text-start text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('User') }}</th>
                        <th class="px-4 py-2 text-start text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('IP') }}</th>
                        <th class="px-4 py-2 text-start text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('OS') }}</th>
                        <th class="px-4 py-2 text-start text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Browser') }}</th>
                        <th class="px-4 py-2 text-start text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Device') }}</th>
                        <th class="px-4 py-2 text-start text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Viewport') }}</th>
                        <th class="px-4 py-2 text-start text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('#') }}</th>
                        <th class="px-4 py-2 text-start text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('When') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($recentLogins as $login)
                        <tr wire:key="login-{{ $login->id }}" class="text-sm">
                            <td class="whitespace-nowrap px-4 py-2 text-zinc-900 dark:text-zinc-100">{{ $login->user?->name ?? __('Deleted') }}</td>
                            <td class="whitespace-nowrap px-4 py-2 text-zinc-600 dark:text-zinc-400">{{ $login->ip_address }}</td>
                            <td class="whitespace-nowrap px-4 py-2 text-zinc-600 dark:text-zinc-400">{{ $login->os }}</td>
                            <td class="whitespace-nowrap px-4 py-2 text-zinc-600 dark:text-zinc-400">{{ $login->browser }}</td>
                            <td class="whitespace-nowrap px-4 py-2 text-zinc-600 dark:text-zinc-400">{{ ucfirst($login->device ?? '') }}</td>
                            <td class="whitespace-nowrap px-4 py-2 text-zinc-600 dark:text-zinc-400">{{ $login->viewport }}</td>
                            <td class="whitespace-nowrap px-4 py-2 text-zinc-600 dark:text-zinc-400">{{ $login->counter }}</td>
                            <td class="whitespace-nowrap px-4 py-2 text-zinc-600 dark:text-zinc-400">{{ $login->created_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-4 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('No login records yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
