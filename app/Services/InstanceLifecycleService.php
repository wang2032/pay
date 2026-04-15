<?php

namespace App\Services;

use App\Models\Instance;

class InstanceLifecycleService
{
    protected BillingService $billingService;
    protected AccessService $accessService;

    public function __construct(
        BillingService $billingService,
        AccessService $accessService
    ) {
        $this->billingService = $billingService;
        $this->accessService = $accessService;
    }

    /**
     * 停止实例
     */
    public function stopInstance(Instance $instance): bool
    {
        if (!$instance->canStop()) {
            throw new \Exception('实例当前状态不允许停止');
        }

        $instance->update([
            'status' => Instance::STATUS_STOPPED,
        ]);

        // 停止计费（按量实例）
        if ($instance->pricePlan->plan_type === 'hourly') {
            $this->billingService->stopBilling($instance);
        }

        // TODO: 实际停止容器/资源

        return true;
    }

    /**
     * 启动实例
     */
    public function startInstance(Instance $instance): bool
    {
        if (!$instance->canStart()) {
            throw new \Exception('实例当前状态不允许启动');
        }

        $instance->update([
            'status' => Instance::STATUS_RUNNING,
        ]);

        // 恢复计费（按量实例）
        if ($instance->pricePlan->plan_type === 'hourly') {
            $instance->update([
                'billing_status' => Instance::BILLING_ACTIVE,
            ]);
        }

        // TODO: 实际启动容器/资源

        // 健康检查
        if (!$this->accessService->checkSSHHealth($instance)) {
            throw new \Exception('SSH健康检查失败');
        }

        if (!$this->accessService->checkJupyterLabHealth($instance)) {
            throw new \Exception('JupyterLab健康检查失败');
        }

        return true;
    }

    /**
     * 删除实例
     */
    public function deleteInstance(Instance $instance): bool
    {
        if (!$instance->canDelete()) {
            throw new \Exception('实例当前状态不允许删除');
        }

        $instance->update([
            'status' => Instance::STATUS_TERMINATED,
            'billing_status' => Instance::BILLING_CLOSED,
        ]);

        // 释放访问入口
        $this->accessService->releaseAccess($instance);

        // TODO: 清理本地数据
        // TODO: 释放容器/资源

        return true;
    }

    /**
     * 续费
     */
    public function renewInstance(Instance $instance, string $planType): bool
    {
        if ($instance->pricePlan->plan_type === 'hourly') {
            throw new \Exception('按量计费实例不支持续费');
        }

        return $this->billingService->renew($instance, $planType);
    }

    /**
     * 处理到期
     */
    public function handleExpired(Instance $instance): bool
    {
        if ($instance->status !== Instance::STATUS_EXPIRED) {
            return false;
        }

        // 停止实例服务
        $instance->update([
            'billing_status' => Instance::BILLING_PERIOD_ENDED,
        ]);

        // TODO: 停止容器但保留数据

        return true;
    }

    /**
     * 处理异常
     */
    public function handleError(Instance $instance, string $errorMessage): bool
    {
        $instance->update([
            'status' => Instance::STATUS_ERROR,
            'error_message' => $errorMessage,
        ]);

        // 停止计费
        $this->billingService->stopBilling($instance, 'error');

        return true;
    }

    /**
     * 获取生命周期动作
     */
    public function getAvailableActions(Instance $instance): array
    {
        $actions = [];

        if ($instance->canStop()) {
            $actions[] = 'stop';
        }

        if ($instance->canStart()) {
            $actions[] = 'start';
        }

        if ($instance->canDelete()) {
            $actions[] = 'delete';
        }

        if ($instance->pricePlan->plan_type !== 'hourly') {
            $actions[] = 'renew';
        }

        $actions[] = 'copy_ssh_command';
        $actions[] = 'open_jupyterlab';

        return $actions;
    }
}