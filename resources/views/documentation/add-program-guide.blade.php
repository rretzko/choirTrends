<x-layouts.app :title="__('Documentation')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:heading size="xl">{{ __('Add Program Guide') }}</flux:heading>

        <div class="max-w-3xl space-y-8">

            <section>
                <flux:heading size="lg">{{ __('Overview') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('The Add Program page lets you submit concert programs so ChoirTrends can extract the event details, ensembles, and repertoire automatically. The process has two steps: upload your program, then review and confirm the results.') }}
                </flux:text>
            </section>

            <section>
                <flux:heading size="lg">{{ __('Step 1: Upload Your Program') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('You have two options for submitting a concert program:') }}
                </flux:text>
                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-3">
                        <flux:icon name="document-arrow-up" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Upload a File') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Take a photo or scan of your printed program and upload the file. Accepted formats: PDF, TXT, PNG, JPG, JPEG, GIF, and WEBP. Maximum file size is 20 MB.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="link" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Paste Web Addresses') }}</flux:text>
                            <flux:text class="text-sm">{{ __('If your concert program is published online, paste one web address per line into the text box. ChoirTrends will visit each page and extract the program details.') }}</flux:text>
                        </div>
                    </div>
                </div>
                <flux:text class="mt-4 text-sm">
                    {{ __('Click "Process Program" when ready. Processing may take a minute for larger files.') }}
                </flux:text>
            </section>

            <section>
                <flux:heading size="lg">{{ __('Step 2: Review & Confirm') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('After processing, ChoirTrends displays the extracted information for you to review and edit before saving. The following fields are shown:') }}
                </flux:text>
                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-3">
                        <flux:icon name="calendar" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Event Name & Date') }}</flux:text>
                            <flux:text class="text-sm">{{ __('The name and date of the concert. Both are required.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="academic-cap" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('School/Org Name') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Please use the full name. If you have previously submitted programs, ChoirTrends will suggest matching schools/orgs so your data stays consistent.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="user" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Director Name') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Defaults to your account name if none is found in the program.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="user-group" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Ensembles & Repertoire') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Each ensemble is shown with its songs. For every song you can edit the title, composer, arranger, and program notes. Use the "+ Add Ensemble" and "+ Add Song" buttons if anything was missed.') }}</flux:text>
                        </div>
                    </div>
                </div>
                <flux:text class="mt-4 text-sm">
                    {{ __('When everything looks correct, click "Confirm & Save." Use "Start Over" to discard the results and try again.') }}
                </flux:text>
            </section>

            <section>
                <flux:heading size="lg">{{ __('Tips for Best Results') }}</flux:heading>
                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-3">
                        <flux:icon name="camera" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Photo Quality') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Make sure the text is in focus, well-lit, and not cut off at the edges. Flat, straight-on shots work better than angled photos.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="document-text" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('PDF Files') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Digital PDFs (not scanned images) generally produce the most accurate results.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="shield-check" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Privacy') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Student rosters are NOT uploaded or stored. Only event, ensemble, and song information is extracted. You can control whether your name, school/org name, and ensemble names are visible to others in your privacy settings.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="bug-ant" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Something Went Wrong?') }}</flux:text>
                            <flux:text class="text-sm">{{ __('If an upload fails for any reason, use the Feedback link in the sidebar and attach the file. We\'ll look into it as quickly as possible.') }}</flux:text>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </div>
</x-layouts.app>
