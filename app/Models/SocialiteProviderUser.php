<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialiteProviderUser extends Model
{
    use HasFactory;

    protected  $table= 'socialites_providers';

    protected $fillable = [
        'provider',
        'provider_id',
        'user_id'
    ];

    // User
    public function user(){
        return $this->belongsTo(User::class);
    }
}
