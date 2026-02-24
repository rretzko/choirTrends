{{-- Sticky footer: use mt-auto on this or flex-col + flex-1 on the parent container --}}
<footer class="mt-auto border-t border-zinc-200 dark:border-zinc-700 py-3 px-6">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-zinc-500 dark:text-zinc-400">
        <span>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>

        <div class="flex items-center gap-4">
            <flux:modal.trigger name="about-modal">
                <button type="button" class="hover:text-zinc-700 dark:hover:text-zinc-200 transition-colors cursor-pointer">
                    About
                </button>
            </flux:modal.trigger>

            <span>v{{ config('app.version') }}</span>

            <a href="https://mfrholdings.com" target="_blank" rel="noopener noreferrer" class="hover:text-zinc-700 dark:hover:text-zinc-200 transition-colors">
                Powered by MFR Holdings, LLC
            </a>
        </div>
    </div>
</footer>

<flux:modal name="about-modal" class="md:w-[32rem]">
    <div class="space-y-4">
        <flux:heading size="lg">Why I Built {{ config('app.name') }}</flux:heading>

        <div class="space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
            <p>{{ config('app.name') }} is my gift to the choral community.</p>

            <p>My wife, Barbara, is a highly respected choral director&mdash;NJ All-State Chorus, CJMEA Region II, All-Eastern co-Director, and conductor of multiple international choirs. When we married in 1988, I became &ldquo;choir-adjacent&rdquo; in nearly every aspect of life. Over time, that led me to create TheDirectorsRoom.com to support Barbara&rsquo;s audition registration work for NJMEA and CJMEA, and eventually to assist other regional nonprofit choral organizations with similar needs.</p>

            <p>Along the way&mdash;through long car rides listening to countless JW Pepper CDs and over many dinners with choral directors&mdash;I gained a deep appreciation for a persistent challenge: finding high-quality choral music that can realistically and successfully be brought into the classroom.</p>

            <p>For most directors, attending concerts outside their own district simply isn&rsquo;t feasible. Time, energy, and budget all work against you. Yet seeing and hearing repertoire performed live is one of the most reliable ways to discover what truly works.</p>

            <p>{{ config('app.name') }} is designed to help bridge that gap. It provides a central, easy-to-use website where directors can share their concert programs and repertoire experiences&mdash;creating a practical, community-driven resource for discovering music that has already proven successful in real classrooms and on real stages.</p>

            <p>My hope is that {{ config('app.name') }} becomes a valuable tool in your repertoire search&mdash;and that you&rsquo;ll contribute your own concert details to help others in return.</p>

            <p>
                Best,<br>
                Rick Retzko<br>
                <a href="mailto:rick@mfrholdings.com" class="text-zinc-800 dark:text-zinc-200 underline hover:no-underline">rick@mfrholdings.com</a>
            </p>

            <p class="italic">P.S. Why is this free? Because the choral community has given my family enormous value and support over the years. And honestly&mdash;don&rsquo;t you already pay more than your share?</p>
        </div>
    </div>
</flux:modal>
