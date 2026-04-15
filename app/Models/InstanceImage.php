<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstanceImage extends Model
{
    // 兼容状态
    const COMPATIBLE = 'compatible';
    const WARNING = 'warning';
    const INCOMPATIBLE = 'incompatible';

    protected $fillable = [
        'name',
        'version',
        'base_system',
        'cuda_version',
        'framework',
        'updated_at',
        'compatibility_status',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'updated_at' => 'datetime',
    ];

    /**
     * 关联实例
     */
    public function instances(): HasMany
    {
        return $this->hasMany(Instance::class);
    }

    /**
     * 获取兼容状态文本
     */
    public function getCompatibilityTextAttribute(): string
    {
        return match($this->compatibility_status) {
            self::COMPATIBLE => '兼容',
            self::WARNING => '警告',
            self::INCOMPATIBLE => '不可用',
            default => '未知',
        };
    }
}