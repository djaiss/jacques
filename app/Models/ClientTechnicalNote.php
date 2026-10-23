<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\ClientTechnicalNoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ClientTechnicalNote
 *
 * What a staff member records after performing a service on a client: the
 * colour formula, the developer, the processing time, the result and what to
 * do next time. It is attached to one service of one appointment, so the
 * history of formulas is never lost. The client is copied from the
 * appointment, to list the notes of a client without crossing three tables.
 *
 * The text is encrypted, and the name of the author is copied so the note
 * stays signed once the staff member is deleted.
 *
 * @property int $id
 * @property int $client_id
 * @property int $appointment_service_id
 * @property int|null $staff_id
 * @property string $author_name
 * @property string $body
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class ClientTechnicalNote extends Model
{
    /** @use HasFactory<ClientTechnicalNoteFactory> */
    use HasFactory;

    protected $table = 'client_technical_notes';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'appointment_service_id',
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
     * Get the client the service was performed on.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the service of the appointment the note records.
     *
     * @return BelongsTo<AppointmentService, $this>
     */
    public function appointmentService(): BelongsTo
    {
        return $this->belongsTo(AppointmentService::class);
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
