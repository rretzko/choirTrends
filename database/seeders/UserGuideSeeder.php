<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\UserGuide;
use Illuminate\Database\Seeder;

class UserGuideSeeder extends Seeder
{
    public function run(): void
    {
        $sections = $this->sections();

        foreach ($sections as $index => $section) {
            UserGuide::query()->updateOrCreate(
                ['slug' => $section['slug']],
                [
                    'title' => $section['title'],
                    'body' => $section['body'],
                    'sort_order' => $index + 1,
                    'is_published' => true,
                ],
            );
        }
    }

    /** @return array<int, array{title: string, slug: string, body: string}> */
    private function sections(): array
    {
        return [
            [
                'title' => 'Quick Start',
                'slug' => 'quick-start',
                'body' => $this->quickStart(),
            ],
            [
                'title' => 'Dashboard',
                'slug' => 'dashboard',
                'body' => $this->dashboard(),
            ],
            [
                'title' => 'Add Program',
                'slug' => 'add-program',
                'body' => $this->addProgram(),
            ],
            [
                'title' => 'Programs',
                'slug' => 'programs',
                'body' => $this->programs(),
            ],
            [
                'title' => 'Composers and Arrangers',
                'slug' => 'composers-and-arrangers',
                'body' => $this->composersAndArrangers(),
            ],
            [
                'title' => 'Ensembles',
                'slug' => 'ensembles',
                'body' => $this->ensembles(),
            ],
            [
                'title' => 'Schools/Orgs',
                'slug' => 'schools',
                'body' => $this->schools(),
            ],
            [
                'title' => 'Song Titles',
                'slug' => 'song-titles',
                'body' => $this->songTitles(),
            ],
            [
                'title' => 'Feedback',
                'slug' => 'feedback',
                'body' => $this->feedback(),
            ],
            [
                'title' => 'Quick Tips',
                'slug' => 'quick-tips',
                'body' => $this->quickTips(),
            ],
            [
                'title' => 'Your Profile and Settings',
                'slug' => 'your-profile-and-settings',
                'body' => $this->profileAndSettings(),
            ],
            [
                'title' => 'Page Conventions',
                'slug' => 'page-conventions',
                'body' => $this->pageConventions(),
            ],
        ];
    }

    private function quickStart(): string
    {
        return <<<'HTML'
<h3>Welcome to ChoirTrends!</h3>
<p>ChoirTrends helps choral directors discover what repertoire is trending across the choral community. You upload your past concert programs, and ChoirTrends reads them automatically to build a shared library of composers, arrangers, ensembles, and song titles.</p>

<div class="guide-tip guide-tip-info">
    <span class="guide-tip-icon">&#9654;&#65039;</span>
    <p>Before you dive in, you may want to <a href="/">watch the short overview video</a> on the ChoirTrends home page. It walks you through the basics in just a few minutes.</p>
</div>

<p>Here is how to get started in three simple steps:</p>

<div class="guide-step">
    <span class="guide-step-number">1</span>
    <div>
        <h4>Look Around Your Dashboard</h4>
        <p>After you sign in, you land on your <strong>Dashboard</strong>. This is your home base. Right now, everything will show zeros because you have not uploaded any programs yet. That is perfectly normal! You will also see a <strong>Setup Checklist</strong> with a few easy steps to help you get going.</p>
    </div>
</div>

<div class="guide-step">
    <span class="guide-step-number">2</span>
    <div>
        <h4>Upload Your First Program</h4>
        <p>Click <strong>"Add Program"</strong> in the left sidebar. This is where the magic happens:</p>
        <ul>
        <li>Choose a file from your computer &mdash; a <strong>photo</strong> (JPG, PNG) or a <strong>PDF</strong> of any concert program, up to 20 MB.</li>
        <li>Click <strong>"Upload"</strong> and wait a moment while ChoirTrends reads your program and pulls out the event name, date, school/org, ensembles, song titles, composers, and arrangers.</li>
        <li>Review and fix anything that does not look right &mdash; change the event name, correct a composer's spelling, or add a missing song.</li>
        <li>Click <strong>"Confirm &amp; Save"</strong> when everything looks good.</li>
        </ul>
    </div>
</div>

<div class="guide-step">
    <span class="guide-step-number">3</span>
    <div>
        <h4>Explore the Data</h4>
        <p>Go back to your Dashboard. You will see your counts start to fill in. Click any of the colored cards to jump to that section and explore what is in the system.</p>
    </div>
</div>

<div class="guide-tip guide-tip-success">
    <span class="guide-tip-icon">&#9989;</span>
    <p><strong>Don't worry if you make a mistake.</strong> You can always go back and edit your program later from the Programs page. The more programs you upload, the more useful ChoirTrends becomes &mdash; for you and for the whole choral community.</p>
</div>

<div class="guide-screenshot">Screenshot: Dashboard with stats cards</div>
HTML;
    }

    private function dashboard(): string
    {
        return <<<'HTML'
<h3>Your Home Base</h3>
<p>The Dashboard is the first thing you see when you sign in. It gives you a bird's-eye view of your activity and the ChoirTrends community.</p>

<h4>Statistics Cards</h4>
<p>At the top of the Dashboard, you will see a row of colored cards. Each one shows a count. Click any card to jump directly to that page.</p>

<div class="guide-feature">
    <span class="guide-feature-icon">&#128212;</span>
    <div><p><strong>Programs</strong> &mdash; The number of concert programs you have uploaded.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#127925;</span>
    <div><p><strong>Composers/Arrangers</strong> &mdash; The total number of composers and arrangers found in your programs.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#127932;</span>
    <div><p><strong>Ensembles</strong> &mdash; The performing groups identified in your programs.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#127979;</span>
    <div><p><strong>Schools/Orgs</strong> &mdash; The schools and organizations associated with your programs.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#127926;</span>
    <div><p><strong>Song Titles</strong> &mdash; The individual pieces of music found in your programs.</p></div>
</div>

<div class="guide-screenshot">Screenshot: Dashboard statistics cards</div>

<h4>Share Your Catalog</h4>
<p>Want to share your concert history with a colleague or your alumni? The <strong>"Share Your Catalog"</strong> card lets you create a special read-only web address. Anyone with that web address can view your programs without needing an account. You can turn sharing on or off at any time, and copy the web address to your clipboard with one click.</p>

<h4>Participation Status</h4>
<p>The <strong>Participation Status</strong> card helps you keep track of your contributions. It shows whether you have completed your initial upload and any annual participation goals.</p>

<div class="guide-tip guide-tip-info">
    <span class="guide-tip-icon">&#128161;</span>
    <p><strong>Green</strong> means you are all set. <strong>Amber</strong> or <strong>red</strong> means there is still work to do &mdash; but don't worry, it is easy to catch up!</p>
</div>

<h4>Setup Checklist</h4>
<p>If you are new, you will see a checklist of simple steps to get your account fully set up. Each item becomes checked off as you complete it. Once everything is done, the checklist disappears and you are good to go!</p>

<div class="guide-screenshot">Screenshot: Setup checklist</div>
HTML;
    }

    private function addProgram(): string
    {
        return <<<'HTML'
<h3>Uploading a Concert Program</h3>
<p>This is the heart of ChoirTrends. When you upload a concert program, the site reads it automatically and extracts all the important details: event name, date, school/org, ensembles, song titles, composers, and arrangers.</p>

<h4>What You Can Upload</h4>

<div class="guide-feature">
    <span class="guide-feature-icon">&#128247;</span>
    <div><p><strong>Photos:</strong> JPG, JPEG, PNG, GIF, or WEBP &mdash; great for phone snapshots of printed programs.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#128196;</span>
    <div><p><strong>Documents:</strong> PDF or TXT &mdash; best for multi-page programs. Digital PDFs give the most accurate results.</p></div>
</div>

<p>The maximum file size is <strong>20 MB</strong>.</p>

<div class="guide-tip guide-tip-success">
    <span class="guide-tip-icon">&#128274;</span>
    <p><strong>Privacy note:</strong> Your student rosters are <em>not</em> uploaded. ChoirTrends only extracts information about the music, ensembles, and event details.</p>
</div>

<h4>How to Upload</h4>

<div class="guide-step">
    <span class="guide-step-number">1</span>
    <div><p>Click <strong>"Add Program"</strong> in the left sidebar.</p></div>
</div>
<div class="guide-step">
    <span class="guide-step-number">2</span>
    <div><p>Click the upload area or drag and drop your file onto it.</p></div>
</div>
<div class="guide-step">
    <span class="guide-step-number">3</span>
    <div><p>You will see a progress bar while your file uploads.</p></div>
</div>
<div class="guide-step">
    <span class="guide-step-number">4</span>
    <div><p>After the upload finishes, ChoirTrends will process your program. This usually takes about 15 to 30 seconds. You will see a spinning indicator while it works.</p></div>
</div>

<div class="guide-screenshot">Screenshot: Upload area with file types shown</div>

<h4>Reviewing the Results</h4>
<p>Once processing is complete, you will see a confirmation screen with everything that was found:</p>

<div class="guide-feature">
    <span class="guide-feature-icon">&#128221;</span>
    <div><p><strong>Event Name</strong> &mdash; The name of the concert (e.g., "Spring Concert 2025"). Edit this if it is not quite right.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#128197;</span>
    <div><p><strong>Event Date</strong> &mdash; When the concert took place. Make sure this is correct.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#127979;</span>
    <div><p><strong>School/Org Name</strong> &mdash; The school, church, or organization where the concert happened. You may see a "Did you mean?" suggestion if ChoirTrends finds a close match.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#128100;</span>
    <div><p><strong>Director Name</strong> &mdash; Pre-filled with your name, but you can change it.</p></div>
</div>

<h4>Ensembles and Repertoire</h4>
<p>Below the event details, you will see each ensemble that was found, along with the songs it performed. For each song, you can review and edit the title, composer, and arranger. You can also add ensembles or songs that were missed, or remove ones that do not belong.</p>

<h4>Saving Your Program</h4>
<p>When everything looks good, click <strong>"Confirm &amp; Save"</strong> at the bottom. Your program is now in the system!</p>

<div class="guide-tip guide-tip-success">
    <span class="guide-tip-icon">&#128161;</span>
    <p><strong>Tip:</strong> Don't worry about getting everything perfect on the first try. You can always come back to the Programs page later and click the pencil icon to make changes.</p>
</div>

<div class="guide-screenshot">Screenshot: Confirmation screen with ensemble and song details</div>

<h4>Uploading by Web Address</h4>
<p>If your concert program is already posted online (for example, on your school's or organization's website), you can paste the web address instead of uploading a file. Just enter the web address in the text area provided, one per line.</p>
HTML;
    }

    private function programs(): string
    {
        return <<<'HTML'
<h3>Browsing Your Programs</h3>
<p>The Programs page shows all the concert programs you have uploaded, along with programs shared by other directors in the community.</p>

<h4>What You Will See</h4>
<p>On a computer, programs are displayed in a table with these columns:</p>

<div class="guide-feature">
    <span class="guide-feature-icon">&#127979;</span>
    <div><p><strong>School/Org</strong> &mdash; The school or organization where the concert took place.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#128196;</span>
    <div><p><strong>Program Name</strong> &mdash; The name of the event. <strong>Click it</strong> to see the full details.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#128197;</span>
    <div><p><strong>Program Date</strong> &mdash; When the concert happened.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#128100;</span>
    <div><p><strong>Director</strong> &mdash; Who directed the concert.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#9999;&#65039;</span>
    <div><p><strong>Actions</strong> &mdash; A pencil icon appears for programs you own, so you can edit them.</p></div>
</div>

<div class="guide-tip guide-tip-info">
    <span class="guide-tip-icon">&#128241;</span>
    <p>On a phone or tablet, you will see the same information arranged as cards instead of a table.</p>
</div>

<div class="guide-screenshot">Screenshot: Programs table on desktop</div>

<h4>Working with Your Programs</h4>

<div class="guide-feature">
    <span class="guide-feature-icon">&#8593;&#8595;</span>
    <div><p><strong>Sorting</strong> &mdash; Click any column header to sort the list. Click the same header again to reverse the sort order. A small arrow shows you which direction the list is sorted.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#127979;</span>
    <div><p><strong>Filtering by School/Org</strong> &mdash; Use the school/org filter drop-down at the top to narrow the list to one or more specific schools or organizations. This is especially helpful when the community has many programs.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#127991;&#65039;</span>
    <div><p><strong>Filtering by Type</strong> &mdash; Use the Type drop-down next to the school/org filter to narrow the list to a specific kind of organization: High School, Middle School, Elementary School, Community Choir, Church Choir, University Choir, Honors Choir, or Other.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#128270;</span>
    <div><p><strong>Viewing Program Details</strong> &mdash; Click a program name to open a detail view. This shows you all the ensembles and the repertoire they performed, including composers and arrangers for each piece.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#9999;&#65039;</span>
    <div><p><strong>Editing a Program</strong> &mdash; If you see a pencil icon on the far right, that means you own it and can make changes. You can update the event name, date, director, ensemble and song details, reorder ensembles, and add or remove individual songs.</p></div>
</div>

<div class="guide-screenshot">Screenshot: Program detail modal</div>
HTML;
    }

    private function composersAndArrangers(): string
    {
        return <<<'HTML'
<h3>Exploring Composers and Arrangers</h3>
<p>This page shows every composer and arranger found across all uploaded programs. It is a great way to discover who is being performed in the choral community.</p>

<h4>My vs. All</h4>
<p>At the top, you will see two toggle buttons:</p>

<div class="guide-feature">
    <span class="guide-feature-icon">&#128100;</span>
    <div><p><strong>My</strong> &mdash; Shows only the composers and arrangers from your own programs.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#127758;</span>
    <div><p><strong>All</strong> &mdash; Shows composers and arrangers from every program in the system. This button becomes available after you have uploaded at least one program.</p></div>
</div>

<p>Each button also shows a count, so you can see at a glance how many artists are in each view.</p>

<h4>Searching</h4>
<p>Use the search bar to find a specific composer or arranger by name. Just start typing, and the list narrows down as you go.</p>

<h4>Song Titles Button</h4>
<p>Next to each artist's name, you will see a numbered button. This number tells you how many songs are attributed to that artist. Click the button to open a window that shows the full list of songs, along with whether the artist is credited as the composer, arranger, or both.</p>

<div class="guide-screenshot">Screenshot: Composers list with song count buttons</div>

<div class="guide-tip guide-tip-info">
    <span class="guide-tip-icon">&#128241;</span>
    <p>On smaller screens, the list appears as cards rather than a table. Everything works the same way &mdash; tap the numbered button to see the artist's songs.</p>
</div>
HTML;
    }

    private function ensembles(): string
    {
        return <<<'HTML'
<h3>Viewing Your Ensembles</h3>
<p>The Ensembles page lists all the performing groups found in uploaded programs. Ensembles are grouped by school/org.</p>

<h4>My vs. All</h4>
<p>Just like the Composers/Arrangers page, you can switch between viewing only your ensembles or all ensembles in the community.</p>

<h4>Ensemble Details</h4>
<p>For each ensemble, you will see:</p>

<div class="guide-feature">
    <span class="guide-feature-icon">&#127979;</span>
    <div><p><strong>School/Org</strong> &mdash; Which school or organization the ensemble belongs to.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#127932;</span>
    <div><p><strong>Ensemble Name</strong> &mdash; The name of the group (e.g., "Concert Choir," "Chamber Singers").</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#127908;</span>
    <div><p><strong>Type</strong> &mdash; The voicing of the ensemble: SATB, Soprano/Alto, or Tenor/Bass.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#127925;</span>
    <div><p><strong>A Cappella</strong> &mdash; Whether the ensemble primarily performs without accompaniment.</p></div>
</div>

<h4>Editing Your Ensembles</h4>
<p>For ensembles that belong to you, you can:</p>
<ul>
<li>Change the <strong>Type</strong> by selecting from the drop-down menu.</li>
<li>Check or uncheck the <strong>A Cappella</strong> box.</li>
<li>Click the <strong>pencil icon</strong> to rename the ensemble.</li>
</ul>

<div class="guide-tip guide-tip-warning">
    <span class="guide-tip-icon">&#128274;</span>
    <p>You cannot edit ensembles that belong to other directors. You will only see edit controls for your own ensembles.</p>
</div>

<div class="guide-screenshot">Screenshot: Ensembles table with type dropdown</div>
HTML;
    }

    private function schools(): string
    {
        return <<<'HTML'
<h3>Schools/Orgs in ChoirTrends</h3>
<p>The Schools/Orgs page shows all the schools, church choirs, community choirs, and other organizations that have contributed concert programs to ChoirTrends.</p>

<h4>My vs. All</h4>
<p>Use the toggle buttons to switch between your schools/orgs and all schools/orgs in the community.</p>

<h4>Filtering by Type</h4>
<p>Use the Type drop-down to narrow the list to a specific kind of organization: High School, Middle School, Elementary School, Community Choir, Church Choir, University Choir, Honors Choir, or Other.</p>

<h4>What You Will See</h4>
<p>For each school/org, the page shows a summary of its contributions:</p>

<div class="guide-feature">
    <span class="guide-feature-icon">&#127979;</span>
    <div><p><strong>Name</strong></p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#127991;&#65039;</span>
    <div><p><strong>Type</strong> &mdash; High School, Middle School, Elementary School, Community Choir, Church Choir, University Choir, Honors Choir, or Other.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#128212;</span>
    <div><p><strong>Programs</strong> &mdash; How many concert programs have been uploaded from this school/org.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#127932;</span>
    <div><p><strong>Ensembles</strong> &mdash; How many different ensembles are associated with this school/org.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#127925;</span>
    <div><p><strong>Composers/Arrangers</strong> &mdash; How many unique composers and arrangers appear in this school/org's programs.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#127926;</span>
    <div><p><strong>Song Titles</strong> &mdash; How many individual pieces of music are in this school/org's catalog.</p></div>
</div>

<p>All columns are sortable. Click a column header to sort, and click again to reverse the order.</p>

<div class="guide-screenshot">Screenshot: Schools/Orgs table with sortable columns</div>

<div class="guide-tip guide-tip-info">
    <span class="guide-tip-icon">&#128241;</span>
    <p>On smaller screens, each school/org appears as a card showing all the same statistics in a compact layout.</p>
</div>
HTML;
    }

    private function songTitles(): string
    {
        return <<<'HTML'
<h3>Discovering Repertoire</h3>
<p>The Song Titles page is one of the most powerful features of ChoirTrends. It shows every piece of music found across all uploaded programs, helping you discover what other directors are programming.</p>

<h4>My vs. All</h4>
<p>Switch between your own repertoire and the full community library.</p>

<h4>Searching</h4>
<p>Use the search bar to find songs by title, or by composer or arranger name. The list updates as you type.</p>

<h4>What You Will See</h4>

<div class="guide-feature">
    <span class="guide-feature-icon">&#127926;</span>
    <div><p><strong>Song Title</strong> &mdash; The name of the piece.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#127925;</span>
    <div><p><strong>Composer</strong> &mdash; Who wrote the music.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#127924;</span>
    <div><p><strong>Arranger</strong> &mdash; Who arranged it (if applicable).</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#128200;</span>
    <div><p><strong>Programmed</strong> &mdash; How many times this piece has appeared across all programs. Higher numbers mean it is more widely performed.</p></div>
</div>

<div class="guide-screenshot">Screenshot: Song Titles table with programmed counts</div>

<h4>Media and YouTube</h4>
<div class="guide-feature">
    <span class="guide-feature-icon">&#9654;&#65039;</span>
    <div><p><strong>Media icon</strong> &mdash; Click to listen to or watch a performance recording if one has been attached to the song.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#128308;</span>
    <div><p><strong>YouTube icon</strong> &mdash; Click to search YouTube for that piece &mdash; a great way to hear a song before you decide to program it.</p></div>
</div>

<h4>Sorting</h4>
<p>All columns are sortable. For example, you can sort by "Programmed" count to see which pieces are the most popular across the community.</p>
HTML;
    }

    private function feedback(): string
    {
        return <<<'HTML'
<h3>Sharing Your Feedback</h3>
<p>The Feedback page is how you communicate directly with the ChoirTrends team. Whether you found a bug, have an idea for a new feature, want to say something nice, or just have a comment, this is the place.</p>

<h4>Submitting Feedback</h4>
<p>On the <strong>"Report"</strong> tab, you will see a simple form. First, pick a request type:</p>

<div class="guide-feature">
    <span class="guide-feature-icon">&#128027;</span>
    <div><p><strong>Bug</strong> &mdash; Something is not working correctly.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#128161;</span>
    <div><p><strong>Enhancement</strong> &mdash; You have an idea to make something better.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#11088;</span>
    <div><p><strong>Kudo</strong> &mdash; You want to give us a compliment (we love these!).</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#128172;</span>
    <div><p><strong>Comment</strong> &mdash; Anything else on your mind.</p></div>
</div>

<p>Then describe your feedback in the text area. Be as detailed as you like. You can optionally attach a file or screenshot to help explain what you mean.</p>

<p>Click <strong>"Submit Feedback"</strong> when you are ready. You will see a confirmation message once your feedback has been received.</p>

<div class="guide-screenshot">Screenshot: Feedback form with request type buttons</div>

<h4>Viewing Your History</h4>
<p>Click the <strong>"History"</strong> tab to see all the feedback you have submitted, along with its current status. You can filter by type (Bug, Enhancement, etc.) and by scope (your requests or all requests).</p>
HTML;
    }

    private function quickTips(): string
    {
        return <<<'HTML'
<h3>Helpful Tips and Tricks</h3>
<p>The Quick Tips page is a collection of short, helpful tips to help you get the most out of ChoirTrends. New tips are added regularly.</p>

<h4>How It Works</h4>
<p>Each tip appears as an expandable panel. Click the header to open it and read the full tip. Tips may include:</p>
<ul>
<li>A brief introduction explaining what the tip is about.</li>
<li>The tip itself with step-by-step guidance.</li>
<li>A call-to-action link that takes you directly to the relevant page so you can try it out right away.</li>
</ul>

<div class="guide-tip guide-tip-info">
    <span class="guide-tip-icon">&#128161;</span>
    <p>Use the <strong>"Next Quick Tip"</strong> button at the bottom to page through all available tips. New tips are delivered periodically, so check back often!</p>
</div>

<div class="guide-screenshot">Screenshot: Quick Tips accordion with expanded tip</div>
HTML;
    }

    private function profileAndSettings(): string
    {
        return <<<'HTML'
<h3>Managing Your Account</h3>

<div class="guide-tip guide-tip-info">
    <span class="guide-tip-icon">&#128100;</span>
    <p>To access your profile and settings, click your name or initials at the bottom of the left sidebar, then select <strong>"Profile"</strong>.</p>
</div>

<h4>Profile</h4>
<p>Here you can update your name and email address. If you change your email, you will need to verify the new address before it takes effect.</p>

<h4>Your Schools/Orgs</h4>
<p>On the Profile page, you will also see a section for managing the schools and organizations associated with your account. You can add new ones by entering the name, type (High School, Middle School, Elementary School, Community Choir, Church Choir, University Choir, Honors Choir, or Other), and location details.</p>

<h4>Privacy Settings</h4>
<p>ChoirTrends respects your privacy. On the Profile page, you will find toggles to control what information is visible to other users:</p>

<div class="guide-feature">
    <span class="guide-feature-icon">&#128065;</span>
    <div><p><strong>Don't display my name</strong> &mdash; Your name will be hidden from community views.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#128065;</span>
    <div><p><strong>Don't display my school/org</strong> &mdash; Your school/org name will be hidden.</p></div>
</div>
<div class="guide-feature">
    <span class="guide-feature-icon">&#128065;</span>
    <div><p><strong>Don't display my ensemble names</strong> &mdash; Your ensemble names will be hidden.</p></div>
</div>

<p>You can turn these on or off individually, or click <strong>"Reset"</strong> to share everything again.</p>

<div class="guide-screenshot">Screenshot: Privacy settings toggles</div>

<h4>Password</h4>
<p>Click <strong>"Password"</strong> in the settings menu to change your password. You will need to enter your current password and then your new password twice to confirm it.</p>

<h4>Appearance</h4>
<p>Click <strong>"Appearance"</strong> to switch between light mode and dark mode. You can also set it to <strong>"Auto"</strong> to follow your computer's preference. You can also toggle dark mode from the sidebar by clicking the <strong>"Dark Mode"</strong> link.</p>

<h4>Two-Factor Authentication</h4>
<p>For extra security, you can enable two-factor authentication (2FA). This adds a second step when you sign in &mdash; you will need a code from an authenticator app on your phone in addition to your password.</p>

<div class="guide-tip guide-tip-success">
    <span class="guide-tip-icon">&#128274;</span>
    <p>The settings page walks you through the 2FA setup with a QR code and gives you recovery codes in case you lose access to your phone. Keep those recovery codes somewhere safe!</p>
</div>
HTML;
    }

    private function pageConventions(): string
    {
        return <<<'HTML'
<h3>Common Features Across the Site</h3>
<p>ChoirTrends uses consistent patterns throughout the site, so once you learn them in one place, they work the same everywhere.</p>

<div class="guide-feature">
    <span class="guide-feature-icon">&#8593;&#8595;</span>
    <div>
        <p><strong>Sortable Columns</strong> &mdash; On pages that show tables, click any column header to sort. Click the same header again to reverse the order. A small arrow shows you the current sort direction.</p>
    </div>
</div>

<div class="guide-feature">
    <span class="guide-feature-icon">&#128100;&#127758;</span>
    <div>
        <p><strong>My vs. All Toggle</strong> &mdash; On pages like Composers/Arrangers, Ensembles, Schools/Orgs, and Song Titles, use the <strong>"My"</strong> and <strong>"All"</strong> buttons to switch between your data and the full community. Each button shows a count. The "All" button becomes available after you upload at least one program.</p>
    </div>
</div>

<div class="guide-feature">
    <span class="guide-feature-icon">&#128269;</span>
    <div>
        <p><strong>Search Bars</strong> &mdash; Just start typing to filter any list. Results update immediately &mdash; no need to press Enter.</p>
    </div>
</div>

<div class="guide-feature">
    <span class="guide-feature-icon">&#9999;&#65039;</span>
    <div>
        <p><strong>Edit Icons</strong> &mdash; A pencil icon on the far right means you own that item and can edit it. No pencil means it belongs to another director and is read-only.</p>
    </div>
</div>

<div class="guide-feature">
    <span class="guide-feature-icon">&#128241;</span>
    <div>
        <p><strong>Mobile-Friendly Design</strong> &mdash; ChoirTrends works on phones and tablets too. On smaller screens, tables become cards and the sidebar becomes a menu you open by tapping the menu icon at the top.</p>
    </div>
</div>

<div class="guide-feature">
    <span class="guide-feature-icon">&#127769;</span>
    <div>
        <p><strong>Dark Mode</strong> &mdash; Prefer a darker screen? Click <strong>"Dark Mode"</strong> in the sidebar, or change it in your Appearance settings. The site remembers your choice.</p>
    </div>
</div>

<div class="guide-screenshot">Screenshot: Mobile card layout example</div>
HTML;
    }
}
