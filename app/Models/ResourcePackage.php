<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResourcePackage extends Model
{
    protected $fillable = [
        'name',
        'gpu_model',
        'gpu_count',
        'vram',
        'cpu_count',
        'cpu_model',
        'memory',
        'region',
        'driver_version',
        'cuda_version',
        'local_storage',
        'supports_ssh',
        'supports_jupyterlab',
        'is_active',
        'stock_status', // available, scarce, sold_out
    ];

    protected $casts = [
        'gpu_count' => 'integer',
        'vram' => 'integer',
        'cpu_count' => 'integer',
        'memory' => 'integer',
        'local_storage' => 'integer',
        'supports_ssh' => 'boolean',
        'supports_jupyterlab' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * 关联价格方案
     */
    public function pricePlans(): HasMany
    {
        return $this->hasMany(PricePlan::class);
    }

    /**
     * 获取有效的价格方案
     */
    public function activePricePlans(): HasMany
    {
        return $this->hasMany(PricePlan::class)->where('is_active', true);
    }

    /**
     * 判断是否可购买
     */
    public function isAvailable(): bool
    {
        return $this->is_active && $this->stock_status !== 'sold_out';
    }

    /**
     * 获取库存状态文本
     */
    public function getStockStatusTextAttribute(): string
    {
        return match($this->stock_status) {
            'available' => '可购买',
            'scarce' => '紧张',
            'sold_out' => '售罄',
            default => '未知',
        };
    }
}