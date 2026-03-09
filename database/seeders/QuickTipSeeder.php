<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\QuickTipStatus;
use App\Models\QuickTip;
use Illuminate\Database\Seeder;

class QuickTipSeeder extends Seeder
{
    /**
     * @return array<int, array{header: string, introduction: string|null, tip: string}>
     */
    protected function tips(): array
    {
        return [
            // --- NEED TO KNOW (Core value) ---
            [
                'header' => 'Upload Your First Concert Program',
                'introduction' => '<p>The heart of ChoirTrends is your concert programs. Upload a photo or PDF and we do the rest.</p>',
                'tip' => '<p>Click <strong>Add Program</strong> in the sidebar, then upload a photo (JPG, PNG) or PDF of any past concert program. ChoirTrends reads the program automatically and pulls out ensemble names, song titles, composers, and arrangers.</p>'
                    .'<p>A few things to keep in mind:</p>'
                    .'<ul><li>File size limit is 20 MB.</li>'
                    .'<li>Your student rosters are <strong>not</strong> uploaded or stored &mdash; only the performance details.</li>'
                    .'<li>The more programs you upload, the richer the trends become for everyone.</li></ul>',
            ],
            [
                'header' => 'Review Your Extracted Program',
                'introduction' => '<p>After uploading, ChoirTrends uses AI to extract the details. Here is what to expect.</p>',
                'tip' => '<p>After you upload a program, ChoirTrends processes it in the background. You will see a status page while it works &mdash; this usually takes less than a minute.</p>'
                    .'<p>Once processing finishes, you will see a <strong>review screen</strong> showing the extracted event name, date, director, school, ensembles, and songs. <strong>Please check this carefully</strong> before confirming &mdash; AI extraction is good but not perfect, especially with unusual formatting or handwritten programs.</p>'
                    .'<p>You can always edit the details later from the Programs page.</p>',
            ],
            [
                'header' => 'Edit Your Program Details',
                'introduction' => null,
                'tip' => '<p>Need to fix a composer name or add a missing song? Open the <strong>Programs</strong> page from the sidebar, find your program, and click the pencil icon on the far right.</p>'
                    .'<p>From the edit screen you can:</p>'
                    .'<ul><li>Change the event name, date, and director.</li>'
                    .'<li>Add or remove ensembles.</li>'
                    .'<li>Add, edit, or remove individual songs and their composers/arrangers.</li>'
                    .'<li>Reorder songs within each ensemble.</li></ul>'
                    .'<p>You can only edit programs that you uploaded &mdash; look for the pencil icon.</p>',
            ],

            // --- IMPORTANT (Getting more value) ---
            [
                'header' => 'See What the Community Is Singing',
                'introduction' => '<p>ChoirTrends is more than your own library &mdash; it is a window into what choral directors across the community are programming.</p>',
                'tip' => '<p>On any index page (Programs, Song Titles, Composers/Arrangers, Ensembles, Schools), look for the <strong>My / All</strong> toggle near the top.</p>'
                    .'<ul><li><strong>My</strong> shows only your own data.</li>'
                    .'<li><strong>All</strong> shows everything across the entire ChoirTrends community.</li></ul>'
                    .'<p>This is a great way to discover new repertoire, see which composers are trending, or find out what other schools are performing.</p>',
            ],
            [
                'header' => 'Discover Popular Repertoire',
                'introduction' => null,
                'tip' => '<p>Open <strong>Song Titles</strong> from the sidebar and switch to <strong>All</strong> to see every song in the system. The <strong>Times Performed</strong> column shows how many programs feature each piece &mdash; click the column header to sort and find the most popular repertoire.</p>'
                    .'<p>Use the search bar to look up a specific title, composer, or arranger. It searches across all three fields at once.</p>'
                    .'<p>This is one of the most powerful features for finding new literature for your ensembles.</p>',
            ],
            [
                'header' => 'Explore Composers and Arrangers',
                'introduction' => null,
                'tip' => '<p>The <strong>Composers/Arrangers</strong> page lists every artist found across all uploaded programs. Click the numbered button under <strong>Song Titles</strong> to see a full list of that artist\'s works in the system.</p>'
                    .'<p>Try switching to <strong>All</strong> and sorting by the song count to discover prolific composers you may not have encountered before. It is a great way to build your repertoire library.</p>',
            ],
            [
                'header' => 'Filter Programs by School',
                'introduction' => null,
                'tip' => '<p>On the <strong>Programs</strong> page, notice the school drop-down filter above the table. You can select one school, several schools, or all schools to narrow down what you see.</p>'
                    .'<p>This is especially useful when you want to see what a particular school has been programming over multiple concert seasons, or when preparing for a festival and want to check if another school has performed the same piece recently.</p>',
            ],

            // --- VALUABLE (Profile & sharing) ---
            [
                'header' => 'Set Up Your School',
                'introduction' => '<p>Connecting your school to your profile makes your data easier to find and helps the community.</p>',
                'tip' => '<p>Go to <strong>Settings &gt; Profile</strong> and scroll to the Schools section. Click <strong>Add School</strong> and fill in your school name, abbreviation, and postal code.</p>'
                    .'<p>A handy shortcut: after entering a valid US ZIP code, the state and country fields fill in automatically.</p>'
                    .'<p>If your school already exists in the system (added by another director), you will be connected to the same record &mdash; no duplicates.</p>',
            ],
            [
                'header' => 'Classify Your Ensembles',
                'introduction' => null,
                'tip' => '<p>When programs are uploaded, ensembles are created automatically, but their <strong>type</strong> defaults to Unknown. You can fix this on the <strong>Ensembles</strong> page.</p>'
                    .'<p>Click the pencil icon next to any ensemble you own and set:</p>'
                    .'<ul><li><strong>Type</strong>: SATB (four-part mixed), Soprano/Alto, or Tenor/Bass.</li>'
                    .'<li><strong>A Cappella</strong>: Whether the ensemble primarily performs without accompaniment.</li></ul>'
                    .'<p>Classifying your ensembles helps the community compare repertoire across similar ensemble types.</p>',
            ],
            [
                'header' => 'Share Your Catalog',
                'introduction' => '<p>Want to share your concert history with alumni, administrators, or colleagues? The Catalog feature creates a read-only link to your programs.</p>',
                'tip' => '<p>On your <strong>Dashboard</strong>, find the <strong>Catalog</strong> section and toggle it on. A unique web address will appear that you can copy and share with anyone &mdash; they do not need a ChoirTrends account to view it.</p>'
                    .'<p>Your catalog updates automatically. Any new programs you upload will appear at the shared web address right away. Toggle it off at any time to disable the link instantly.</p>',
            ],
            [
                'header' => 'Control Your Privacy',
                'introduction' => null,
                'tip' => '<p>ChoirTrends lets you share as much or as little as you are comfortable with. Go to <strong>Settings &gt; Profile</strong> and scroll to <strong>Privacy Settings</strong>.</p>'
                    .'<p>You can independently hide:</p>'
                    .'<ul><li><strong>Your name</strong> &mdash; other users see a generic label instead of your name as director.</li>'
                    .'<li><strong>Your school</strong> &mdash; your school name is masked on all shared pages.</li>'
                    .'<li><strong>Your ensemble names</strong> &mdash; ensemble names are replaced with generic labels.</li></ul>'
                    .'<p>Privacy only affects what <em>others</em> see. Your own view is always complete.</p>',
            ],

            // --- NICE TO KNOW (Ongoing engagement) ---
            [
                'header' => 'Upload Videos and Recordings',
                'introduction' => '<p>ChoirTrends supports video and audio recordings alongside your program data.</p>',
                'tip' => '<p>Edit any program you own and look for the video upload sections. You can attach:</p>'
                    .'<ul><li>A <strong>full concert video</strong> for the entire program (MP4, MOV, AVI, or WMV up to 512 MB).</li>'
                    .'<li><strong>Individual song recordings</strong> &mdash; audio (MP3, WAV, etc.) or video &mdash; for each piece in each ensemble.</li></ul>'
                    .'<p>Each recording has its own visibility setting: <strong>Private</strong> (only you) or <strong>Public</strong> (visible in your shared catalog). You can change visibility at any time.</p>',
            ],
            [
                'header' => 'Sort Any Column',
                'introduction' => null,
                'tip' => '<p>Most tables in ChoirTrends have sortable columns. Click any column header with an arrow icon to sort by that column. Click again to reverse the direction.</p>'
                    .'<p>A few sorting tips:</p>'
                    .'<ul><li>On <strong>Song Titles</strong>, sort by <em>Times Performed</em> to find the most popular pieces.</li>'
                    .'<li>On <strong>Programs</strong>, sort by <em>Event Date</em> to see the most recent concerts first.</li>'
                    .'<li>On <strong>Composers/Arrangers</strong>, the list sorts alphabetically by last name by default.</li></ul>',
            ],
            [
                'header' => 'Send Us Feedback',
                'introduction' => null,
                'tip' => '<p>Found a bug? Have an idea for a new feature? Just want to say something nice? Click <strong>Feedback</strong> in the sidebar.</p>'
                    .'<p>Choose a type &mdash; Bug, Enhancement, Kudo, or Comment &mdash; and describe what is on your mind. You can attach a screenshot or file if it helps. We read every submission and use your feedback to improve ChoirTrends.</p>'
                    .'<p>You can check the <strong>History</strong> tab anytime to see the status of your past submissions.</p>',
            ],
            [
                'header' => 'Secure Your Account',
                'introduction' => null,
                'tip' => '<p>ChoirTrends supports <strong>two-factor authentication</strong> for extra security. Go to <strong>Settings &gt; Two-Factor Authentication</strong> to set it up.</p>'
                    .'<p>You will scan a QR code with an authenticator app (like Google Authenticator or Authy) on your phone. After that, logging in requires both your password and a 6-digit code from the app.</p>'
                    .'<p>If you cannot scan the QR code, a manual setup key is available as a fallback.</p>',
            ],
        ];
    }

    public function run(): void
    {
        if (QuickTip::count() > 0) {
            $this->command->warn('Quick tips already exist. Skipping seeder to avoid duplicates.');

            return;
        }

        foreach ($this->tips() as $index => $tipData) {
            QuickTip::create([
                'sort_order' => $index + 1,
                'header' => $tipData['header'],
                'introduction' => $tipData['introduction'],
                'tip' => $tipData['tip'],
                'footer' => null,
                'call_to_action' => null,
                'status' => QuickTipStatus::Pending,
            ]);
        }

        $this->command->info('Created '.count($this->tips()).' quick tips.');
    }
}
