<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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

    protected $appends = ['reminder_at_formatted'];

    public function getReminderAtFormattedAttribute()
    {
        return Carbon::parse($this->reminder_at)
            ->timezone('Asia/Jakarta')
            ->format('d-m-Y H:i');
    }
}
