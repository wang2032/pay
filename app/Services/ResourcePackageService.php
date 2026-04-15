<?php

namespace App\Services;

use App\Models\ResourcePackage;
use App\Models\PricePlan;
use Illuminate\Support\Collection;

class ResourcePackageService
{
    /**
     * 获取市场列表（带价格方案）
     */
    public function getMarketList(array $filters = []): Collection
    {
        $query = ResourcePackage::with(['activePricePlans']);

        // 按地区筛选
        if (!empty($filters['region'])) {
            $query->where('region', $filters['region']);
        }

        // 按GPU型号筛选
        if (!empty($filters['gpu_model'])) {
            $query->where('gpu_model', 'like', '%' . $filters['gpu_model'] . '%');
        }

        // 按可购买状态筛选
        if (!empty($filters['stock_status'])) {
            $query->where('stock_status', $filters['stock_status']);
        }

        // 按价格排序
        if (!empty($filters['sort_by_price'])) {
            $query->whereHas('activePricePlans', function ($q) use ($filters) {
                $order = $filters['sort_by_price'] === 'asc' ? 'asc' : 'desc';
                $q->orderBy('price', $order);
            });
        }

        return $query->where('is_active', true)->get();
    }

    /**
     * 获取套餐详情
     */
    public function getPackageDetail(int $packageId): ?ResourcePackage
    {
        return ResourcePackage::with(['activePricePlans'])->find($packageId);
    }

    /**
     * 获取筛选项
     */
    public function getFilterOptions(): array
    {
        return [
            'regions' => ResourcePackage::where('is_active', true)
                ->distinct()
                ->pluck('region')
                ->filter()
                ->values()
                ->toArray(),
            'gpu_models' => ResourcePackage::where('is_active', true)
                ->distinct()
                ->pluck('gpu_model')
                ->filter()
                ->values()
                ->toArray(),
            'stock_statuses' => [
                ['value' => 'available', 'label' => '可购买'],
                ['value' => 'scarce', 'label' => '紧张'],
                ['value' => 'sold_out', 'label' => '售罄'],
            ],
        ];
    }

    /**
     * 计算市场状态
     */
    public function calculateStockStatus(int $packageId): string
    {
        $package = ResourcePackage::find($packageId);

        if (!$package || !$package->is_active) {
            return 'sold_out';
        }

        // TODO: 根据实际库存数量计算
        // 这里应该调用资源池查询能力来获取真实库存
        // 暂时返回available作为示例
        return 'available';
    }
}