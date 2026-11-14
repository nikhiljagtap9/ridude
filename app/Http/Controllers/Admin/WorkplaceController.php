<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkplaceController extends Controller
{
    public function index()
    {
        $workplaces = DB::table('workplaces')
            ->orderBy('company_name')
            ->paginate(10);

        $workplaceIds = $workplaces
            ->pluck('id')
            ->toArray();

        $branches = DB::table('workplace_branches')
            ->whereIn('workplace_id', $workplaceIds)
            ->orderBy('id')
            ->get()
            ->groupBy('workplace_id');

        return view(
            'admin.workplaces.index',
            compact(
                'workplaces',
                'branches'
            )
        );
    }


    public function store(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            'company_name' => [
                'required',
                'string',
                'max:150',
            ],

            'branch_address' => [
                'required',
                'string',
                'max:255',
            ],

            'latitude' => [
                'required',
                'numeric',
                'between:-90,90',
            ],

            'longitude' => [
                'required',
                'numeric',
                'between:-180,180',
            ],
        ]);


        DB::beginTransaction();


        try {

            /*
            |--------------------------------------------------------------------------
            | Create Workplace
            |--------------------------------------------------------------------------
            */

            $workplaceId =
                DB::table(
                    'workplaces'
                )
                ->insertGetId([

                    'company_name' =>
                        trim(
                            $validated[
                                'company_name'
                            ]
                        ),

                    'is_active' =>
                        true,

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);


            /*
            |--------------------------------------------------------------------------
            | Create First Branch
            |--------------------------------------------------------------------------
            */

            $branchId =
                DB::table(
                    'workplace_branches'
                )
                ->insertGetId([

                    'workplace_id' =>
                        $workplaceId,

                    'address' =>
                        trim(
                            $validated[
                                'branch_address'
                            ]
                        ),

                    'latitude' =>
                        $validated[
                            'latitude'
                        ],

                    'longitude' =>
                        $validated[
                            'longitude'
                        ],

                    'is_active' =>
                        true,

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);


            /*
            |--------------------------------------------------------------------------
            | Commit
            |--------------------------------------------------------------------------
            */

            DB::commit();


            /*
            |--------------------------------------------------------------------------
            | Return Created Data
            |--------------------------------------------------------------------------
            */

            $workplace =
                DB::table(
                    'workplaces as w'
                )

                ->join(
                    'workplace_branches as wb',
                    'wb.workplace_id',
                    '=',
                    'w.id'
                )

                ->where(
                    'w.id',
                    $workplaceId
                )

                ->where(
                    'wb.id',
                    $branchId
                )

                ->select(

                    'w.id as workplace_id',

                    'w.company_name',

                    'wb.id as branch_id',

                    'wb.address',

                    'wb.latitude',

                    'wb.longitude'
                )

                ->first();


            return response()->json([

                'status' =>
                    true,

                'message' =>
                    'Company added successfully.',

                'data' =>
                    $workplace,
            ]);


        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Rollback
            |--------------------------------------------------------------------------
            */

            if (
                DB::transactionLevel()
                > 0
            ) {

                DB::rollBack();
            }


            /*
            |--------------------------------------------------------------------------
            | Log Error
            |--------------------------------------------------------------------------
            */

            \Log::error(
                'Workplace create error',
                [
                    'error' =>
                        $e->getMessage(),

                    'file' =>
                        $e->getFile(),

                    'line' =>
                        $e->getLine(),
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | Error Response
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'status' =>
                    false,

                'message' =>
                    'Unable to add company.',

                'error' =>
                    $e->getMessage(),

            ], 500);
        }
    }


    public function storeBranches(
    Request $request,
    int $workplaceId
    ) {
        $validated =
            $request->validate([

                'branches' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'branches.*.address' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'branches.*.latitude' => [
                    'required',
                    'numeric',
                    'between:-90,90',
                ],

                'branches.*.longitude' => [
                    'required',
                    'numeric',
                    'between:-180,180',
                ],
            ]);


        $workplace =
            DB::table('workplaces')

                ->where(
                    'id',
                    $workplaceId
                )

                ->where(
                    'is_active',
                    1
                )

                ->first();


        if (!$workplace) {

            return response()->json([
                'status' => false,
                'message' =>
                    'Workplace not found.',
            ], 404);
        }


        DB::beginTransaction();


        try {

            foreach (
                $validated['branches']
                as $branch
            ) {

                DB::table(
                    'workplace_branches'
                )

                ->insert([

                    'workplace_id' =>
                        $workplaceId,

                    'address' =>
                        trim(
                            $branch[
                                'address'
                            ]
                        ),

                    'latitude' =>
                        $branch[
                            'latitude'
                        ],

                    'longitude' =>
                        $branch[
                            'longitude'
                        ],

                    'is_active' =>
                        true,

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);
            }


            DB::commit();


            return response()->json([
                'status' => true,
                'message' =>
                    'Branches added successfully.',
            ]);


        } catch (\Throwable $e) {

            DB::rollBack();


            \Log::error(
                'Workplace branch create error',
                [
                    'workplace_id' =>
                        $workplaceId,

                    'error' =>
                        $e->getMessage(),
                ]
            );


            return response()->json([
                'status' => false,
                'message' =>
                    'Unable to add branches.',
                'error' =>
                    $e->getMessage(),
            ], 500);
        }
    }


    public function deleteBranch(int $id)
    {
        $branch = DB::table(
            'workplace_branches'
        )
        ->where('id', $id)
        ->first();


        if (!$branch) {

            return response()->json([
                'status' => false,
                'message' =>
                    'Branch not found.',
            ], 404);
        }


        DB::table(
            'workplace_branches'
        )
        ->where('id', $id)
        ->delete();


        return response()->json([
            'status' => true,
            'message' =>
                'Branch removed successfully.',
        ]);
    }
}