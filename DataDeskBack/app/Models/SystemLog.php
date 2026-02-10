<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemLog extends Model
{
    protected $fillable = [
        'user_id', 'user_name', 'company_id', 'company_name',
        'action', 'module', 'description', 'ip_address', 'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
