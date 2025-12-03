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
        return DB::transaction(function () use ($userId, $payload) {

            $total = 0;
            $items = $payload['items'];

            $order = Order::create([
                'user_id' => $userId,
                'total'   => 0,
            ]);

            foreach ($items as $item) {

                $product = Product::find($item['product_id']);

                if (!$product) {
                    throw new \Exception("Product ID {$item['product_id']} not found");
                }

                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Stock not enough for product ID {$item['product_id']}");
                }

                $product->decrement('stock', $item['quantity']);

                $subTotal = $product->price * $item['quantity'];
                $total += $subTotal;

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'price'      => $product->price,
                ]);
            }

            $order->update(['total' => $total]);

            return [
                'order_id' => $order->id,
                'total'    => $total,
            ];
        });
    }
}
