<?php

namespace App\Services;

use App\Models\Instance;
use App\Models\Balance;
use App\Models\BillRecord;
use App\Models\PricePlan;
use Carbon\Carbon;

class BillingService
{
    /**
     * 启动计费
     */
    public function startBilling(Instance $instance): void
    {
        $pricePlan = $instance->pricePlan;

        if ($pricePlan->plan_type === 'hourly') {
            // 按量计费：更新计费状态
            $instance->update([
                'billing_status' => Instance::BILLING_ACTIVE,
                'billing_started_at' => now(),
            ]);
        } else {
            // 包期计费：设置到期时间
            $endedAt = $this->calculatePeriodEnd($pricePlan->plan_type);
            $instance->update([
                'billing_status' => Instance::BILLING_ACTIVE,
                'period_started_at' => now(),
                'period_ended_at' => $endedAt,
            ]);
        }
    }

    /**
     * 计算包期到期时间
     */
    protected function calculatePeriodEnd(string $planType): Carbon
    {
        return match($planType) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            default => now()->addDay(),
        };
    }

    /**
     * 停止计费
     */
    public function stopBilling(Instance $instance, string $reason = 'stopped'): void
    {
        $instance->update([
            'billing_status' => Instance::BILLING_STOPPED,
        ]);
    }

    /**
     * 按量计费扣费
     */
    public function deductHourlyBilling(Instance $instance): bool
    {
        if ($instance->billing_status !== Instance::BILLING_ACTIVE) {
            return false;
        }

        $pricePlan = $instance->pricePlan;
        if ($pricePlan->plan_type !== 'hourly') {
            return false;
        }

        // 计算当前费用
        $hours = $instance->total_usage_hours + 1;
        $cost = $hours * $pricePlan->price;

        // 获取余额
        $balance = Balance::where('user_id', $instance->user_id)->first();

        // 检查余额是否足够
        if (!$balance || !$balance->isEnough($pricePlan->price)) {
            $this->handleInsufficientBalance($instance);
            return false;
        }

        // 扣费
        DB::transaction(function () use ($balance, $instance, $cost, $pricePlan) {
            $balance->deduct($pricePlan->price);

            $instance->update([
                'total_usage_hours' => $instance->total_usage_hours + 1,
                'total_cost' => $cost,
            ]);

            BillRecord::create([
                'user_id' => $instance->user_id,
                'instance_id' => $instance->id,
                'type' => BillRecord::TYPE_DEDUCTION,
                'amount' => -$pricePlan->price,
                'currency' => $pricePlan->currency,
                'balance_before' => $balance->amount + $pricePlan->price,
                'balance_after' => $balance->amount,
                'description' => "实例 {$instance->name} 计费",
            ]);
        });

        return true;
    }

    /**
     * 处理欠费
     */
    protected function handleInsufficientBalance(Instance $instance): void
    {
        $instance->update([
            'billing_status' => Instance::BILLING_INSUFFICIENT,
        ]);

        // TODO: 发送欠费通知
        // TODO: 启动欠费缓冲期倒计时
    }

    /**
     * 续费
     */
    public function renew(Instance $instance, string $planType): bool
    {
        if ($instance->billing_status === Instance::BILLING_PERIOD_ENDED) {
            $instance->update([
                'billing_status' => Instance::BILLING_ACTIVE,
            ]);
        }

        // 延长到期时间
        $currentEnd = $instance->period_ended_at ?? now();
        $newEnd = $this->calculatePeriodEndFromDate($planType, $currentEnd);

        $instance->update([
            'period_ended_at' => $newEnd,
        ]);

        // 记录续费账单
        BillRecord::create([
            'user_id' => $instance->user_id,
            'instance_id' => $instance->id,
            'type' => BillRecord::TYPE_RENEWAL,
            'amount' => 0, // TODO: 计算续费金额
            'currency' => 'USDT',
            'balance_before' => 0,
            'balance_after' => 0,
            'description' => "实例 {$instance->name} 续费至 {$newEnd}",
        ]);

        return true;
    }

    /**
     * 从指定日期计算到期时间
     */
    protected function calculatePeriodEndFromDate(string $planType, Carbon $fromDate): Carbon
    {
        return match($planType) {
            'daily' => $fromDate->copy()->addDay(),
            'weekly' => $fromDate->copy()->addWeek(),
            'monthly' => $fromDate->copy()->addMonth(),
            default => $fromDate->copy()->addDay(),
        };
    }

    /**
     * 检查包期到期
     */
    public function checkPeriodExpired(Instance $instance): bool
    {
        if ($instance->billing_status !== Instance::BILLING_ACTIVE) {
            return false;
        }

        if ($instance->period_ended_at && now()->greaterThan($instance->period_ended_at)) {
            $instance->update([
                'billing_status' => Instance::BILLING_PERIOD_ENDED,
                'status' => Instance::STATUS_EXPIRED,
            ]);
            return true;
        }

        return false;
    }
}