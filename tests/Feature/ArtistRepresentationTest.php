<?php

declare(strict_types=1);

use App\Enums\RepresentationGroup;
use App\Enums\RepresentationStatus;
use App\Models\Artist;
use App\Models\ArtistRepresentation;
use App\Models\RepresentationCategory;
use Illuminate\Database\QueryException;

it('links an artist to a representation category through artist_representations', function () {
    $artist = Artist::factory()->create();
    $category = RepresentationCategory::factory()->create([
        'group' => RepresentationGroup::Gender,
        'label' => 'Female',
        'slug' => 'female',
    ]);

    $representation = ArtistRepresentation::factory()
        ->approved()
        ->create([
            'artist_id' => $artist->id,
            'representation_category_id' => $category->id,
        ]);

    expect($artist->representations)->toHaveCount(1)
        ->and($artist->representations->first()->is($representation))->toBeTrue()
        ->and($representation->representationCategory->label)->toBe('Female')
        ->and($representation->status)->toBe(RepresentationStatus::Approved);
});

it('only surfaces approved records via approvedRepresentations', function () {
    $artist = Artist::factory()->create();

    ArtistRepresentation::factory()->create([
        'artist_id' => $artist->id,
        'status' => RepresentationStatus::PendingReview,
    ]);
    $approved = ArtistRepresentation::factory()->approved()->create([
        'artist_id' => $artist->id,
    ]);

    $result = $artist->approvedRepresentations()->get();

    expect($result)->toHaveCount(1)
        ->and($result->first()->is($approved))->toBeTrue();
});

it('prevents duplicate citations for the same artist, category, and source url', function () {
    $artist = Artist::factory()->create();
    $category = RepresentationCategory::factory()->create();

    ArtistRepresentation::factory()->create([
        'artist_id' => $artist->id,
        'representation_category_id' => $category->id,
        'source_url' => 'https://composerdiversity.com/example',
    ]);

    expect(fn () => ArtistRepresentation::factory()->create([
        'artist_id' => $artist->id,
        'representation_category_id' => $category->id,
        'source_url' => 'https://composerdiversity.com/example',
    ]))->toThrow(QueryException::class);
});
