<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    // 订单类型
    const TYPE_CREATE = 'create';
    const TYPE_RENEWAL = 'renewal';

    // 订单状态
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'user_id',
        'instance_id',
        'order_type',
        'order_no',
        'amount',
        'currency',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'paid_at' => 'datetime',
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
     * 获取订单类型文本
     */
    public function getOrderTypeTextAttribute(): string
    {
        return match($this->order_type) {
            self::TYPE_CREATE => '创建订单',
            self::TYPE_RENEWAL => '续费订单',
            default => '未知',
        };
    }

    /**
     * 获取订单状态文本
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => '待支付',
            self::STATUS_PAID => '已支付',
            self::STATUS_FAILED => '失败',
            self::STATUS_REFUNDED => '已退款',
            default => '未知',
        };
    }
}