<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * @var string
     */
    protected $tokenName = 'custom-token';

    /**
     * Bank table for this model
     * @var string
     */
    protected $table = 'users';

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
        'name',
        'email',
        'password',
        'email_verified_at',
        'cpf',
        'level',
        'status',
        'producer_id',
        'address_id',
        'customer_id',
        'phone',
        'office'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'roles_has_users', 'user_id', 'role_id');
    }

    public function address(): BelongsTo
    {
        return $this->BelongsTo(Address::class, 'address_id', 'id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'payment_id', 'id');
    }

    public function producer()
    {
        return $this->belongsTo(Producer::class, 'producer_id');
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
        'user_name',
        'is_owner'
    ];

    public function getUserNameAttribute()
    {
        $fullName = $this->attributes['name'];
        $parts = explode(' ', $fullName);
        return $parts[0] . ' ' . $parts[count($parts) - 1];
    }

    public function setCpfAttribute($value)
    {
        $this->attributes['cpf'] = preg_replace("/[^0-9]/", "", $value);
    }

    public function setPhoneAttribute($value)
    {
        $this->attributes['phone'] = preg_replace("/[^0-9]/", "", $value);
    }

    public function getIsOwnerAttribute()
    {
        $owner = $this?->producer?->owner ?? null;
        $is_owner = false;
        if ($owner && ($owner->cpf == $this->cpf || $owner->email == $this->email)) {
            $is_owner = true;
        }
        return $is_owner;
    }
}
