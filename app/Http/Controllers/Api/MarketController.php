<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ResourcePackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    protected ResourcePackageService $resourcePackageService;

    public function __construct(ResourcePackageService $resourcePackageService)
    {
        $this->resourcePackageService = $resourcePackageService;
    }

    /**
     * 获取市场列表
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['region', 'gpu_model', 'stock_status', 'sort_by_price']);

        $packages = $this->resourcePackageService->getMarketList($filters);

        $data = $packages->map(function ($package) {
            return [
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
                'stock_status' => $package->stock_status,
                'stock_status_text' => $package->stock_status_text,
                'is_available' => $package->isAvailable(),
                'price_plans' => $package->activePricePlans->map(function ($plan) {
                    return [
                        'id' => $plan->id,
                        'plan_type' => $plan->plan_type,
                        'plan_type_text' => $plan->plan_type_text,
                        'price' => $plan->price,
                        'display_price' => $plan->display_price,
                    ];
                }),
                'min_price' => $package->activePricePlans->min('price'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * 获取套餐详情
     */
    public function show(int $id): JsonResponse
    {
        $package = $this->resourcePackageService->getPackageDetail($id);

        if (!$package) {
            return response()->json([
                'success' => false,
                'message' => '套餐不存在',
            ], 404);
        }

        $data = [
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
            'local_storage' => $package->local_storage,
            'supports_ssh' => $package->supports_ssh,
            'supports_jupyterlab' => $package->supports_jupyterlab,
            'stock_status' => $package->stock_status,
            'stock_status_text' => $package->stock_status_text,
            'is_available' => $package->isAvailable(),
            'price_plans' => $package->activePricePlans->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'plan_type' => $plan->plan_type,
                    'plan_type_text' => $plan->plan_type_text,
                    'price' => $plan->price,
                    'display_price' => $plan->display_price,
                ];
            }),
            'rules_summary' => '具体规则请参考平台服务条款',
            'image_compatibility' => '镜像兼容性说明',
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * 获取筛选项
     */
    public function filters(): JsonResponse
    {
        $options = $this->resourcePackageService->getFilterOptions();

        return response()->json([
            'success' => true,
            'data' => $options,
        ]);
    }
}