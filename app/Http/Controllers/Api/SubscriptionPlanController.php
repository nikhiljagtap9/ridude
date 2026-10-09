<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanController extends Controller
{
    public function index()
    {
        try {

            $plans = DB::table('subscription_plans')
                ->where('is_active', 1)
                ->orderBy('sort_order', 'asc')
                ->get([
                    'id',
                    'name',
                    'slug',
                    'price',
                    'billing_cycle',
                    'trial_days',
                    'currency'
                ]);

            return response()->json([
                'status' => true,
                'message' => 'Subscription plans fetched successfully.',
                'data' => $plans
            ], 200);

        } catch (\Throwable $e) {

            \Log::error('Subscription Plan API Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Unable to fetch subscription plans.'
            ], 500);
        }
    }

    public function paymentMethods()
    {
        try {

            $paymentMethods = DB::table('payment_methods')
                ->where('is_active', 1)
                ->orderBy('sort_order', 'asc')
                ->get([
                    'id',
                    'name',
                    'slug',
                    'icon'
                ]);

            return response()->json([
                'status' => true,
                'message' => 'Payment methods fetched successfully.',
                'data' => $paymentMethods
            ], 200);

        } catch (\Throwable $e) {

            \Log::error('Payment Method API Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Unable to fetch payment methods.'
            ], 500);
        }
    }
}