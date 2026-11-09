<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public Trends Privacy Threshold
    |--------------------------------------------------------------------------
    |
    | A piece, composer or arranger appears on public pages only after it has
    | been performed by at least this many distinct schools, so no single
    | program or school can be identified from the public numbers.
    |
    */

    'minimum_schools' => 3,

    /*
    |--------------------------------------------------------------------------
    | Excluded Artist Name Patterns
    |--------------------------------------------------------------------------
    |
    | Lower-case SQL LIKE patterns for placeholder "artists" (traditional,
    | folk, spiritual, anonymous) that should not be ranked as composers or
    | arrangers on public pages.
    |
    */

    'excluded_artist_patterns' => [
        'trad%',
        '%traditional%',
        'anon%',
        'unknown%',
        '%folk%',
        '%spiritual%',
        '% carol',
        '% hymn',
    ],

    'list_limit' => 25,

    'cache_minutes' => 360,

];
