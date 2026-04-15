<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillRecord extends Model
{
    // 类型
    const TYPE_DEDUCTION = 'deduction'; // 扣费
    const TYPE_REFILL = 'refill'; // 充值
    const TYPE_REFUND = 'refund'; // 退款
    const TYPE_RENEWAL = 'renewal'; // 续费

    protected $fillable = [
        'user_id',
        'instance_id',
        'type',
        'amount',
        'currency',
        'balance_before',
        'balance_after',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'balance_before' => 'decimal:4',
        'balance_after' => 'decimal:4',
    ];

    /**
     * 获取用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 获取实例
     */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(Instance::class);
    }

    /**
     * 获取类型文本
     */
    public function getTypeTextAttribute(): string
    {
        return match($this->type) {
            self::TYPE_DEDUCTION => '扣费',
            self::TYPE_REFILL => '充值',
            self::TYPE_REFUND => '退款',
            self::TYPE_RENEWAL => '续费',
            default => '未知',
        };
    }
}