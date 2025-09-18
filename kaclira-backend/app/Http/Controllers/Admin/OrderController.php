<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Display a listing of orders
     */
    public function index(Request $request): JsonResponse
    {
        // Ensure user is authenticated
        $user = Auth::user();
        if (!$user) {
            \Log::warning('Admin order listing failed: No authenticated user');
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 401);
        }

        try {
            $query = Order::query();

            // Apply filters
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                      ->orWhere('customer_name', 'like', "%{$search}%")
                      ->orWhere('customer_email', 'like', "%{$search}%");
                });
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $orders
            ]);
        } catch (\Exception $e) {
            \Log::error('Order listing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created order
     */
    public function store(Request $request): JsonResponse
    {
        // Ensure user is authenticated
        $user = Auth::user();
        if (!$user) {
            \Log::warning('Admin order creation failed: No authenticated user');
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'total_amount' => 'required|numeric|min:0',
            'shipping_address' => 'required|string',
            'status' => 'required|string|in:pending,processing,shipped,delivered,cancelled',
            'payment_status' => 'nullable|string|in:pending,paid,failed,refunded',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            \Log::warning('Order validation failed', [
                'errors' => $validator->errors()->toArray(),
                'admin_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $orderData = $request->all();
            $orderData['order_number'] = 'ORD-' . strtoupper(uniqid());
            
            $order = Order::create($orderData);

            \Log::info('Order created successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'admin_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $order
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_id' => Auth::id(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified order
     */
    public function show(Order $order): JsonResponse
    {
        // Ensure user is authenticated
        $user = Auth::user();
        if (!$user) {
            \Log::warning('Admin order view failed: No authenticated user');
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 401);
        }

        try {
            return response()->json([
                'success' => true,
                'data' => $order
            ]);
        } catch (\Exception $e) {
            \Log::error('Order view failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_id' => Auth::id(),
                'order_id' => $order->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified order
     */
    public function update(Request $request, Order $order): JsonResponse
    {
        // Ensure user is authenticated
        $user = Auth::user();
        if (!$user) {
            \Log::warning('Admin order update failed: No authenticated user');
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'customer_name' => 'sometimes|required|string|max:255',
            'customer_email' => 'sometimes|required|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'total_amount' => 'sometimes|required|numeric|min:0',
            'shipping_address' => 'sometimes|required|string',
            'status' => 'sometimes|required|string|in:pending,processing,shipped,delivered,cancelled',
            'payment_status' => 'nullable|string|in:pending,paid,failed,refunded',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            \Log::warning('Order update validation failed', [
                'errors' => $validator->errors()->toArray(),
                'admin_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order->update($request->all());

            \Log::info('Order updated successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'admin_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully',
                'data' => $order
            ]);
        } catch (\Exception $e) {
            \Log::error('Order update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_id' => Auth::id(),
                'order_id' => $order->id,
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified order
     */
    public function destroy(Order $order): JsonResponse
    {
        // Ensure user is authenticated
        $user = Auth::user();
        if (!$user) {
            \Log::warning('Admin order deletion failed: No authenticated user');
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 401);
        }

        try {
            $order->delete();

            \Log::info('Order deleted successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'admin_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Order deletion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_id' => Auth::id(),
                'order_id' => $order->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        // Ensure user is authenticated
        $user = Auth::user();
        if (!$user) {
            \Log::warning('Admin order status update failed: No authenticated user');
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,processing,shipped,delivered,cancelled'
        ]);

        if ($validator->fails()) {
            \Log::warning('Order status update validation failed', [
                'errors' => $validator->errors()->toArray(),
                'admin_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order->update(['status' => $request->status]);

            \Log::info('Order status updated successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'new_status' => $request->status,
                'admin_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'data' => $order
            ]);
        } catch (\Exception $e) {
            \Log::error('Order status update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_id' => Auth::id(),
                'order_id' => $order->id,
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}