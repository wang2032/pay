<?php

namespace App\Services;

use App\Models\Instance;
use App\Models\CreateTask;
use App\Models\ResourcePackage;
use App\Models\PricePlan;
use App\Models\InstanceImage;
use App\Models\Balance;
use App\Exceptions\InstanceException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InstanceService
{
    protected AccessService $accessService;
    protected BillingService $billingService;

    public function __construct(
        AccessService $accessService,
        BillingService $billingService
    ) {
        $this->accessService = $accessService;
        $this->billingService = $billingService;
    }

    /**
     * 创建实例 - 受理
     */
    public function createInstance(array $data): array
    {
        // 校验
        $this->validateCreateData($data);

        return DB::transaction(function () use ($data) {
            // 创建实例记录
            $instance = Instance::create([
                'user_id' => $data['user_id'],
                'resource_package_id' => $data['resource_package_id'],
                'price_plan_id' => $data['price_plan_id'],
                'name' => $data['name'],
                'status' => Instance::STATUS_PENDING,
                'billing_status' => Instance::BILLING_NOT_STARTED,
                'image_id' => $data['image_id'],
                'ssh_public_key' => $data['ssh_public_key'] ?? null,
                'ssh_password' => $data['ssh_password'] ?? null,
            ]);

            // 创建任务记录
            $task = CreateTask::create([
                'instance_id' => $instance->id,
                'status' => CreateTask::STATUS_PENDING,
            ]);

            // TODO: 触发异步创建流程
            // $this->dispatchCreateJob($instance);

            return [
                'instance_id' => $instance->id,
                'task_id' => $task->id,
                'status' => 'pending',
            ];
        });
    }

    /**
     * 校验创建数据
     */
    protected function validateCreateData(array $data): void
    {
        // 校验资源套餐
        $package = ResourcePackage::find($data['resource_package_id']);
        if (!$package || !$package->is_active) {
            throw new InstanceException('资源套餐无效或已下架');
        }

        if ($package->stock_status === 'sold_out') {
            throw new InstanceException('该资源套餐已售罄');
        }

        // 校验价格方案
        $pricePlan = PricePlan::find($data['price_plan_id']);
        if (!$pricePlan || !$pricePlan->is_active) {
            throw new InstanceException('价格方案无效');
        }

        if ($pricePlan->resource_package_id !== $package->id) {
            throw new InstanceException('价格方案与资源套餐不匹配');
        }

        // 校验镜像
        $image = InstanceImage::find($data['image_id']);
        if (!$image || !$image->is_active) {
            throw new InstanceException('镜像无效');
        }

        if ($image->compatibility_status === InstanceImage::INCOMPATIBLE) {
            throw new InstanceException('该镜像与资源套餐不兼容');
        }

        // 校验SSH
        if (empty($data['ssh_public_key']) && empty($data['ssh_password'])) {
            throw new InstanceException('请提供SSH公钥或密码');
        }

        if (!empty($data['ssh_public_key'])) {
            if (!Str::startsWith($data['ssh_public_key'], ['ssh-rsa', 'ssh-dss', 'ssh-ed25519', 'ecdsa-sha2'])) {
                throw new InstanceException('SSH公钥格式无效');
            }
        }

        // 校验余额（仅按量计费）
        if ($pricePlan->plan_type === 'hourly') {
            $balance = Balance::where('user_id', $data['user_id'])->first();
            if (!$balance || !$balance->isEnough($pricePlan->price)) {
                throw new InstanceException('余额不足，请先充值');
            }
        }
    }

    /**
     * 获取创建任务状态
     */
    public function getCreateTaskStatus(int $taskId): array
    {
        $task = CreateTask::with('instance.resourcePackage')->findOrFail($taskId);

        return [
            'task_id' => $task->id,
            'instance_id' => $task->instance_id,
            'status' => $task->status,
            'status_text' => $task->status_text,
            'error_message' => $task->error_message,
            'instance' => $task->instance ? [
                'resource_package' => $task->instance->resourcePackage->name ?? null,
                'price_plan' => $task->instance->pricePlan->plan_type_text ?? null,
                'image' => $task->instance->image_id,
            ] : null,
        ];
    }

    /**
     * 取消创建
     */
    public function cancelCreate(int $taskId): bool
    {
        $task = CreateTask::find($taskId);

        if (!$task || !$task->canCancel()) {
            throw new InstanceException('该任务无法取消');
        }

        $task->update([
            'status' => CreateTask::STATUS_CANCELLED,
            'completed_at' => now(),
        ]);

        // 更新实例状态
        if ($task->instance) {
            $task->instance->update([
                'status' => Instance::STATUS_CANCELLED,
            ]);
        }

        return true;
    }

    /**
     * 内部方法：执行异步创建
     * TODO: 由队列任务调用
     */
    public function executeCreate(Instance $instance): void
    {
        $instance->update([
            'status' => Instance::STATUS_CREATING,
        ]);

        try {
            // 资源再次校验
            // ...

            // 分配访问入口
            $accessInfo = $this->accessService->allocateAccess($instance);

            // 初始化SSH
            $this->accessService->initializeSSH($instance);

            // 初始化JupyterLab
            $this->accessService->initializeJupyterLab($instance);

            // 更新实例
            $instance->update([
                'status' => Instance::STATUS_RUNNING,
                'ssh_host' => $accessInfo['ssh_host'],
                'ssh_port' => $accessInfo['ssh_port'],
                'ssh_username' => $accessInfo['ssh_username'],
                'jupyterlab_url' => $accessInfo['jupyterlab_url'],
                'local_storage_path' => $accessInfo['local_storage_path'],
            ]);

            // 更新任务
            $instance->createTask->update([
                'status' => CreateTask::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            // 启动计费
            $this->billingService->startBilling($instance);

        } catch (\Exception $e) {
            $instance->update([
                'status' => Instance::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            $instance->createTask->update([
                'status' => CreateTask::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * 获取实例详情
     */
    public function getInstanceDetail(int $instanceId): ?Instance
    {
        return Instance::with([
            'resourcePackage',
            'pricePlan',
        ])->find($instanceId);
    }
}