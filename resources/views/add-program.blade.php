<x-layouts.app :title="__('Add Program')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <flux:heading size="xl">{{ __('Add Program') }}</flux:heading>

        {{-- Display Success Message --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
                <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
            </div>
        @endif

        {{-- Display Validation Errors --}}
        @if($errors->any())
            <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
                <ul class="list-disc list-inside text-sm text-red-800 dark:text-red-200 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Section 1: Upload Concert Program --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-900 p-6">
            <flux:heading size="lg" class="mb-4">{{ __('Upload Concert Program') }}</flux:heading>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-6">
                {{ __('Upload a concert program file (PDF, image, or text) or provide URLs to concert program pages.') }}
            </p>

            <form id="program-upload-form" action="{{ route('addProgram.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                <input type="hidden" name="vapor_file_key" id="vapor-file-key" value="">
                <input type="hidden" name="vapor_file_name" id="vapor-file-name" value="">
                <input type="hidden" name="vapor_file_type" id="vapor-file-type" value="">

                {{-- File Upload Option --}}
                <div>
                    <flux:field>
                        <flux:label>{{ __('Upload File') }}</flux:label>
                        <flux:input
                            type="file"
                            id="program-file-input"
                            name="program_file"
                            accept=".pdf,.txt,.png,.jpg,.jpeg,.gif,.webp"
                        />
                        <flux:text class="text-xs">
                            {{ __('Accepted formats: PDF, TXT, PNG, JPG, JPEG, GIF, WEBP (Max: 100MB)') }}
                        </flux:text>
                    </flux:field>

                    {{-- Upload Progress Bar --}}
                    <div id="upload-progress-container" class="hidden mt-3">
                        <div class="flex items-center gap-3">
                            <div class="flex-1 bg-zinc-200 dark:bg-zinc-700 rounded-full h-2.5">
                                <div id="upload-progress-bar" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                            <span id="upload-progress-text" class="text-sm text-zinc-600 dark:text-zinc-400 min-w-[3rem]">0%</span>
                        </div>
                        <p id="upload-status-text" class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ __('Uploading file...') }}</p>
                    </div>
                </div>

                <flux:separator />

                {{-- OR divider --}}
                <div class="flex items-center gap-4">
                    <div class="flex-1 border-t border-zinc-200 dark:border-zinc-700"></div>
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('OR') }}</span>
                    <div class="flex-1 border-t border-zinc-200 dark:border-zinc-700"></div>
                </div>

                {{-- URI Input Option --}}
                <div>
                    <flux:field>
                        <flux:label>{{ __('Program URLs') }}</flux:label>
                        <flux:textarea
                            name="program_uris"
                            rows="4"
                            placeholder="https://example.com/program1&#10;https://example.com/program2"
                        />
                        <flux:text class="text-xs">
                            {{ __('Enter one URL per line for concert program pages') }}
                        </flux:text>
                    </flux:field>
                </div>

                {{-- Submit Button --}}
                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" href="{{ route('dashboard') }}">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary" id="submit-button">
                        {{ __('Process Program') }}
                    </flux:button>
                </div>
            </form>

            {{-- Vapor Upload Script --}}
            <script type="module">
                const form = document.getElementById('program-upload-form');
                const fileInput = document.getElementById('program-file-input');
                const vaporKeyInput = document.getElementById('vapor-file-key');
                const vaporNameInput = document.getElementById('vapor-file-name');
                const vaporTypeInput = document.getElementById('vapor-file-type');
                const progressContainer = document.getElementById('upload-progress-container');
                const progressBar = document.getElementById('upload-progress-bar');
                const progressText = document.getElementById('upload-progress-text');
                const statusText = document.getElementById('upload-status-text');
                const submitButton = document.getElementById('submit-button');

                // Only use Vapor.store() when running on Vapor (S3 filesystem)
                const useVaporUpload = {{ config('filesystems.default') === 's3' ? 'true' : 'false' }};

                form.addEventListener('submit', async (e) => {
                    // Check if there's a file to upload
                    if (fileInput.files.length > 0) {
                        const file = fileInput.files[0];

                        // Validate file size (100MB max)
                        const maxSize = 100 * 1024 * 1024; // 100MB in bytes
                        if (file.size > maxSize) {
                            e.preventDefault();
                            alert('{{ __("The file size must not exceed 100MB.") }}');
                            return;
                        }

                        // Only use Vapor direct upload when on Vapor with S3
                        if (useVaporUpload && window.Vapor) {
                            e.preventDefault();

                            // Show progress bar and disable submit
                            progressContainer.classList.remove('hidden');
                            submitButton.disabled = true;
                            submitButton.textContent = '{{ __("Uploading...") }}';
                            statusText.textContent = '{{ __("Uploading file to cloud storage...") }}';

                            try {
                                // Upload directly to S3 using Vapor.store
                                const response = await window.Vapor.store(file, {
                                    progress: progress => {
                                        const percent = Math.round(progress * 100);
                                        progressBar.style.width = percent + '%';
                                        progressText.textContent = percent + '%';
                                    }
                                });

                                // Store the S3 key and file info in hidden inputs
                                vaporKeyInput.value = response.key;
                                vaporNameInput.value = file.name;
                                vaporTypeInput.value = file.type;

                                // Clear the file input so it doesn't try to upload again via form
                                fileInput.value = '';

                                // Update status
                                statusText.textContent = '{{ __("Upload complete! Processing...") }}';
                                submitButton.textContent = '{{ __("Processing...") }}';

                                // Submit the form with the S3 key
                                form.submit();
                            } catch (error) {
                                console.error('Vapor upload error:', error);
                                progressContainer.classList.add('hidden');
                                submitButton.disabled = false;
                                submitButton.textContent = '{{ __("Process Program") }}';
                                alert('{{ __("Failed to upload file. Please try again.") }}\n\n' + (error.message || error));
                            }
                        }
                        // On local development, let the form submit normally with traditional upload
                    }
                    // No file selected with URLs, or local file upload - let form submit normally
                });
            </script>
        </div>

        {{-- Section 2: Confirmation Dialog --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-900 p-6">
            <flux:heading size="lg" class="mb-4">{{ __('Confirm Program Contents') }}</flux:heading>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">
                {{ __('Review and confirm the information extracted from the concert program.') }}
            </p>
            @php
                $privacyLink = '<a href="/settings/profile" class="underline hover:no-underline">'.__('privacy settings').'</a>';
            @endphp
            <p class="text-sm text-red-600 dark:text-yellow-400 mb-6 ">
                {!! __('Update your :link if you do not want your name, school name or ensemble names to be displayed to other users.', ['link' => $privacyLink]) !!}
            </p>

            @if(isset($analysis['status']) && $analysis['status'] === 'processing')
                {{-- Processing State --}}
                <div class="min-h-[300px] rounded-lg border border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800/50 flex items-center justify-center">
                    <div class="text-center text-zinc-500 dark:text-zinc-400">
                        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-zinc-300 dark:border-zinc-600 border-t-zinc-600 dark:border-t-zinc-300 mb-3"></div>
                        <p class="text-sm font-medium">{{ __('Processing your program...') }}</p>
                        <p class="text-xs mt-1">{{ __('This may take a minute for larger files') }}</p>
                    </div>
                </div>
            @elseif(isset($analysis['status']) && $analysis['status'] === 'failed')
                {{-- Failed State --}}
                <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-6">
                    <p class="text-sm text-red-800 dark:text-red-200 font-medium mb-2">{{ __('Processing Failed') }}</p>
                    <p class="text-sm text-red-700 dark:text-red-300">{{ $analysis['error'] ?? __('An unknown error occurred') }}</p>
                    <flux:button variant="ghost" href="{{ route('addProgram') }}" class="mt-4">
                        {{ __('Try Again') }}
                    </flux:button>
                </div>
            @elseif(isset($analysis['status']) && $analysis['status'] === 'completed' && isset($analysis['data']))
                {{-- Display Extracted Data --}}
                <form action="{{ route('addProgram.confirm') }}" method="POST" class="space-y-4">
                    @csrf
                    @php
                        $data = $analysis['data'];
                    @endphp

                    <flux:field>
                        <flux:label>{{ __('Event Name') }}</flux:label>
                        <flux:input
                            type="text"
                            name="event_name"
                            value="{{ old('event_name', $data['event_name'] ?? '') }}"
                            placeholder="{{ __('No event name found') }}"
                            required
                        />
                        @error('event_name')
                            <flux:text class="text-xs text-red-500">{{ $message }}</flux:text>
                        @enderror
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Event Date') }}</flux:label>
                        <flux:input
                            type="date"
                            name="event_date"
                            value="{{ old('event_date', $data['event_date'] ?? '') }}"
                            placeholder="{{ __('No date found') }}"
                            required
                        />
                        @error('event_date')
                            <flux:text class="text-xs text-red-500">{{ $message }}</flux:text>
                        @enderror
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('School Name: Please use the full name!') }}</flux:label>
                        <flux:input
                            type="text"
                            name="school_name"
                            value="{{ old('school_name', $data['school_name'] ?? '') }}"
                            placeholder="{{ __('No school name found') }}"
                            required
                        />
                        @error('school_name')
                            <flux:text class="text-xs text-red-500">{{ $message }}</flux:text>
                        @enderror
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Director Name') }}</flux:label>
                        <flux:input
                            type="text"
                            name="director_name"
                            value="{{ old('director_name', $data['director_name'] ?? auth()->user()->name) }}"
                            placeholder="{{ __('No director name found') }}"
                        />
                        @error('director_name')
                            <flux:text class="text-xs text-red-500">{{ $message }}</flux:text>
                        @enderror
                    </flux:field>

                    {{-- Ensembles and Songs Section --}}
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <flux:heading size="md">{{ __('Ensembles & Repertoire') }}</flux:heading>
                            <flux:button
                                variant="ghost"
                                type="button"
                                onclick="addEnsemble()"
                            >
                                {{ __('+ Add Ensemble') }}
                            </flux:button>
                        </div>

                        <div id="ensembles-container" class="space-y-6">
                            @forelse($data['ensembles'] ?? [] as $ensembleIndex => $ensemble)
                                <div class="ensemble-group rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 space-y-4" data-ensemble-index="{{ $ensembleIndex }}">
                                    {{-- Ensemble Header --}}
                                    <div class="flex gap-3 items-start">
                                        <div class="flex-1">
                                            <flux:field>
                                                <flux:label>{{ __('Ensemble Name') }}</flux:label>
                                                <flux:input
                                                    type="text"
                                                    name="ensembles[{{ $ensembleIndex }}][name]"
                                                    value="{{ $ensemble['name'] ?? '' }}"
                                                    placeholder="{{ __('Enter ensemble name') }}"
                                                />
                                            </flux:field>
                                        </div>
                                        <flux:button
                                            variant="ghost"
                                            type="button"
                                            onclick="this.closest('.ensemble-group').remove()"
                                            class="mt-7"
                                        >
                                            {{ __('Remove Ensemble') }}
                                        </flux:button>
                                    </div>

                                    {{-- Songs for this Ensemble --}}
                                    <div class="ml-4 space-y-3">
                                        <div class="flex items-center justify-between lg:w-1/2">
                                            <flux:text class="text-sm font-medium">{{ __('Songs') }}</flux:text>
                                            <flux:button
                                                variant="ghost"
                                                size="sm"
                                                type="button"
                                                onclick="addSong(this.closest('.ensemble-group'))"
                                            >
                                                {{ __('+ Add Song') }}
                                            </flux:button>
                                        </div>

                                        <div class="songs-container space-y-3">
                                            @forelse($ensemble['songs'] ?? [] as $songIndex => $song)
                                                <div class="song-item rounded border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800/50 p-3 space-y-2 lg:w-1/2">
                                                    <flux:field>
                                                        <flux:label class="text-xs">{{ __('Title') }}</flux:label>
                                                        <flux:input
                                                            type="text"
                                                            name="ensembles[{{ $ensembleIndex }}][songs][{{ $songIndex }}][title]"
                                                            value="{{ $song['title'] ?? '' }}"
                                                            placeholder="{{ __('Song title') }}"
                                                        />
                                                    </flux:field>
                                                    <flux:field>
                                                        <flux:label class="text-xs">{{ __('Composer') }}</flux:label>
                                                        <flux:input
                                                            type="text"
                                                            name="ensembles[{{ $ensembleIndex }}][songs][{{ $songIndex }}][composer]"
                                                            value="{{ $song['composer'] ?? '' }}"
                                                            placeholder="{{ __('Composer name') }}"
                                                        />
                                                    </flux:field>
                                                    <flux:field>
                                                        <flux:label class="text-xs">{{ __('Arranger') }}</flux:label>
                                                        <flux:input
                                                            type="text"
                                                            name="ensembles[{{ $ensembleIndex }}][songs][{{ $songIndex }}][arranger]"
                                                            value="{{ $song['arranger'] ?? '' }}"
                                                            placeholder="{{ __('Arranger name (optional)') }}"
                                                        />
                                                    </flux:field>
                                                    <flux:field>
                                                        <flux:label class="text-xs">{{ __('Program Notes') }}</flux:label>
                                                        <flux:input
                                                            type="text"
                                                            name="ensembles[{{ $ensembleIndex }}][songs][{{ $songIndex }}][notes]"
                                                            value="{{ $song['notes'] ?? '' }}"
                                                            placeholder="{{ __('Notes (optional)') }}"
                                                        />
                                                    </flux:field>
                                                    <flux:button
                                                        variant="danger"
                                                        size="sm"
                                                        type="button"
                                                        onclick="this.closest('.song-item').remove()"
                                                        class="w-fit"
                                                    >
                                                        {{ __('Remove') }}
                                                    </flux:button>
                                                </div>
                                            @empty
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400 italic">{{ __('No songs found for this ensemble') }}</p>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-zinc-500 dark:text-zinc-400 text-center py-8">{{ __('No ensembles found') }}</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <flux:button variant="ghost" href="{{ route('addProgram') }}">
                            {{ __('Start Over') }}
                        </flux:button>
                        <flux:button variant="primary" type="submit">
                            {{ __('Confirm & Save') }}
                        </flux:button>
                    </div>
                </form>
            @else
                {{-- Placeholder when no data --}}
                <div class="min-h-[300px] rounded-lg border border-dashed border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800/50 flex items-center justify-center">
                    <div class="text-center text-zinc-500 dark:text-zinc-400">
                        <svg class="mx-auto h-12 w-12 mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <p class="text-sm font-medium">{{ __('Confirmation dialog will appear here') }}</p>
                        <p class="text-xs mt-1">{{ __('After processing, you\'ll review the extracted information') }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if(isset($analysis['status']) && $analysis['status'] === 'processing')
        <script>
            // Poll for status updates every 3 seconds when processing
            const pollInterval = setInterval(async () => {
                try {
                    const response = await fetch('{{ route("addProgram.status") }}');
                    const data = await response.json();

                    if (data.status === 'completed' || data.status === 'failed') {
                        // Reload the page to show results
                        window.location.reload();
                    }
                } catch (error) {
                    console.error('Error polling status:', error);
                }
            }, 3000);

            // Clean up interval when leaving page
            window.addEventListener('beforeunload', () => {
                clearInterval(pollInterval);
            });
        </script>
    @endif

    <script>
        function addEnsemble() {
            const container = document.getElementById('ensembles-container');

            // Remove "No ensembles found" message if it exists
            const emptyMessage = container.querySelector('p.text-sm');
            if (emptyMessage) {
                emptyMessage.remove();
            }

            // Get the next ensemble index
            const ensembleIndex = container.querySelectorAll('.ensemble-group').length;

            // Create new ensemble group
            const newEnsemble = document.createElement('div');
            newEnsemble.className = 'ensemble-group rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 space-y-4';
            newEnsemble.setAttribute('data-ensemble-index', ensembleIndex);
            newEnsemble.innerHTML = `
                <div class="flex gap-3 items-start">
                    <div class="flex-1">
                        <div>
                            <label class="block text-sm font-medium text-zinc-900 dark:text-zinc-100 mb-1">{{ __('Ensemble Name') }}</label>
                            <input
                                type="text"
                                name="ensembles[${ensembleIndex}][name]"
                                placeholder="{{ __('Enter ensemble name') }}"
                                class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                    </div>
                    <button
                        type="button"
                        onclick="this.closest('.ensemble-group').remove()"
                        class="mt-7 rounded-lg px-3 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700"
                    >
                        {{ __('Remove Ensemble') }}
                    </button>
                </div>
                <div class="ml-4 space-y-3">
                    <div class="flex items-center justify-between lg:w-1/2">
                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Songs') }}</span>
                        <button
                            type="button"
                            onclick="addSong(this.closest('.ensemble-group'))"
                            class="rounded-lg px-2 py-1 text-xs font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700"
                        >
                            {{ __('+ Add Song') }}
                        </button>
                    </div>
                    <div class="songs-container space-y-3">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 italic">{{ __('No songs found for this ensemble') }}</p>
                    </div>
                </div>
            `;

            container.appendChild(newEnsemble);

            // Focus the ensemble name input
            newEnsemble.querySelector('input[type="text"]').focus();
        }

        function addSong(ensembleElement) {
            const songsContainer = ensembleElement.querySelector('.songs-container');
            const ensembleIndex = ensembleElement.getAttribute('data-ensemble-index');

            // Remove "No songs found" message if it exists
            const emptyMessage = songsContainer.querySelector('p.text-xs');
            if (emptyMessage) {
                emptyMessage.remove();
            }

            // Get the next song index for this ensemble
            const songIndex = songsContainer.querySelectorAll('.song-item').length;

            // Create new song item
            const newSong = document.createElement('div');
            newSong.className = 'song-item rounded border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800/50 p-3 space-y-2 lg:w-1/2';
            newSong.innerHTML = `
                <div>
                    <label class="block text-xs font-medium text-zinc-900 dark:text-zinc-100 mb-1">{{ __('Title') }}</label>
                    <input
                        type="text"
                        name="ensembles[${ensembleIndex}][songs][${songIndex}][title]"
                        placeholder="{{ __('Song title') }}"
                        class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-900 dark:text-zinc-100 mb-1">{{ __('Composer') }}</label>
                    <input
                        type="text"
                        name="ensembles[${ensembleIndex}][songs][${songIndex}][composer]"
                        placeholder="{{ __('Composer name') }}"
                        class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-900 dark:text-zinc-100 mb-1">{{ __('Arranger') }}</label>
                    <input
                        type="text"
                        name="ensembles[${ensembleIndex}][songs][${songIndex}][arranger]"
                        placeholder="{{ __('Arranger name (optional)') }}"
                        class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-900 dark:text-zinc-100 mb-1">{{ __('Program Notes') }}</label>
                    <input
                        type="text"
                        name="ensembles[${ensembleIndex}][songs][${songIndex}][notes]"
                        placeholder="{{ __('Notes (optional)') }}"
                        class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>
                <button
                    type="button"
                    onclick="this.closest('.song-item').remove()"
                    class="w-full rounded-lg px-2 py-1 text-xs font-medium text-white bg-red-500 hover:bg-red-600"
                >
                    {{ __('Remove') }}
                </button>
            `;

            songsContainer.appendChild(newSong);

            // Focus the title input
            newSong.querySelector('input[type="text"]').focus();
        }
    </script>
</x-layouts.app>
