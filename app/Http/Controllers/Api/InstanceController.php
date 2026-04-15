<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InstanceService;
use App\Services\InstanceLifecycleService;
use App\Services\AccessService;
use App\Models\InstanceImage;
use App\Models\PricePlan;
use App\Models\Balance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstanceController extends Controller
{
    protected InstanceService $instanceService;
    protected InstanceLifecycleService $lifecycleService;
    protected AccessService $accessService;

    public function __construct(
        InstanceService $instanceService,
        InstanceLifecycleService $lifecycleService,
        AccessService $accessService
    ) {
        $this->instanceService = $instanceService;
        $this->lifecycleService = $lifecycleService;
        $this->accessService = $accessService;
    }

    /**
     * 获取创建页初始化数据
     */
    public function createInit(int $packageId): JsonResponse
    {
        // TODO: 获取当前用户
        $userId = 1;

        // 获取套餐信息
        $package = \App\Services\ResourcePackageService::getPackageDetail($packageId);

        if (!$package) {
            return response()->json([
                'success' => false,
                'message' => '套餐不存在',
            ], 404);
        }

        // 获取镜像列表
        $images = InstanceImage::where('is_active', true)->get()->map(function ($image) use ($package) {
            // TODO: 实际检查兼容性
            $compatibility = $image->cuda_version === $package->cuda_version
                ? InstanceImage::COMPATIBLE
                : InstanceImage::WARNING;

            return [
                'id' => $image->id,
                'name' => $image->name,
                'version' => $image->version,
                'base_system' => $image->base_system,
                'cuda_version' => $image->cuda_version,
                'framework' => $image->framework,
                'updated_at' => $image->updated_at,
                'compatibility_status' => $compatibility,
                'compatibility_text' => $compatibility === InstanceImage::COMPATIBLE ? '兼容' : '警告',
            ];
        });

        // 获取余额
        $balance = Balance::where('user_id', $userId)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'package' => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'gpu_model' => $package->gpu_model,
                    'gpu_count' => $package->gpu_count,
                    'vram' => $package->vram,
                    'cpu_count' => $package->cpu_count,
                    'cpu_model' => $package->cpu_model,
                    'memory' => $package->memory,
                    'region' => $package->region,
                    'driver_version' => $package->driver_version,
                    'cuda_version' => $package->cuda_version,
                    'supports_ssh' => $package->supports_ssh,
                    'supports_jupyterlab' => $package->supports_jupyterlab,
                ],
                'price_plans' => $package->activePricePlans->map(function ($plan) {
                    return [
                        'id' => $plan->id,
                        'plan_type' => $plan->plan_type,
                        'plan_type_text' => $plan->plan_type_text,
                        'price' => $plan->price,
                        'display_price' => $plan->display_price,
                    ];
                }),
                'images' => $images,
                'balance' => $balance ? [
                    'amount' => $balance->amount,
                    'currency' => $balance->currency,
                    'is_enough' => true,
                ] : null,
            ],
        ]);
    }

    /**
     * 校验镜像兼容性
     */
    public function checkImageCompatibility(Request $request): JsonResponse
    {
        $imageId = $request->input('image_id');
        $packageId = $request->input('package_id');

        $image = InstanceImage::find($imageId);
        $package = \App\Models\ResourcePackage::find($packageId);

        if (!$image || !$package) {
            return response()->json([
                'success' => false,
                'message' => '镜像或套餐不存在',
            ], 404);
        }

        // TODO: 实际兼容性检查逻辑
        $isCompatible = $image->cuda_version === $package->cuda_version;

        return response()->json([
            'success' => true,
            'data' => [
                'compatible' => $isCompatible,
                'status' => $isCompatible ? 'compatible' : 'warning',
                'message' => $isCompatible ? '镜像与套餐兼容' : '镜像与套餐可能存在兼容性问题',
            ],
        ]);
    }

    /**
     * 校验余额
     */
    public function checkBalance(Request $request): JsonResponse
    {
        $userId = $request->input('user_id', 1);
        $pricePlanId = $request->input('price_plan_id');

        $pricePlan = PricePlan::find($pricePlanId);
        $balance = Balance::where('user_id', $userId)->first();

        if (!$pricePlan) {
            return response()->json([
                'success' => false,
                'message' => '价格方案不存在',
            ], 404);
        }

        $isEnough = $balance && $balance->isEnough($pricePlan->price);

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $balance ? $balance->amount : 0,
                'required' => $pricePlan->price,
                'is_enough' => $isEnough,
                'message' => $isEnough ? '余额充足' : '余额不足，请先充值',
            ],
        ]);
    }

    /**
     * 提交创建
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'required|integer',
            'resource_package_id' => 'required|integer',
            'price_plan_id' => 'required|integer',
            'name' => 'required|string|max:50|regex:/^[a-zA-Z0-9_-]+$/',
            'image_id' => 'required|integer',
            'ssh_public_key' => 'nullable|string',
            'ssh_password' => 'nullable|string',
        ]);

        try {
            $result = $this->instanceService->createInstance($data);

            return response()->json([
                'success' => true,
                'message' => '创建请求已受理',
                'data' => $result,
            ]);
        } catch (\App\Exceptions\InstanceException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 获取创建任务状态
     */
    public function createStatus(int $taskId): JsonResponse
    {
        try {
            $status = $this->instanceService->getCreateTaskStatus($taskId);

            return response()->json([
                'success' => true,
                'data' => $status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * 取消创建
     */
    public function cancelCreate(int $taskId): JsonResponse
    {
        try {
            $this->instanceService->cancelCreate($taskId);

            return response()->json([
                'success' => true,
                'message' => '创建已取消',
            ]);
        } catch (\App\Exceptions\InstanceException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 获取实例详情
     */
    public function show(int $instanceId): JsonResponse
    {
        $instance = $this->instanceService->getInstanceDetail($instanceId);

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => '实例不存在',
            ], 404);
        }

        $data = [
            'id' => $instance->id,
            'name' => $instance->name,
            'status' => $instance->status,
            'status_text' => $instance->status_text,
            'billing_status' => $instance->billing_status,
            'billing_status_text' => $instance->billing_status_text,
            'resource_package' => [
                'name' => $instance->resourcePackage->name ?? null,
                'gpu_model' => $instance->resourcePackage->gpu_model ?? null,
                'gpu_count' => $instance->resourcePackage->gpu_count ?? null,
                'vram' => $instance->resourcePackage->vram ?? null,
                'cpu_count' => $instance->resourcePackage->cpu_count ?? null,
                'cpu_model' => $instance->resourcePackage->cpu_model ?? null,
                'memory' => $instance->resourcePackage->memory ?? null,
                'region' => $instance->resourcePackage->region ?? null,
            ],
            'price_plan' => [
                'plan_type' => $instance->pricePlan->plan_type ?? null,
                'plan_type_text' => $instance->pricePlan->plan_type_text ?? null,
                'price' => $instance->pricePlan->price ?? null,
            ],
            'ssh' => $instance->ssh_host ? [
                'host' => $instance->ssh_host,
                'port' => $instance->ssh_port,
                'username' => $instance->ssh_username,
                'login_type' => $instance->ssh_public_key ? 'public_key' : 'password',
                'ssh_command' => $this->accessService->getSSHCommand($instance),
            ] : null,
            'jupyterlab_url' => $instance->jupyterlab_url,
            'local_storage' => [
                'path' => $instance->local_storage_path,
                'note' => '删除实例后本地存储将被清理',
            ],
            'billing' => $instance->pricePlan->plan_type === 'hourly' ? [
                'hourly_price' => $instance->pricePlan->price,
                'usage_hours' => $instance->total_usage_hours,
                'total_cost' => $instance->total_cost,
                'billing_started_at' => $instance->billing_started_at,
            ] : [
                'period_started_at' => $instance->period_started_at,
                'period_ended_at' => $instance->period_ended_at,
                'remaining_days' => $instance->period_ended_at
                    ? now()->diffInDays($instance->period_ended_at, false)
                    : null,
                'is_expired' => $instance->status === 'expired',
            ],
            'available_actions' => $this->lifecycleService->getAvailableActions($instance),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * 停止实例
     */
    public function stop(int $instanceId): JsonResponse
    {
        $instance = \App\Models\Instance::find($instanceId);

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => '实例不存在',
            ], 404);
        }

        try {
            $this->lifecycleService->stopInstance($instance);

            return response()->json([
                'success' => true,
                'message' => '实例已停止',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 启动实例
     */
    public function start(int $instanceId): JsonResponse
    {
        $instance = \App\Models\Instance::find($instanceId);

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => '实例不存在',
            ], 404);
        }

        try {
            $this->lifecycleService->startInstance($instance);

            return response()->json([
                'success' => true,
                'message' => '实例已启动',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 删除实例
     */
    public function destroy(int $instanceId): JsonResponse
    {
        $instance = \App\Models\Instance::find($instanceId);

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => '实例不存在',
            ], 404);
        }

        try {
            $this->lifecycleService->deleteInstance($instance);

            return response()->json([
                'success' => true,
                'message' => '实例已删除',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 续费
     */
    public function renew(Request $request, int $instanceId): JsonResponse
    {
        $instance = \App\Models\Instance::find($instanceId);

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => '实例不存在',
            ], 404);
        }

        $planType = $request->input('plan_type', 'monthly');

        try {
            $this->lifecycleService->renewInstance($instance, $planType);

            return response()->json([
                'success' => true,
                'message' => '续费成功',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}