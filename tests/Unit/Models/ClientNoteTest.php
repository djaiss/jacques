<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\ClientNote;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;

it('belongs to a client and its author', function () {
    $note = ClientNote::factory()->create();

    expect($note->client()->exists())->toBeTrue();
    expect($note->staff()->exists())->toBeTrue();
});

it('encrypts its text', function () {
    $note = ClientNote::factory()->create(['body' => 'Allergic to ammonia']);

    $stored = DB::table('client_notes')->where('id', $note->id)->value('body');

    expect($stored)->not->toContain('ammonia');
    expect($note->refresh()->body)->toBe('Allergic to ammonia');
});

it('keeps the name of its author once the author is deleted', function () {
    $kelly = Staff::factory()->create(['first_name' => 'Kelly', 'last_name' => 'Kapoor', 'display_name' => null]);
    $michael = Client::factory()->create(['company_id' => $kelly->company_id]);
    $note = ClientNote::factory()->create(['client_id' => $michael->id, 'staff_id' => $kelly->id, 'author_name' => $kelly->name]);

    $kelly->delete();

    $note->refresh();
    expect($note->staff_id)->toBeNull();
    expect($note->author_name)->toBe('Kelly Kapoor');
});

it('lists the notes of a client newest first', function () {
    $michael = Client::factory()->create();
    $this->travelTo('2026-10-01 10:00:00');
    $older = ClientNote::factory()->create(['client_id' => $michael->id]);
    $this->travelTo('2026-10-20 10:00:00');
    $newer = ClientNote::factory()->create(['client_id' => $michael->id]);

    expect($michael->notes()->pluck('id')->all())->toBe([$newer->id, $older->id]);
});
