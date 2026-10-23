<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConsentChannelEnum;
use Carbon\Carbon;
use Database\Factories\ClientConsentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ClientConsent
 *
 * One change of the marketing consent of a client on one channel. A row is
 * never edited: the latest row of a channel is the current consent, and the
 * rows before it are the history the salon may have to show.
 *
 * @property int $id
 * @property int $client_id
 * @property ConsentChannelEnum $channel
 * @property bool $is_granted
 * @property int|null $staff_id
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class ClientConsent extends Model
{
    /** @use HasFactory<ClientConsentFactory> */
    use HasFactory;

    protected $table = 'client_consents';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'channel',
        'is_granted',
        'staff_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => ConsentChannelEnum::class,
            'is_granted' => 'boolean',
        ];
    }

    /**
     * Get the client whose consent changed.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the staff member who recorded the change, when the client did not
     * give it themselves.
     *
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
