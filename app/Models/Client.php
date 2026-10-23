<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GenderEnum;
use Carbon\Carbon;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Client
 *
 * A person the company serves. The record belongs to the company, so every
 * location of the company sees the same client. The phone, in the
 * international format, identifies the client: two records of a company never
 * share one. A client needs no user account.
 *
 * An erasure request empties every personal field and sets anonymized_at. The
 * appointments stay, attached to the empty record, so the salon keeps the
 * history of its activity.
 *
 * @property int $id
 * @property int $company_id
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $phone
 * @property string|null $email
 * @property GenderEnum|null $gender
 * @property Carbon|null $birth_date
 * @property string|null $address_line_1
 * @property string|null $address_line_2
 * @property string|null $city
 * @property string|null $region
 * @property string|null $postal_code
 * @property string|null $country
 * @property int|null $preferred_staff_id
 * @property string|null $special_instructions
 * @property bool $receives_sms_reminders
 * @property bool $is_online_booking_blocked
 * @property Carbon|null $anonymized_at
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 *
 * The clients list counts these in the same query as the clients, and they
 * are null anywhere else.
 * @property-read int|null $visit_count
 * @property-read int|null $last_visit_id
 * @property-read Carbon|null $last_visit_at
 * @property-read int|null $next_appointment_id
 */
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    protected $table = 'clients';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'gender',
        'birth_date',
        'address_line_1',
        'address_line_2',
        'city',
        'region',
        'postal_code',
        'country',
        'preferred_staff_id',
        'special_instructions',
        'receives_sms_reminders',
        'is_online_booking_blocked',
        'anonymized_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => GenderEnum::class,
            'birth_date' => 'date',
            'special_instructions' => 'encrypted',
            'receives_sms_reminders' => 'boolean',
            'is_online_booking_blocked' => 'boolean',
            'anonymized_at' => 'datetime',
        ];
    }

    /**
     * Get the company the client is a client of.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the staff member the client prefers to be served by.
     *
     * @return BelongsTo<Staff, $this>
     */
    public function preferredStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'preferred_staff_id');
    }

    /**
     * Get the appointments where the client receives the services.
     *
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get the notes the team wrote about the client, newest first.
     *
     * @return HasMany<ClientNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(ClientNote::class)->latest()->orderByDesc('id');
    }

    /**
     * Get the technical notes of every service performed on the client, newest
     * first.
     *
     * @return HasMany<ClientTechnicalNote, $this>
     */
    public function technicalNotes(): HasMany
    {
        return $this->hasMany(ClientTechnicalNote::class)->latest()->orderByDesc('id');
    }

    /**
     * Get every change of the marketing consent of the client, oldest first.
     *
     * @return HasMany<ClientConsent, $this>
     */
    public function consents(): HasMany
    {
        return $this->hasMany(ClientConsent::class)->orderBy('created_at')->orderBy('id');
    }
}
