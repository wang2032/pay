<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Balance extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'status', // active, frozen, closed
    ];

    protected $casts = [
        'amount' => 'decimal:4',
    ];

    /**
     * 获取用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 判断余额是否充足
     */
    public function isEnough(float $amount): bool
    {
        return $this->amount >= $amount;
    }

    /**
     * 扣减余额
     */
    public function deduct(float $amount): bool
    {
        if (!$this->isEnough($amount)) {
            return false;
        }
        $this->amount -= $amount;
        return $this->save();
    }

    /**
     * 增加余额
     */
    public function add(float $amount): bool
    {
        $this->amount += $amount;
        return $this->save();
    }
}