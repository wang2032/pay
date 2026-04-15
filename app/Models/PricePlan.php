<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricePlan extends Model
{
    protected $fillable = [
        'resource_package_id',
        'plan_type', // hourly, daily, weekly, monthly
        'price',
        'currency',
        'is_active',
        'effective_at',
    ];

    protected $casts = [
        'price' => 'decimal:4',
        'is_active' => 'boolean',
        'effective_at' => 'datetime',
    ];

    /**
     * 关联资源套餐
     */
    public function resourcePackage(): BelongsTo
    {
        return $this->belongsTo(ResourcePackage::class);
    }

    /**
     * 获取方案类型文本
     */
    public function getPlanTypeTextAttribute(): string
    {
        return match($this->plan_type) {
            'hourly' => '按量',
            'daily' => '包天',
            'weekly' => '包周',
            'monthly' => '包月',
            default => '未知',
        };
    }

    /**
     * 获取显示价格
     */
    public function getDisplayPriceAttribute(): string
    {
        return number_format($this->price, 4) . ' ' . strtoupper($this->currency);
    }
}