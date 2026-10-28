<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\RiderAllocationService;

class AutoAllocateRiders extends Command
{
    protected $signature =
        'riders:auto-allocate';


    protected $description =
        'Allocate pending rider requests to best matching driver trips.';


    public function handle(
        RiderAllocationService $allocationService
    ): int {

        $pendingRequests =
            DB::table(
                'trip_riders'
            )

            ->whereNull(
                'trip_id'
            )

            ->where(
                'allocation_status',
                'pending'
            )

            ->where(
                'booking_status',
                '!=',
                'cancelled'
            )

            ->whereNotNull(
                'requested_trip_date'
            )

            ->whereNotNull(
                'requested_direction'
            )

            ->whereDate(
                'requested_trip_date',
                '>=',
                now()->toDateString()
            )

            ->orderBy(
                'created_at'
            )

            ->get();


        if (
            $pendingRequests
                ->isEmpty()
        ) {

            $this->info(
                'No pending rider requests found.'
            );

            return
                self::SUCCESS;
        }


        foreach (
            $pendingRequests
            as $request
        ) {

            try {

                $result =
                    $allocationService
                        ->allocateBestTrip(
                            $request->id
                        );


                if (
                    $result[
                        'status'
                    ]
                ) {

                    $this->info(
                        'Rider allocated: '
                        . $request->id
                    );


                    Log::info(
                        'Rider auto allocated',
                        [
                            'trip_rider_id' =>
                                $request->id,

                            'trip_id' =>
                                $result[
                                    'trip_id'
                                ]
                                ?? null,

                            'driver_id' =>
                                $result[
                                    'driver_id'
                                ]
                                ?? null,
                        ]
                    );


                } else {

                    $this->warn(
                        'Pending: '
                        . $request->id
                        . ' - '
                        . (
                            $result[
                                'message'
                            ]
                            ?? 'Unable to allocate'
                        )
                    );
                }


            } catch (\Throwable $e) {

                Log::error(
                    'Rider cron allocation error',
                    [
                        'trip_rider_id' =>
                            $request->id,

                        'message' =>
                            $e->getMessage(),
                    ]
                );


                $this->error(
                    'Error: '
                    . $request->id
                );
            }
        }


        return
            self::SUCCESS;
    }
}