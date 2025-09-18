<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'user_id',
        'filename',
        'file_path',
        'total_products',
        'processed_products',
        'status',
        'results',
    ];

    protected $casts = [
        'results' => 'array',
        'total_products' => 'integer',
        'processed_products' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_products === 0) {
            return 0;
        }
        
        return round(($this->processed_products / $this->total_products) * 100, 2);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }
}
