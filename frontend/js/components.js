/**
 * 通用组件 - Vue 3 兼容版本
 */

window.Components = {
    /**
     * 显示 Toast 通知
     */
    toast(message, type = 'success') {
        const existing = document.querySelector('.toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => toast.remove(), 3000);
    },

    /**
     * 显示确认对话框
     */
    confirm(title, message) {
        return new Promise((resolve) => {
            const overlay = document.createElement('div');
            overlay.className = 'modal-overlay';

            overlay.innerHTML = `
                <div class="modal">
                    <h3 class="modal-title">${title}</h3>
                    <p class="modal-message">${message}</p>
                    <div class="modal-actions">
                        <button class="btn btn-ghost" data-action="cancel">取消</button>
                        <button class="btn btn-danger" data-action="confirm">确认</button>
                    </div>
                </div>
            `;

            document.body.appendChild(overlay);

            const close = (result) => {
                overlay.remove();
                resolve(result);
            };

            overlay.querySelector('[data-action="cancel"]').onclick = () => close(false);
            overlay.querySelector('[data-action="confirm"]').onclick = () => close(true);
            overlay.onclick = (e) => {
                if (e.target === overlay) close(false);
            };
        });
    },

    /**
     * 复制文本
     */
    async copyText(text) {
        try {
            await navigator.clipboard.writeText(text);
            this.toast('已复制到剪贴板');
            return true;
        } catch (err) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            this.toast('已复制到剪贴板');
            return true;
        }
    },

    /**
     * 格式化金额
     */
    formatMoney(amount, currency = 'USDT') {
        return `${parseFloat(amount).toFixed(2)} ${currency}`;
    },

    /**
     * 格式化日期时间
     */
    formatDateTime(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleString('zh-CN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        });
    },

    /**
     * 格式化相对时间
     */
    formatRelativeTime(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;

        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) return '刚刚';
        if (minutes < 60) return `${minutes} 分钟前`;
        if (hours < 24) return `${hours} 小时前`;
        if (days < 30) return `${days} 天前`;
        return this.formatDateTime(dateString);
    },
};