<div>
    <div class="mx-auto max-w-5xl space-y-6">
        <flux:heading size="xl">{{ __('Song Title Conflicts') }}</flux:heading>

        <flux:text>{{ __('Song titles with conflicting composer or arranger attributions. Use the verify links to check attributions via Google, then resolve conflicts on the Duplicates page.') }}</flux:text>

        @if ($conflictGroups->isEmpty())
            <div class="rounded-lg border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text>{{ __('No song title conflicts found.') }}</flux:text>
            </div>
        @else
            @foreach ($conflictGroups as $songTitle => $records)
                <div wire:key="conflict-{{ md5($songTitle) }}" class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                        <flux:heading size="lg">{{ $songTitle }}</flux:heading>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                                <tr>
                                    <th class="px-4 py-2">{{ __('ID') }}</th>
                                    <th class="px-4 py-2">{{ __('Composer') }}</th>
                                    <th class="px-4 py-2">{{ __('Arranger') }}</th>
                                    <th class="px-4 py-2">{{ __('Programs') }}</th>
                                    <th class="px-4 py-2">{{ __('Verify') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach ($records as $record)
                                    <tr wire:key="record-{{ $record->id }}" class="text-zinc-700 dark:text-zinc-300">
                                        <td class="px-4 py-2 font-mono text-xs">{{ $record->id }}</td>
                                        <td class="px-4 py-2">{{ $record->composer?->artist_name ?? '—' }}</td>
                                        <td class="px-4 py-2">{{ $record->arranger?->artist_name ?? '—' }}</td>
                                        <td class="px-4 py-2">
                                            <flux:badge size="sm">{{ $record->programs_count }}</flux:badge>
                                        </td>
                                        <td class="flex gap-2 px-4 py-2">
                                            @php
                                                $searchBase = '"' . $record->song_title . '"';
                                            @endphp
                                            <a href="https://google.com/search?q={{ urlencode($searchBase . ' composer arranger choral') }}"
                                               target="_blank"
                                               rel="noopener"
                                               class="text-blue-600 hover:underline dark:text-blue-400"
                                               title="{{ __('Search for song title') }}">
                                                {{ __('Song') }}
                                            </a>
                                            @if ($record->composer)
                                                <a href="https://google.com/search?q={{ urlencode($searchBase . ' "' . $record->composer->artist_name . '"') }}"
                                                   target="_blank"
                                                   rel="noopener"
                                                   class="text-blue-600 hover:underline dark:text-blue-400"
                                                   title="{{ __('Verify composer attribution') }}">
                                                    {{ __('Composer') }}
                                                </a>
                                            @endif
                                            @if ($record->arranger)
                                                <a href="https://google.com/search?q={{ urlencode($searchBase . ' "' . $record->arranger->artist_name . '"') }}"
                                                   target="_blank"
                                                   rel="noopener"
                                                   class="text-blue-600 hover:underline dark:text-blue-400"
                                                   title="{{ __('Verify arranger attribution') }}">
                                                    {{ __('Arranger') }}
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

            <div class="mt-4">
                {{ $conflictGroups->links() }}
            </div>
        @endif

        <div>
            <flux:button variant="ghost" :href="route('founder.duplicates')" wire:navigate>
                {{ __('Go to Duplicates Page') }}
            </flux:button>
        </div>
    </div>
</div>
