<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    protected $table = 'login_attempts';

    protected $fillable = [
        'email',
        'ip_address',
        'attempted_at',
        'successful',
    ];

    protected $casts = [
        'successful'   => 'boolean',
        'attempted_at' => 'datetime',
    ];

    public $timestamps = false;
}
