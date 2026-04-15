# 算力市场轻量版平台

基于 Paymenter 二开的算力市场平台。

## 功能特性

- 资源套餐管理（GPU型号、数量、显存、CPU、内存等）
- 多种价格方案（按量/包天/包周/包月）
- 实例创建与管理（SSH/JupyterLab接入）
- 计费与余额管理
- 实例生命周期管理（启动/停止/删除/续费）

## 技术栈

- PHP 8.2+
- Laravel 11
- MySQL

## 安装

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

## API 接口

### 市场接口
- `GET /api/market` - 获取市场列表
- `GET /api/market/filters` - 获取筛选项
- `GET /api/market/packages/{id}` - 获取套餐详情

### 实例接口
- `GET /api/instance/create/{packageId}/init` - 创建页初始化
- `POST /api/instance/check-image` - 校验镜像兼容性
- `POST /api/instance/check-balance` - 校验余额
- `POST /api/instance` - 提交创建
- `GET /api/instance/create/{taskId}/status` - 创建任务状态
- `DELETE /api/instance/create/{taskId}` - 取消创建
- `GET /api/instance/{instanceId}` - 实例详情
- `POST /api/instance/{instanceId}/stop` - 停止实例
- `POST /api/instance/{instanceId}/start` - 启动实例
- `DELETE /api/instance/{instanceId}` - 删除实例
- `POST /api/instance/{instanceId}/renew` - 续费

### 账单接口
- `GET /api/bill/balance/{userId}` - 获取余额
- `GET /api/bill/records/{userId}` - 获取账单流水
- `GET /api/bill/orders/{userId}` - 获取订单

## 状态机

### 实例状态
- pending, creating, running, stopped, failed, cancelled, expired, suspended, error, terminated

### 计费状态
- not_started, billing_active, billing_stopped, period_ended, payment_insufficient, closed

## License

MIT