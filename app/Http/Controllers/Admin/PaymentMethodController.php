<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentMethodController extends Controller
{
    /**
     * Payment method listing.
     */
    public function index()
    {
        $paymentMethods = DB::table('payment_methods')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(8)
            ->withQueryString();

        return view(
            'admin.payment-methods.index',
            compact('paymentMethods')
        );
    }


    /**
     * Add payment method.
     */
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


        $name = trim($request->name);

        $slug = Str::slug(
            $name,
            '_'
        );


        /*
        |--------------------------------------------------------------------------
        | Make slug unique
        |--------------------------------------------------------------------------
        */

        $originalSlug = $slug;

        $counter = 1;


        while (
            DB::table('payment_methods')
                ->where('slug', $slug)
                ->exists()
        ) {

            $slug =
                $originalSlug .
                '_' .
                $counter;

            $counter++;
        }


        /*
        |--------------------------------------------------------------------------
        | Sort order
        |--------------------------------------------------------------------------
        */

        $sortOrder =
            DB::table('payment_methods')
                ->max('sort_order');

        $sortOrder =
            ($sortOrder ?? 0) + 1;


        /*
        |--------------------------------------------------------------------------
        | Insert
        |--------------------------------------------------------------------------
        */

        $id = DB::table(
            'payment_methods'
        )->insertGetId([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
            'sort_order' => $sortOrder,
            'created_at' => now(),
            'updated_at' => now(),
        ]);


        $method = DB::table(
            'payment_methods'
        )
            ->where('id', $id)
            ->first();


        return response()->json([
            'status' => true,
            'message' =>
                'Payment method added successfully.',
            'data' => $method,
        ]);
    }


    /**
     * Toggle active/inactive.
     */
    public function toggle(int $id)
    {
        $method = DB::table(
            'payment_methods'
        )
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


        DB::table(
            'payment_methods'
        )
            ->where('id', $id)
            ->update([
                'is_active' =>
                    $newStatus,

                'updated_at' =>
                    now(),
            ]);


        return response()->json([
            'status' => true,

            'message' =>
                $newStatus
                    ? 'Payment method activated.'
                    : 'Payment method deactivated.',

            'is_active' =>
                $newStatus,
        ]);
    }


    /**
     * Update payment method.
     */
    public function update(
        Request $request,
        int $id
    ) {
        $method = DB::table(
            'payment_methods'
        )
            ->where('id', $id)
            ->first();


        if (!$method) {

            return response()->json([
                'status' => false,
                'message' =>
                    'Payment method not found.',
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
            ],
        ]);


        $name =
            trim($request->name);


        /*
        |--------------------------------------------------------------------------
        | Duplicate name check
        |--------------------------------------------------------------------------
        */

        $duplicate =
            DB::table('payment_methods')
                ->where('name', $name)
                ->where('id', '!=', $id)
                ->exists();


        if ($duplicate) {

            return response()->json([
                'status' => false,

                'message' =>
                    'Payment method name already exists.',
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Generate new slug
        |--------------------------------------------------------------------------
        */

        $slug =
            Str::slug(
                $name,
                '_'
            );


        $originalSlug =
            $slug;

        $counter = 1;


        while (
            DB::table('payment_methods')
                ->where('slug', $slug)
                ->where('id', '!=', $id)
                ->exists()
        ) {

            $slug =
                $originalSlug .
                '_' .
                $counter;

            $counter++;
        }


        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

        DB::table(
            'payment_methods'
        )
            ->where('id', $id)
            ->update([
                'name' => $name,
                'slug' => $slug,
                'updated_at' => now(),
            ]);


        return response()->json([
            'status' => true,

            'message' =>
                'Payment method updated successfully.',

            'data' => [
                'id' => $id,
                'name' => $name,
                'slug' => $slug,
            ],
        ]);
    }


    /**
     * Delete payment method.
     */
    public function destroy(int $id)
    {
        $method = DB::table(
            'payment_methods'
        )
            ->where('id', $id)
            ->first();


        if (!$method) {

            return response()->json([
                'status' => false,
                'message' =>
                    'Payment method not found.',
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Check if driver uses this method
        |--------------------------------------------------------------------------
        */

        $usedByDriver =
            DB::table(
                'driver_payment_methods'
            )
                ->where(
                    'payment_method_id',
                    $id
                )
                ->exists();


        if ($usedByDriver) {

            return response()->json([
                'status' => false,

                'message' =>
                    'This payment method is assigned to one or more drivers. Please deactivate it instead of deleting it.',
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Delete
        |--------------------------------------------------------------------------
        */

        DB::table(
            'payment_methods'
        )
            ->where('id', $id)
            ->delete();


        return response()->json([
            'status' => true,

            'message' =>
                'Payment method deleted successfully.',
        ]);
    }
}