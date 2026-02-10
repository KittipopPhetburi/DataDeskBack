<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'asset_code', 'serial_number', 'type', 'brand', 'model',
        'start_date', 'location', 'company_id', 'branch_id', 'responsible',
        'department', 'ip_address', 'diagram_file', 'images',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'images' => 'array',
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

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
