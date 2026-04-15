<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Instance extends Model
{
    // 实例状态
    const STATUS_PENDING = 'pending';
    const STATUS_CREATING = 'creating';
    const STATUS_RUNNING = 'running';
    const STATUS_STOPPED = 'stopped';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_ERROR = 'error';
    const STATUS_TERMINATED = 'terminated';

    // 计费状态
    const BILLING_NOT_STARTED = 'not_started';
    const BILLING_ACTIVE = 'billing_active';
    const BILLING_STOPPED = 'billing_stopped';
    const BILLING_PERIOD_ENDED = 'period_ended';
    const BILLING_INSUFFICIENT = 'payment_insufficient';
    const BILLING_CLOSED = 'closed';

    protected $fillable = [
        'user_id',
        'resource_package_id',
        'price_plan_id',
        'name',
        'status',
        'billing_status',
        'image_id',
        'ssh_public_key',
        'ssh_password',
        'ssh_host',
        'ssh_port',
        'ssh_username',
        'jupyterlab_url',
        'local_storage_path',
        'billing_started_at',
        'period_started_at',
        'period_ended_at',
        'total_usage_hours',
        'total_cost',
        'error_message',
    ];

    protected $casts = [
        'billing_started_at' => 'datetime',
        'period_started_at' => 'datetime',
        'period_ended_at' => 'datetime',
        'total_usage_hours' => 'decimal:2',
        'total_cost' => 'decimal:4',
    ];

    protected $hidden = [
        'ssh_password',
    ];

    /**
     * 关联资源套餐
     */
    public function resourcePackage(): BelongsTo
    {
        return $this->belongsTo(ResourcePackage::class);
    }

    /**
     * 关联价格方案
     */
    public function pricePlan(): BelongsTo
    {
        return $this->belongsTo(PricePlan::class);
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => '待创建',
            self::STATUS_CREATING => '创建中',
            self::STATUS_RUNNING => '运行中',
            self::STATUS_STOPPED => '已停止',
            self::STATUS_FAILED => '失败',
            self::STATUS_CANCELLED => '已取消',
            self::STATUS_EXPIRED => '已到期',
            self::STATUS_SUSPENDED => '已暂停',
            self::STATUS_ERROR => '异常',
            self::STATUS_TERMINATED => '已终止',
            default => '未知',
        };
    }

    /**
     * 获取计费状态文本
     */
    public function getBillingStatusTextAttribute(): string
    {
        return match($this->billing_status) {
            self::BILLING_NOT_STARTED => '未开始',
            self::BILLING_ACTIVE => '计费中',
            self::BILLING_STOPPED => '计费停止',
            self::BILLING_PERIOD_ENDED => '包期结束',
            self::BILLING_INSUFFICIENT => '欠费',
            self::BILLING_CLOSED => '已关闭',
            default => '未知',
        };
    }

    /**
     * 是否运行中
     */
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * 是否可停止
     */
    public function canStop(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * 是否可启动
     */
    public function canStart(): bool
    {
        return $this->status === self::STATUS_STOPPED;
    }

    /**
     * 是否可删除
     */
    public function canDelete(): bool
    {
        return in_array($this->status, [
            self::STATUS_RUNNING,
            self::STATUS_STOPPED,
            self::STATUS_ERROR,
        ]);
    }
}