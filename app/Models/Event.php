<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class Event extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * Bank table for this model
     * @var string
     */
    protected $table = 'events';


    /**
     * Primary key
     * @var string
     */
    protected $primaryKey = 'id';


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event',
        'name',
        'slug',
        'local',
        'date',
        'scheduling',
        'banner',
        'courtesies',
        'courtesies_used',
        'classification',
        'address_id',
        'category_id',
        'producer_id',
        'description',
        'maps_id',
        'emphasis_type',
        'emphasis_date_init',
        'emphasis_date_finish',
        'emphasis_rate',
        'contact_email',
        'contact_name',
        'description_canceled',
        'date_canceled',
        'canceled'
    ];

    protected $casts = [
        'emphasis_type' => 'array'
    ];

    protected $with = ['producerCompanyName'];

    public function producerCompanyName(): BelongsTo
    {
        return $this->belongsTo(Producer::class, 'producer_id')->select('id', 'corporative_name');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function producer(): BelongsTo
    {
        return $this->belongsTo(Producer::class, 'producer_id');
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class, 'event_id', 'id');
    }

    public function ticketEvents(): HasMany
    {
        return $this->hasMany(TicketEvent::class, 'event_id', 'id');
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(Maps::class, 'maps_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'event_id', 'id');
    }

    public function getbannerAttribute()
    {
        if(isset($this->attributes['banner'])){
            return Storage::disk('s3')->temporaryUrl($this->attributes['banner'], now()->addMinutes(720));
        }
        return null;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * active timestamps
     * timestamp is used in transferencia_data for execution registred
     * @var bool
     */
    public $timestamps = true;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [];


}
