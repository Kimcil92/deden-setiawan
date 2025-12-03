<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createOrder(?string $userId, array $payload): mixed
    {
        DB::beginTransaction();
        try {
            $total = 0;
            $items = $payload['items'];
            foreach ($items as $item) {
                $product = Product::where('id', $item['product_id']);
                if (!$product) {
                    return response()->json(['error' => 'Product not found'], 404);
                }

                if ($product->stock <= 0) {
                    DB::rollBack();
                    return response()->json(['error' => 'Out of stock'], 400);
                }

                Product::where('id', $product->id)
                    ->update(['stock' => $product->stock - $item['quantity']]);

                $total += $product->price * $item['quantity'];
            }

            $orderId = Order::create([
                'user_id' => $userId,
                'total' => $total,
                'created_at' => now(),
            ]);

            OrderItem::create([
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $product->price,
            ]);

            DB::commit();
            return response()->json(['order_id' => $orderId, 'total' => $total], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
