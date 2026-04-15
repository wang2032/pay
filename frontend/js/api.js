/**
 * API 模块 - 与后端 API 交互
 */

const API_BASE = '/api';

const Api = {
    /**
     * 发送请求
     */
    async request(endpoint, options = {}) {
        const url = `${API_BASE}${endpoint}`;
        const config = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...options.headers,
            },
            ...options,
        };

        if (config.body && typeof config.body === 'object') {
            config.body = JSON.stringify(config.body);
        }

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || '请求失败');
            }

            return data;
        } catch (error) {
            console.error(`API Error [${endpoint}]:`, error);
            throw error;
        }
    },

    // ========== 市场相关 ==========

    /**
     * 获取市场列表
     */
    async getMarketList(filters = {}) {
        const query = new URLSearchParams(filters).toString();
        return this.request(`/market${query ? '?' + query : ''}`);
    },

    /**
     * 获取套餐详情
     */
    async getPackageDetail(id) {
        return this.request(`/market/packages/${id}`);
    },

    /**
     * 获取筛选项
     */
    async getFilters() {
        return this.request('/market/filters');
    },

    // ========== 实例相关 ==========

    /**
     * 获取创建页初始化数据
     */
    async getCreateInit(packageId) {
        return this.request(`/instance/create/${packageId}/init`);
    },

    /**
     * 校验镜像兼容性
     */
    async checkImageCompatibility(imageId, packageId) {
        return this.request('/instance/check-image', {
            method: 'POST',
            body: { image_id: imageId, package_id: packageId },
        });
    },

    /**
     * 校验余额
     */
    async checkBalance(userId, pricePlanId) {
        return this.request('/instance/check-balance', {
            method: 'POST',
            body: { user_id: userId, price_plan_id: pricePlanId },
        });
    },

    /**
     * 创建实例
     */
    async createInstance(data) {
        return this.request('/instance', {
            method: 'POST',
            body: data,
        });
    },

    /**
     * 获取创建状态
     */
    async getCreateStatus(taskId) {
        return this.request(`/instance/create/${taskId}/status`);
    },

    /**
     * 取消创建
     */
    async cancelCreate(taskId) {
        return this.request(`/instance/create/${taskId}`, {
            method: 'DELETE',
        });
    },

    /**
     * 获取实例详情
     */
    async getInstanceDetail(instanceId) {
        return this.request(`/instance/${instanceId}`);
    },

    /**
     * 停止实例
     */
    async stopInstance(instanceId) {
        return this.request(`/instance/${instanceId}/stop`, {
            method: 'POST',
        });
    },

    /**
     * 启动实例
     */
    async startInstance(instanceId) {
        return this.request(`/instance/${instanceId}/start`, {
            method: 'POST',
        });
    },

    /**
     * 删除实例
     */
    async deleteInstance(instanceId) {
        return this.request(`/instance/${instanceId}`, {
            method: 'DELETE',
        });
    },

    /**
     * 续费实例
     */
    async renewInstance(instanceId, planType = 'monthly') {
        return this.request(`/instance/${instanceId}/renew`, {
            method: 'POST',
            body: { plan_type: planType },
        });
    },

    // ========== 账单相关 ==========

    /**
     * 获取余额
     */
    async getBalance(userId) {
        return this.request(`/bill/balance/${userId}`);
    },

    /**
     * 获取账单流水
     */
    async getBills(userId, limit = 20, offset = 0) {
        return this.request(`/bill/records/${userId}?limit=${limit}&offset=${offset}`);
    },

    /**
     * 获取订单
     */
    async getOrders(userId, limit = 20, offset = 0) {
        return this.request(`/bill/orders/${userId}?limit=${limit}&offset=${offset}`);
    },
};

// 导出
window.Api = Api;