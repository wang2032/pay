<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ResourcePackage;
use App\Models\PricePlan;
use App\Models\InstanceImage;
use App\Models\Balance;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 创建资源套餐示例数据
        $packages = [
            [
                'name' => 'GPU 计算型 S',
                'gpu_model' => 'NVIDIA A100',
                'gpu_count' => 1,
                'vram' => 40960,
                'cpu_count' => 4,
                'cpu_model' => 'AMD EPYC 7H12',
                'memory' => 32768,
                'region' => 'us-west',
                'driver_version' => '535.154.05',
                'cuda_version' => '12.2',
                'local_storage' => 500,
                'supports_ssh' => true,
                'supports_jupyterlab' => true,
                'stock_status' => 'available',
            ],
            [
                'name' => 'GPU 计算型 M',
                'gpu_model' => 'NVIDIA A100',
                'gpu_count' => 2,
                'vram' => 81920,
                'cpu_count' => 8,
                'cpu_model' => 'AMD EPYC 7H12',
                'memory' => 65536,
                'region' => 'us-west',
                'driver_version' => '535.154.05',
                'cuda_version' => '12.2',
                'local_storage' => 1000,
                'supports_ssh' => true,
                'supports_jupyterlab' => true,
                'stock_status' => 'available',
            ],
            [
                'name' => 'GPU 计算型 L',
                'gpu_model' => 'NVIDIA A100',
                'gpu_count' => 4,
                'vram' => 163840,
                'cpu_count' => 16,
                'cpu_model' => 'AMD EPYC 7H12',
                'memory' => 131072,
                'region' => 'us-west',
                'driver_version' => '535.154.05',
                'cuda_version' => '12.2',
                'local_storage' => 2000,
                'supports_ssh' => true,
                'supports_jupyterlab' => true,
                'stock_status' => 'scarce',
            ],
            [
                'name' => 'GPU 推理型',
                'gpu_model' => 'NVIDIA L40',
                'gpu_count' => 1,
                'vram' => 46080,
                'cpu_count' => 4,
                'cpu_model' => 'Intel Xeon Gold 6330',
                'memory' => 32768,
                'region' => 'eu-central',
                'driver_version' => '535.154.05',
                'cuda_version' => '12.2',
                'local_storage' => 500,
                'supports_ssh' => true,
                'supports_jupyterlab' => true,
                'stock_status' => 'available',
            ],
        ];

        foreach ($packages as $packageData) {
            $package = ResourcePackage::create($packageData);

            // 为每个套餐创建价格方案
            PricePlan::create([
                'resource_package_id' => $package->id,
                'plan_type' => 'hourly',
                'price' => rand(50, 200) + 0.99,
                'currency' => 'USDT',
                'is_active' => true,
            ]);

            PricePlan::create([
                'resource_package_id' => $package->id,
                'plan_type' => 'daily',
                'price' => rand(300, 1500) + 0.99,
                'currency' => 'USDT',
                'is_active' => true,
            ]);

            PricePlan::create([
                'resource_package_id' => $package->id,
                'plan_type' => 'weekly',
                'price' => rand(1800, 9000) + 0.99,
                'currency' => 'USDT',
                'is_active' => true,
            ]);

            PricePlan::create([
                'resource_package_id' => $package->id,
                'plan_type' => 'monthly',
                'price' => rand(6000, 30000) + 0.99,
                'currency' => 'USDT',
                'is_active' => true,
            ]);
        }

        // 创建镜像示例数据
        $images = [
            [
                'name' => 'PyTorch',
                'version' => '2.1.0',
                'base_system' => 'Ubuntu 22.04',
                'cuda_version' => '12.2',
                'framework' => 'PyTorch 2.1.0 / Python 3.10',
                'compatibility_status' => 'compatible',
            ],
            [
                'name' => 'TensorFlow',
                'version' => '2.14.0',
                'base_system' => 'Ubuntu 22.04',
                'cuda_version' => '12.2',
                'framework' => 'TensorFlow 2.14.0 / Python 3.10',
                'compatibility_status' => 'compatible',
            ],
            [
                'name' => 'JupyterLab',
                'version' => '4.0.0',
                'base_system' => 'Ubuntu 22.04',
                'cuda_version' => '12.2',
                'framework' => 'JupyterLab 4.0 / Python 3.10',
                'compatibility_status' => 'compatible',
            ],
            [
                'name' => 'Deep Learning',
                'version' => '1.0.0',
                'base_system' => 'Ubuntu 22.04',
                'cuda_version' => '11.8',
                'framework' => 'PyTorch 2.0 / TensorFlow 2.13 / Python 3.10',
                'compatibility_status' => 'warning',
            ],
        ];

        foreach ($images as $imageData) {
            InstanceImage::create($imageData);
        }

        // 创建测试余额
        Balance::create([
            'user_id' => 1,
            'amount' => 1000.0000,
            'currency' => 'USDT',
            'status' => 'active',
        ]);
    }
}