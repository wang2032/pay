<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Balance;
use App\Models\BillRecord;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillController extends Controller
{
    /**
     * 获取余额
     */
    public function balance(int $userId): JsonResponse
    {
        $balance = Balance::where('user_id', $userId)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'amount' => $balance ? $balance->amount : 0,
                'currency' => $balance ? $balance->currency : 'USDT',
                'status' => $balance ? $balance->status : 'active',
            ],
        ]);
    }

    /**
     * 获取账单流水
     */
    public function bills(int $userId, Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);
        $offset = $request->input('offset', 0);

        $query = BillRecord::where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        $total = $query->count();
        $records = $query->skip($offset)->take($limit)->get();

        $data = $records->map(function ($record) {
            return [
                'id' => $record->id,
                'type' => $record->type,
                'type_text' => $record->type_text,
                'amount' => $record->amount,
                'currency' => $record->currency,
                'balance_before' => $record->balance_before,
                'balance_after' => $record->balance_after,
                'description' => $record->description,
                'created_at' => $record->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);
    }

    /**
     * 获取订单
     */
    public function orders(int $userId, Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);
        $offset = $request->input('offset', 0);

        $query = Order::where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        $total = $query->count();
        $records = $query->skip($offset)->take($limit)->get();

        $data = $records->map(function ($record) {
            return [
                'id' => $record->id,
                'order_no' => $record->order_no,
                'order_type' => $record->order_type,
                'order_type_text' => $record->order_type_text,
                'amount' => $record->amount,
                'currency' => $record->currency,
                'status' => $record->status,
                'status_text' => $record->status_text,
                'paid_at' => $record->paid_at,
                'created_at' => $record->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);
    }
}