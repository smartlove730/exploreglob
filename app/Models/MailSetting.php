<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailSetting extends Model
{
    protected $fillable = [
        'provider',
        'mailer',
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'from_name',
        'from_email',
        'is_active',
        'last_tested_at',
    ];

    protected $casts = [
        'password' => 'encrypted',
        'is_active' => 'boolean',
        'last_tested_at' => 'datetime',
    ];
}
