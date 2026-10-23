<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\ClientNoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ClientNote
 *
 * A dated note a staff member writes about a client: a habit, a preference, a
 * product to avoid. The text is encrypted, since it may hold health
 * information. The name of the author is copied, so the note stays signed once
 * the staff member is deleted.
 *
 * @property int $id
 * @property int $client_id
 * @property int|null $staff_id
 * @property string $author_name
 * @property string $body
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class ClientNote extends Model
{
    /** @use HasFactory<ClientNoteFactory> */
    use HasFactory;

    protected $table = 'client_notes';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'staff_id',
        'author_name',
        'body',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'body' => 'encrypted',
        ];
    }

    /**
     * Get the client the note is about.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the staff member who wrote the note.
     *
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
