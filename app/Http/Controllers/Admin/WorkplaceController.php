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
        ]);


        DB::beginTransaction();

        try {

            $workplaceId = DB::table(
                'workplaces'
            )->insertGetId([
                'company_name' =>
                    $validated['company_name'],

                'is_active' => true,

                'created_at' => now(),
                'updated_at' => now(),
            ]);


            DB::table(
                'workplace_branches'
            )->insert([
                'workplace_id' =>
                    $workplaceId,

                'address' =>
                    $validated['branch_address'],

                'is_active' => true,

                'created_at' => now(),
                'updated_at' => now(),
            ]);


            DB::commit();


            return response()->json([
                'status' => true,
                'message' =>
                    'Company added successfully.',
            ]);


        } catch (\Throwable $e) {

            DB::rollBack();

            \Log::error(
                'Workplace create error',
                [
                    'error' =>
                        $e->getMessage()
                ]
            );


            return response()->json([
                'status' => false,
                'message' =>
                    'Unable to add company.',
            ], 500);
        }
    }


    public function storeBranch(
        Request $request,
        int $id
    ) {
        $validated = $request->validate([
            'branches' => [
                'required',
                'array',
                'min:1',
            ],

            'branches.*' => [
                'required',
                'string',
                'max:255',
            ],
        ]);


        $workplace = DB::table('workplaces')
            ->where('id', $id)
            ->first();


        if (!$workplace) {

            return response()->json([
                'status' => false,
                'message' => 'Company not found.',
            ], 404);
        }


        $insertData = [];


        foreach (
            $validated['branches']
            as $address
        ) {

            $insertData[] = [
                'workplace_id' => $id,

                'address' =>
                    trim($address),

                'is_active' =>
                    true,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ];

        }


        DB::table(
            'workplace_branches'
        )->insert($insertData);


        return response()->json([
            'status' => true,
            'message' =>
                count($insertData) .
                ' branch(es) added successfully.',
        ]);
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