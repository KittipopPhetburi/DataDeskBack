<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataCenterLog extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'visitor_name', 'visitor_company', 'contact_number',
        'entry_time', 'exit_time', 'purpose', 'equipment_brought',
        'authorized_by', 'company_id', 'branch_id', 'created_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'entry_time' => 'datetime',
            'exit_time' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
