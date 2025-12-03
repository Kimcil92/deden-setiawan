<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }
    public function store(Request $request, OrderService $orderService)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'nullable|string',
                'items' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
            $data = $orderService->createOrder(
                userId: $request->user()->id,
                payload: $validator->validate()
            );

            return response()->json([
                'status' => 'success',
                'data' => $data
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
