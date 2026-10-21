<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $paymentMethods = DB::table('payment_methods')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view(
            'admin.payment-methods.index',
            compact('paymentMethods')
        );
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'unique:payment_methods,name',
            ],
        ]);

        $slug = Str::slug(
            $request->name,
            '_'
        );

        /*
         * Ensure slug is unique
         */
        $originalSlug = $slug;
        $counter = 1;

        while (
            DB::table('payment_methods')
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug =
                $originalSlug . '_' . $counter;

            $counter++;
        }


        $id = DB::table('payment_methods')
            ->insertGetId([
                'name' => trim(
                    $request->name
                ),

                'slug' => $slug,

                'is_active' => true,

                'sort_order' => (
                    DB::table('payment_methods')
                        ->max('sort_order') ?? 0
                ) + 1,

                'created_at' => now(),

                'updated_at' => now(),
            ]);


        $method = DB::table('payment_methods')
            ->where('id', $id)
            ->first();


        return response()->json([
            'status' => true,
            'message' =>
                'Payment method added successfully.',
            'data' => $method,
        ]);
    }


    public function toggle(int $id)
    {
        $method = DB::table('payment_methods')
            ->where('id', $id)
            ->first();


        if (!$method) {

            return response()->json([
                'status' => false,
                'message' =>
                    'Payment method not found.',
            ], 404);
        }


        $newStatus =
            !$method->is_active;


        DB::table('payment_methods')
            ->where('id', $id)
            ->update([
                'is_active' => $newStatus,
                'updated_at' => now(),
            ]);


        return response()->json([
            'status' => true,

            'message' =>
                $newStatus
                    ? 'Payment method activated.'
                    : 'Payment method deactivated.',

            'is_active' => $newStatus,
        ]);
    }
}