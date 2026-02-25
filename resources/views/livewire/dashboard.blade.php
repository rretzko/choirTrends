<div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">

    <a href="{{ route('programs.index') }}" class="block rounded-xl border border-neutral-200 bg-white p-6 transition-colors hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800 dark:hover:bg-neutral-700">
        <div class="flex items-center gap-4">
            <div class="flex size-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900">
                <flux:icon name="document-text" class="size-6 text-green-600 dark:text-green-400" />
            </div>
            <div>
                <flux:text class="text-sm text-neutral-500 dark:text-neutral-400">Programs</flux:text>
                <flux:heading size="xl">{{ number_format($programsCount) }}</flux:heading>
            </div>
        </div>
    </a>

    <a href="{{ route('artists.index') }}" class="block rounded-xl border border-neutral-200 bg-white p-6 transition-colors hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800 dark:hover:bg-neutral-700">
        <div class="flex items-center gap-4">
            <div class="flex size-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                <flux:icon name="musical-note" class="size-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <flux:text class="text-sm text-neutral-500 dark:text-neutral-400">Composers/Arrangers</flux:text>
                <flux:heading size="xl">{{ number_format($artistsCount) }}</flux:heading>
            </div>
        </div>
    </a>

    <a href="{{ route('ensembles.index') }}" class="block rounded-xl border border-neutral-200 bg-white p-6 transition-colors hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800 dark:hover:bg-neutral-700">
        <div class="flex items-center gap-4">
            <div class="flex size-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900">
                <flux:icon name="user-group" class="size-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
                <flux:text class="text-sm text-neutral-500 dark:text-neutral-400">Ensembles</flux:text>
                <flux:heading size="xl">{{ number_format($ensemblesCount) }}</flux:heading>
            </div>
        </div>
    </a>

    <a href="{{ route('schools.index') }}" class="block rounded-xl border border-neutral-200 bg-white p-6 transition-colors hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800 dark:hover:bg-neutral-700">
        <div class="flex items-center gap-4">
            <div class="flex size-12 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900">
                <flux:icon name="academic-cap" class="size-6 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
                <flux:text class="text-sm text-neutral-500 dark:text-neutral-400">Schools</flux:text>
                <flux:heading size="xl">{{ number_format($schoolsCount) }}</flux:heading>
            </div>
        </div>
    </a>

    <a href="{{ route('song-titles.index') }}" class="block rounded-xl border border-neutral-200 bg-white p-6 transition-colors hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800 dark:hover:bg-neutral-700">
        <div class="flex items-center gap-4">
            <div class="flex size-12 items-center justify-center rounded-lg bg-rose-100 dark:bg-rose-900">
                <flux:icon name="queue-list" class="size-6 text-rose-600 dark:text-rose-400" />
            </div>
            <div>
                <flux:text class="text-sm text-neutral-500 dark:text-neutral-400">Song Titles</flux:text>
                <flux:heading size="xl">{{ number_format($songTitlesCount) }}</flux:heading>
            </div>
        </div>
    </a>

    @if (auth()->user()->isFounder())
        <a href="{{ route('founder.users') }}" class="block rounded-xl border border-neutral-200 bg-white p-6 transition-colors hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800 dark:hover:bg-neutral-700">
    @else
        <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-800">
    @endif
        <div class="flex items-center gap-4">
            <div class="flex size-12 items-center justify-center rounded-lg bg-cyan-100 dark:bg-cyan-900">
                <flux:icon name="users" class="size-6 text-cyan-600 dark:text-cyan-400" />
            </div>
            <div>
                <flux:text class="text-sm text-neutral-500 dark:text-neutral-400">Users</flux:text>
                <flux:heading size="xl">{{ number_format($usersCount) }}</flux:heading>
            </div>
        </div>
    @if (auth()->user()->isFounder())
        </a>
    @else
        </div>
    @endif

    @if (! $compliance['isExempt'])
        <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="flex items-center gap-4">
                <div class="flex size-12 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900">
                    <flux:icon name="clipboard-document-check" class="size-6 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div>
                    <flux:text class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Participation Status') }}</flux:text>
                </div>
            </div>

            <div class="mt-4 space-y-2">
                <div class="flex items-start gap-2">
                    @if ($compliance['initialUpload']['status'] === 'green')
                        <flux:icon name="check-circle" variant="mini" class="mt-0.5 size-4 text-green-500" />
                    @elseif ($compliance['initialUpload']['status'] === 'amber')
                        <flux:icon name="exclamation-circle" variant="mini" class="mt-0.5 size-4 text-amber-500" />
                    @else
                        <flux:icon name="x-circle" variant="mini" class="mt-0.5 size-4 text-red-500" />
                    @endif
                    <flux:text class="text-xs">{{ $compliance['initialUpload']['message'] }}</flux:text>
                </div>

                @foreach ($compliance['annualRequirements'] as $year => $requirement)
                    <div class="flex items-start gap-2">
                        @if ($requirement['status'] === 'green')
                            <flux:icon name="check-circle" variant="mini" class="mt-0.5 size-4 text-green-500" />
                        @else
                            <flux:icon name="x-circle" variant="mini" class="mt-0.5 size-4 text-red-500" />
                        @endif
                        <flux:text class="text-xs">{{ $requirement['message'] }}</flux:text>
                    </div>
                @endforeach

                @if ($compliance['currentYearAdvisory'])
                    <div class="flex items-start gap-2">
                        @if ($compliance['currentYearAdvisory']['status'] === 'green')
                            <flux:icon name="check-circle" variant="mini" class="mt-0.5 size-4 text-green-500" />
                        @else
                            <flux:icon name="exclamation-circle" variant="mini" class="mt-0.5 size-4 text-amber-500" />
                        @endif
                        <flux:text class="text-xs">{{ $compliance['currentYearAdvisory']['message'] }}</flux:text>
                    </div>
                @endif
            </div>
        </div>
    @endif

</div>
