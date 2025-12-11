<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'message',
        'reminder_at',
        'sent'
    ];

    protected $casts = [
        'reminder_at' => 'datetime',
        'sent' => 'boolean',
    ];
}
