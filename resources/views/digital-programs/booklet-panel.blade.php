@switch($panel['type'])

{{-- ── Cover ── --}}
@case('cover')
    <div class="cover-wrap">
        @if($dp->program?->school)
            <p class="cover-school">{{ $dp->program->school->school_name }}</p>
        @endif
        <h1 class="cover-title">{{ $dp->program?->event_name ?? 'Concert Program' }}</h1>
        @if($dp->program?->event_date)
            <p class="cover-date">{{ $dp->program->event_date->format('l, F j, Y') }}</p>
        @endif
        @if($dp->program?->director_name)
            <p class="cover-director">{{ $dp->program->director_name }}, Director</p>
        @endif
    </div>
@break

{{-- ── Director's Welcome ── --}}
@case('welcome')
    @if($dp->welcome_message)
        <p class="section-label">{{ __('A Note from the Director') }}</p>
        <div class="prose">{!! $dp->welcome_message !!}</div>
    @endif
@break

{{-- ── Ensemble ── --}}
@case('ensemble')
    @php
        $group       = $panel['group'];
        $ensemble    = $group['ensemble'];
        $songs       = $group['songs'];
        $ensDir      = $group['ensemble_director'];
        $ek          = $group['key'];
        $groupRosters = $rostersByEnsemble->get($ek, collect());
        $groupHonors  = $honorsByEnsemble->get($ek, collect())->sortBy('sort_order')->values();
    @endphp

    <p class="ensemble-name">{{ $ensemble?->ensemble_name ?? __('Program') }}</p>

    @if($ensDir && $ensDir !== $dp->program?->director_name)
        <p class="ensemble-meta">{{ $ensDir }}, Director</p>
    @endif
    @if($ensemble?->a_cappella)
        <p class="ensemble-meta" style="font-style:italic;">a cappella</p>
    @endif

    <ol class="song-list">
        @foreach($songs as $si => $song)
            @php
                $credits = [];
                if ($song->composer) { $credits[] = $song->composer->artist_name; }
                if ($song->arranger) { $credits[] = 'arr. ' . $song->arranger->artist_name; }
                $notes = $notesBySongId[$song->id] ?? null;
            @endphp
            <li class="song-item">
                <span class="song-number">{{ $si + 1 }}.</span>
                <span class="song-title-text">{{ $song->song_title }}</span>
                @if(! empty($credits))
                    <span class="song-credits"> — {{ implode(' / ', $credits) }}</span>
                @endif
                @if($notes)
                    <div class="song-notes">{{ $notes }}</div>
                @endif
            </li>
        @endforeach
    </ol>

    @if($groupRosters->isNotEmpty())
        <div class="roster-wrap">
            @php
                $rosterByPart  = $groupRosters->sortBy('sort_order')->groupBy(fn ($r) => $r->voice_part ?? '__none__');
                $hasVoiceParts = $rosterByPart->keys()->filter(fn ($k) => $k !== '__none__')->isNotEmpty();
            @endphp

            @foreach($rosterByPart as $part => $students)
                @if($hasVoiceParts && $part !== '__none__')
                    <p class="roster-part-heading">{{ $part }}</p>
                @endif
                <p class="roster-names">
                    @foreach($students as $si2 => $student)
                        {{ $student->student_name }}@php
                            $honorNums = $student->honors->pluck('sort_order')->sort()->values();
                        @endphp@if($honorNums->isNotEmpty())<sup>{{ $honorNums->implode(',') }}</sup>@endif@if(! $loop->last), @endif
                    @endforeach
                </p>
            @endforeach

            @if($groupHonors->isNotEmpty())
                <p class="honor-legend">
                    @foreach($groupHonors as $honor)
                        <sup>{{ $honor->sort_order }}</sup>&nbsp;{{ $honor->label }}@if(! $loop->last) &nbsp; @endif
                    @endforeach
                </p>
            @endif
        </div>
    @endif
@break

{{-- ── Acknowledgments + Sponsors ── --}}
@case('ack_sponsors')
    @if($dp->acknowledgments)
        <p class="section-label">{{ __('Acknowledgments') }}</p>
        <div class="prose" style="margin-bottom:0.2in;">{!! $dp->acknowledgments !!}</div>
    @endif
    @if($dp->sponsor_text)
        <p class="section-label">{{ __('Sponsors & Patrons') }}</p>
        <div class="prose">{!! $dp->sponsor_text !!}</div>
    @endif
@break

{{-- ── Blank (padding or back cover) ── --}}
@case('blank')
@break

@endswitch
