<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'title', 'description', 'asset_id', 'priority', 'status',
        'created_by', 'assigned_to', 'approved_by', 'closed_by', 'company_id', 'branch_id',
        'attachments', 'resolution', 'phone_number', 'device_location',
        'ip_address', 'repair_cost', 'replaced_part_name',
        'replaced_part_serial_number', 'replaced_part_brand', 'replaced_part_model',
        'images', 'custom_device_type', 'custom_device_serial_number',
        'custom_device_asset_code', 'custom_device_brand', 'custom_device_model',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'images' => 'array',
            'repair_cost' => 'decimal:2',
            'closed_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TicketHistory::class);
    }
}
