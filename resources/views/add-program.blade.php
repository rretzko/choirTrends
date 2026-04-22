<x-layouts.app :title="__('Add Program')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <flux:heading size="xl">{{ __('Add Program') }}</flux:heading>

        {{-- First-Time Orientation Callout --}}
        <div x-data="{ dismissed: localStorage.getItem('addProgramOrientationDismissed') === 'true' }" x-show="! dismissed" x-collapse>
            <div x-show="! dismissed" x-transition>
                <flux:callout icon="light-bulb" color="sky">
                    <flux:callout.heading>{{ __('How it works') }}</flux:callout.heading>
                    <flux:callout.text>
                        {{ __('Upload a concert program file or paste web addresses, and our AI will extract the event details, ensembles, and songs for you. Review and edit the results below before saving.') }}
                    </flux:callout.text>
                    <x-slot name="controls">
                        <flux:button icon="x-mark" variant="ghost" x-on:click="dismissed = true; localStorage.setItem('addProgramOrientationDismissed', 'true')" />
                    </x-slot>
                </flux:callout>
            </div>
        </div>

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
                            {{ __('Accepted formats: PDF, TXT, PNG, JPG, JPEG, GIF, WEBP (Max: 20MB)') }}
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

                <p class="text-sm text-amber-600 dark:text-amber-400 text-center">
                    {{ __('If the upload fails for') }} <strong>{{ __('any reason') }}</strong>{{ __(', please use the') }} <a href="{{ route('feedback.index') . '?tab=report' }}" class="underline hover:no-underline font-medium">{{ __('Feedback') }}</a> {{ __('link on the left and attach the file. We\'ll look into it as quickly as possible!') }}
                </p>
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
                const allowedExtensions = ['pdf', 'txt', 'png', 'jpg', 'jpeg', 'gif', 'webp'];

                form.addEventListener('submit', async (e) => {
                    // Check if there's a file to upload
                    if (fileInput.files.length > 0) {
                        const file = fileInput.files[0];

                        // Validate file type
                        const extension = file.name.split('.').pop().toLowerCase();
                        if (!allowedExtensions.includes(extension)) {
                            e.preventDefault();
                            alert('{{ __("This file type is not supported. Please save your document as a PDF and try again.") }}' + '\n\n' + '{{ __("Accepted formats: PDF, TXT, PNG, JPG, JPEG, GIF, WEBP") }}');
                            fileInput.value = '';
                            return;
                        }

                        // Validate file size (20MB max)
                        const maxSize = 20 * 1024 * 1024;
                        if (file.size > maxSize) {
                            e.preventDefault();
                            const fileSizeMB = (file.size / (1024 * 1024)).toFixed(1);
                            alert('{{ __("Your file is") }} ' + fileSizeMB + 'MB. {{ __("The file size must not exceed 20MB.") }}');
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
                                console.log('Starting Vapor upload...', {
                                    useVaporUpload: useVaporUpload,
                                    hasVapor: !!window.Vapor,
                                    fileName: file.name,
                                    fileSize: file.size,
                                    fileType: file.type
                                });

                                // Upload directly to S3 using Vapor.store
                                const response = await window.Vapor.store(file, {
                                    progress: progress => {
                                        const percent = Math.round(progress * 100);
                                        progressBar.style.width = percent + '%';
                                        progressText.textContent = percent + '%';
                                        console.log('Upload progress:', percent + '%');
                                    }
                                });

                                console.log('Vapor upload successful:', response);

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
                                console.error('Error details:', {
                                    message: error?.message,
                                    response: error?.response,
                                    status: error?.response?.status,
                                    data: error?.response?.data
                                });

                                progressContainer.classList.add('hidden');
                                submitButton.disabled = false;
                                submitButton.textContent = '{{ __("Process Program") }}';

                                // Build a more informative error message
                                let errorMsg = '{{ __("Failed to upload file. Please try again.") }}';
                                if (error?.response?.status === 401 || error?.response?.status === 419) {
                                    errorMsg = '{{ __("Your session has expired. Please refresh the page and try again.") }}';
                                } else if (error?.response?.status === 403) {
                                    errorMsg = '{{ __("You do not have permission to upload files. Please log in and try again.") }}';
                                } else if (error?.response?.data?.message) {
                                    errorMsg += '\n\n' + error.response.data.message;
                                } else if (error?.message) {
                                    errorMsg += '\n\n' + error.message;
                                }

                                alert(errorMsg);
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
                        <div id="processing-spinner" class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-zinc-300 dark:border-zinc-600 border-t-zinc-600 dark:border-t-zinc-300 mb-3"></div>
                        <p id="processing-message" class="text-sm font-medium">{{ __('Processing your program...') }}</p>
                        <p id="processing-submessage" class="text-xs mt-1">{{ __('This may take a minute for larger files') }}</p>
                        <form action="{{ route('addProgram.reset') }}" method="POST" class="mt-4">
                            @csrf
                            <flux:button type="submit" variant="ghost" size="sm">
                                {{ __('Start Over') }}
                            </flux:button>
                        </form>
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
                            id="school-name-input"
                            value="{{ old('school_name', $data['school_name'] ?? '') }}"
                            placeholder="{{ __('No school name found') }}"
                            required
                        />
                        @error('school_name')
                            <flux:text class="text-xs text-red-500">{{ $message }}</flux:text>
                        @enderror

                        {{-- "Did you mean" school suggestion --}}
                        <div id="school-suggestion" class="hidden mt-2 rounded-lg border border-amber-300 dark:border-amber-600 bg-amber-50 dark:bg-amber-900/20 p-3">
                            <p class="text-sm text-amber-800 dark:text-amber-200">
                                {{ __('Did you mean:') }}
                            </p>
                            <div id="school-suggestion-options" class="mt-1 flex flex-wrap gap-2"></div>
                        </div>
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
                                        <div class="flex-1 space-y-3">
                                            <flux:field>
                                                <flux:label>{{ __('Ensemble Name') }}</flux:label>
                                                <flux:input
                                                    type="text"
                                                    name="ensembles[{{ $ensembleIndex }}][name]"
                                                    value="{{ $ensemble['name'] ?? '' }}"
                                                    placeholder="{{ __('Enter ensemble name') }}"
                                                />
                                            </flux:field>
                                            <flux:field>
                                                <flux:label>{{ __('Ensemble Director') }}</flux:label>
                                                <flux:input
                                                    type="text"
                                                    name="ensembles[{{ $ensembleIndex }}][director]"
                                                    value="{{ $ensemble['director'] ?? $data['director_name'] ?? auth()->user()->name }}"
                                                    placeholder="{{ __('Director for this ensemble') }}"
                                                />
                                            </flux:field>
                                        </div>
                                        <div class="mt-7 flex items-center gap-1">
                                            <flux:button
                                                variant="ghost"
                                                type="button"
                                                onclick="moveEnsemble(this.closest('.ensemble-group'), 'up')"
                                                size="sm"
                                                icon="chevron-up"
                                                title="{{ __('Move Up') }}"
                                            />
                                            <flux:button
                                                variant="ghost"
                                                type="button"
                                                onclick="moveEnsemble(this.closest('.ensemble-group'), 'down')"
                                                size="sm"
                                                icon="chevron-down"
                                                title="{{ __('Move Down') }}"
                                            />
                                            <flux:button
                                                variant="ghost"
                                                type="button"
                                                onclick="this.closest('.ensemble-group').remove(); reindexEnsembles()"
                                            >
                                                {{ __('Remove Ensemble') }}
                                            </flux:button>
                                        </div>
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
                {{-- Instructive empty state --}}
                <div class="min-h-[300px] rounded-lg border border-dashed border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800/50 flex items-center justify-center p-6">
                    <div class="text-center text-zinc-500 dark:text-zinc-400 max-w-md">
                        <flux:icon name="document-magnifying-glass" class="mx-auto size-12 mb-4 opacity-50" />
                        <p class="text-sm font-medium mb-3">{{ __('Upload a program above to get started') }}</p>
                        <p class="text-xs mb-4">{{ __('After processing, you\'ll be able to review and edit:') }}</p>
                        <ul class="text-xs space-y-1.5 text-left inline-block">
                            <li class="flex items-center gap-2">
                                <flux:icon name="check" class="size-3.5 text-zinc-400 shrink-0" />
                                {{ __('Event name and date') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <flux:icon name="check" class="size-3.5 text-zinc-400 shrink-0" />
                                {{ __('School and director') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <flux:icon name="check" class="size-3.5 text-zinc-400 shrink-0" />
                                {{ __('Ensembles with their repertoire') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <flux:icon name="check" class="size-3.5 text-zinc-400 shrink-0" />
                                {{ __('Song titles, composers, and arrangers') }}
                            </li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if(isset($analysis['status']) && $analysis['status'] === 'processing')
        <script>
            // Poll for status updates every 3 seconds when processing
            const pollStartTime = Date.now();
            const pollTimeoutMs = 5 * 60 * 1000; // 5 minutes

            const pollInterval = setInterval(async () => {
                // Stop polling after timeout
                if (Date.now() - pollStartTime > pollTimeoutMs) {
                    clearInterval(pollInterval);
                    const msg = document.getElementById('processing-message');
                    const sub = document.getElementById('processing-submessage');
                    const spinner = document.getElementById('processing-spinner');
                    if (msg) msg.textContent = '{{ __("Processing is taking longer than expected.") }}';
                    if (sub) sub.textContent = '{{ __("You can wait or start over and try again.") }}';
                    if (spinner) spinner.classList.remove('animate-spin');
                    return;
                }

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
        function moveEnsemble(ensembleEl, direction) {
            const container = document.getElementById('ensembles-container');
            if (direction === 'up' && ensembleEl.previousElementSibling) {
                container.insertBefore(ensembleEl, ensembleEl.previousElementSibling);
            } else if (direction === 'down' && ensembleEl.nextElementSibling) {
                container.insertBefore(ensembleEl.nextElementSibling, ensembleEl);
            }
            reindexEnsembles();
        }

        function reindexEnsembles() {
            const container = document.getElementById('ensembles-container');
            container.querySelectorAll('.ensemble-group').forEach((group, eIdx) => {
                group.setAttribute('data-ensemble-index', eIdx);
                // Re-index all inputs/textareas/selects whose name starts with "ensembles["
                group.querySelectorAll('[name^="ensembles["]').forEach(input => {
                    input.name = input.name.replace(/ensembles\[\d+\]/, `ensembles[${eIdx}]`);
                });
            });
        }

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
                    <div class="flex-1 space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-zinc-900 dark:text-zinc-100 mb-1">{{ __('Ensemble Name') }}</label>
                            <input
                                type="text"
                                name="ensembles[${ensembleIndex}][name]"
                                placeholder="{{ __('Enter ensemble name') }}"
                                class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-900 dark:text-zinc-100 mb-1">{{ __('Ensemble Director') }}</label>
                            <input
                                type="text"
                                name="ensembles[${ensembleIndex}][director]"
                                value="{{ $data['director_name'] ?? auth()->user()->name }}"
                                placeholder="{{ __('Director for this ensemble') }}"
                                class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                    </div>
                    <div class="mt-7 flex items-center gap-1">
                        <button
                            type="button"
                            onclick="moveEnsemble(this.closest('.ensemble-group'), 'up')"
                            class="rounded-lg p-1.5 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700"
                            title="{{ __('Move Up') }}"
                        >
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" /></svg>
                        </button>
                        <button
                            type="button"
                            onclick="moveEnsemble(this.closest('.ensemble-group'), 'down')"
                            class="rounded-lg p-1.5 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700"
                            title="{{ __('Move Down') }}"
                        >
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                        </button>
                        <button
                            type="button"
                            onclick="this.closest('.ensemble-group').remove(); reindexEnsembles()"
                            class="rounded-lg px-3 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700"
                        >
                            {{ __('Remove Ensemble') }}
                        </button>
                    </div>
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

        // School name matching against user's existing schools
        (function () {
            const schoolInput = document.getElementById('school-name-input');
            const suggestionBox = document.getElementById('school-suggestion');
            const suggestionOptions = document.getElementById('school-suggestion-options');

            if (!schoolInput || !suggestionBox) return;

            const userSchools = @json($userSchoolsJson);

            if (!userSchools.length) return;

            function normalize(str) {
                return (str || '').trim().toLowerCase();
            }

            function checkSchoolMatch() {
                const input = normalize(schoolInput.value);
                if (!input) {
                    suggestionBox.classList.add('hidden');
                    return;
                }

                // Check for exact match on school_name (case-insensitive)
                const exactNameMatch = userSchools.find(s => normalize(s.school_name) === input);
                if (exactNameMatch) {
                    suggestionBox.classList.add('hidden');
                    return;
                }

                // Check for exact match on abbreviation — auto-fill the full school name
                const abbrMatch = userSchools.find(s => normalize(s.abbreviation) && normalize(s.abbreviation) === input);
                if (abbrMatch) {
                    schoolInput.value = abbrMatch.school_name;
                    suggestionBox.classList.add('hidden');
                    return;
                }

                // Find matches by partial abbreviation or partial name
                const matches = userSchools.filter(s => {
                    const name = normalize(s.school_name);
                    const abbr = normalize(s.abbreviation);

                    // Input contains abbreviation or abbreviation contains input
                    if (abbr && (input.includes(abbr) || abbr.includes(input))) return true;

                    // Partial name match (input is substring of name or vice versa)
                    if (name.includes(input) || input.includes(name)) return true;

                    // Word overlap: share at least one significant word (3+ chars)
                    const inputWords = input.split(/\s+/).filter(w => w.length >= 3);
                    const nameWords = name.split(/\s+/).filter(w => w.length >= 3);
                    return inputWords.some(w => nameWords.includes(w));
                });

                if (matches.length) {
                    suggestionOptions.innerHTML = '';
                    matches.forEach(school => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'rounded-md bg-amber-200 dark:bg-amber-800 px-3 py-1 text-sm font-medium text-amber-900 dark:text-amber-100 hover:bg-amber-300 dark:hover:bg-amber-700 transition-colors';
                        btn.textContent = school.school_name + (school.abbreviation ? ' (' + school.abbreviation + ')' : '');
                        btn.addEventListener('click', function () {
                            schoolInput.value = school.school_name;
                            suggestionBox.classList.add('hidden');
                        });
                        suggestionOptions.appendChild(btn);
                    });
                    suggestionBox.classList.remove('hidden');
                } else {
                    suggestionBox.classList.add('hidden');
                }
            }

            // Run on page load and on input change
            checkSchoolMatch();
            schoolInput.addEventListener('input', checkSchoolMatch);
        })();
    </script>
</x-layouts.app>
