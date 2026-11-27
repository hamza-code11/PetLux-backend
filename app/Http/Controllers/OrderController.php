<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validation
        $request->validate([
            'first_name'          => 'required|string|max:255',
            'last_name'           => 'required|string|max:255',
            'email'               => 'required|email|max:255',
            'phone'               => 'required|string|max:255',
            'address'             => 'required|string',
            'city'                => 'required|string|max:255',
            'postal_code'         => 'required|string|max:255',
            'country'             => 'required|string|max:255',
            'payment_screenshot'  => 'required|file|mimes:jpeg,png,jpg,webp,svg|max:5120',
            'cart_items'          => 'required|array|min:1',
            'cart_items.*.product_id' => 'required|integer',
            'cart_items.*.quantity'   => 'required|integer|min:1',
            'cart_items.*.price'      => 'required|numeric',
        ]);

        try {
            DB::beginTransaction();

            // 2. Image Upload
            $imagePath = null;
            if ($request->hasFile('payment_screenshot')) {
                $imagePath = $request->file('payment_screenshot')->store('payments', 'public');
            }

            // 3. Calculate Financials
            $subtotal = 0;
            foreach ($request->cart_items as $item) {
                $subtotal += ($item['price'] * $item['quantity']);
            }
            
            $shippingFee = (float) Setting::getValue('shipping_charges', 0);
            $total = $subtotal + $shippingFee;

            // 4. Create Order
            $order = Order::create([
                'order_number'       => 'ORD-' . strtoupper(Str::random(8)),
                'user_id'            => $request->user() ? $request->user()->id : null,
                'first_name'         => $request->first_name,
                'last_name'          => $request->last_name,
                'email'              => $request->email,
                'phone'              => $request->phone,
                'address'            => $request->address,
                'city'               => $request->city,
                'postal_code'        => $request->postal_code,
                'country'            => $request->country,
                'payment_screenshot' => $imagePath,
                'status'             => 'pending',
                'subtotal'           => $subtotal,
                'shipping_fee'       => $shippingFee,
                'total'              => $total,
            ]);

            // 5. Create Order Items
            foreach ($request->cart_items as $item) {
                $order->items()->create([
                    'product_id'   => $item['product_id'],
                    'variant_id'   => $item['variant_id'] ?? null,
                    'product_name' => $item['product_name'] ?? 'Product',
                    'variant_name' => $item['variant_name'] ?? null,
                    'price'        => $item['price'],
                    'quantity'     => $item['quantity'],
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Order placed successfully.',
                'order_id' => $order->id,
                'order_number' => $order->order_number
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to place order.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // 1. List All Orders (For Admin)
    public function index()
    {
        $orders = Order::with('items')->latest()->get();

        $formattedOrders = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->first_name . ' ' . $order->last_name,
                'customer_email' => $order->email,
                'customer_phone' => $order->phone,
                'total' => $order->total,
                'status' => $order->status,
                'items_count' => $order->items->count(),
                'date' => $order->created_at->format('Y-m-d'),
                // Order details page ke liye baaki data bhi bhej rahe hain
                'shipping_address' => $order->address,
                'shipping_city' => $order->city,
                'shipping_postal_code' => $order->postal_code,
                'shipping_country' => $order->country,
                'subtotal' => $order->subtotal,
                'shipping_fee' => $order->shipping_fee,
                'payment_screenshot' => $order->payment_screenshot,
                'items' => $order->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->product_name,
                        'variant_name' => $item->variant_name,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                    ];
                })
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $formattedOrders
        ]);
    }

    // 2. Show Single Order
    public function show($id)
    {
        $order = Order::with('items')->findOrFail($id);

        $formattedOrder = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->first_name . ' ' . $order->last_name,
            'customer_email' => $order->email,
            'customer_phone' => $order->phone,
            'total' => $order->total,
            'status' => $order->status,
            'date' => $order->created_at->format('Y-m-d'),
            'shipping_address' => $order->address,
            'shipping_city' => $order->city,
            'shipping_postal_code' => $order->postal_code,
            'shipping_country' => $order->country,
            'subtotal' => $order->subtotal,
            'shipping_fee' => $order->shipping_fee,
            'payment_screenshot' => $order->payment_screenshot,
            'items' => $order->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->product_name,
                    'variant_name' => $item->variant_name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ];
            })
        ];

        return response()->json([
            'status' => true,
            'data' => $formattedOrder
        ]);
    }

    // 3. Update Order Status
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled'
        ]);

        $order = Order::findOrFail($id);
        $order->update(['status' => $request->status]);

        return response()->json([
            'status' => true,
            'message' => 'Order status updated successfully.'
        ]);
    }

    // 4. Delete Order
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        
        // Image delete karein agar exist karti hai
        if ($order->payment_screenshot && Storage::disk('public')->exists($order->payment_screenshot)) {
            Storage::disk('public')->delete($order->payment_screenshot);
        }
        
        $order->delete();

        return response()->json([
            'status' => true,
            'message' => 'Order deleted successfully.'
        ]);
    }



    // Customer ke apne orders
    public function myOrders(Request $request)
    {
        $orders = Order::with('items')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        $formattedOrders = $orders->map(function ($order) {
            return [
                'id'                   => $order->id,
                'order_number'         => $order->order_number,
                'customer_name'        => $order->first_name . ' ' . $order->last_name,
                'customer_email'       => $order->email,
                'customer_phone'       => $order->phone,
                'total'                => $order->total,
                'status'               => $order->status,
                'items_count'          => $order->items->count(),
                'date'                 => $order->created_at->format('Y-m-d'),
                'shipping_address'     => $order->address,
                'shipping_city'        => $order->city,
                'shipping_postal_code' => $order->postal_code,
                'shipping_country'     => $order->country,
                'subtotal'             => $order->subtotal,
                'shipping_fee'         => $order->shipping_fee,
                'payment_screenshot'   => $order->payment_screenshot,
                'items' => $order->items->map(function ($item) {
                    return [
                        'id'           => $item->id,
                        'name'         => $item->product_name,
                        'variant_name' => $item->variant_name,
                        'quantity'     => $item->quantity,
                        'price'        => $item->price,
                    ];
                }),
            ];
        });

        return response()->json([
            'status' => true,
            'data'   => $formattedOrders,
        ]);
    }



    public function trackByNumber(Request $request, $orderNumber)
{
    $order = Order::with('items')
        ->where('order_number', $orderNumber)
        ->first();

    if (!$order) {
        return response()->json([
            'status'  => false,
            'message' => 'Order not found. Please check the order number.',
        ], 404);
    }

    // Agar user logged in hai to sirf apna order dekh sake
    if ($request->user() && $order->user_id && $order->user_id !== $request->user()->id) {
        return response()->json([
            'status'  => false,
            'message' => 'Order not found.',
        ], 404);
    }

    return response()->json([
        'status' => true,
        'data'   => [
            'id'                   => $order->id,
            'order_number'         => $order->order_number,
            'status'               => $order->status,
            'date'                 => $order->created_at->format('Y-m-d'),
            'total'                => $order->total,
            'subtotal'             => $order->subtotal,
            'shipping_fee'         => $order->shipping_fee,
            'customer_name'        => $order->first_name . ' ' . $order->last_name,
            'shipping_city'        => $order->city,
            'shipping_country'     => $order->country,
            'items' => $order->items->map(fn ($i) => [
                'id'           => $i->id,
                'name'         => $i->product_name,
                'variant_name' => $i->variant_name,
                'quantity'     => $i->quantity,
                'price'        => $i->price,
            ]),
        ],
    ]);
}





}
