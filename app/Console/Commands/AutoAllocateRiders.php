<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\RiderAllocationService;
use Carbon\Carbon;

class AutoAllocateRiders extends Command
{
    protected $signature =
        'riders:auto-allocate';


    protected $description =
        'Allocate current date pending rider requests to best matching driver trips.';


    public function handle(
        RiderAllocationService $allocationService
    ): int {

        /*
        |--------------------------------------------------------------------------
        | Current Date
        |--------------------------------------------------------------------------
        */

        $today =
            Carbon::now(
                config('app.timezone')
            )
            ->toDateString();


        /*
        |--------------------------------------------------------------------------
        | Get Only Today's Pending Rider Requests
        |--------------------------------------------------------------------------
        */

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

            /*
             * IMPORTANT:
             * Only current date requests
             */
            ->whereDate(
                'requested_trip_date',
                $today
            )

            ->orderBy(
                'created_at',
                'asc'
            )

            ->get();


        /*
        |--------------------------------------------------------------------------
        | No Pending Requests
        |--------------------------------------------------------------------------
        */

        if (
            $pendingRequests
                ->isEmpty()
        ) {

            $this->info(
                'No pending rider requests found for today: '
                . $today
            );

            return
                self::SUCCESS;
        }


        /*
        |--------------------------------------------------------------------------
        | Process Pending Requests
        |--------------------------------------------------------------------------
        */

        foreach (
            $pendingRequests
            as $request
        ) {

            try {

                /*
                 * allocateBestTrip() will check:
                 *
                 * 1. Same date
                 * 2. Same direction
                 * 3. Workplace match
                 * 4. Seat availability
                 * 5. Driver route
                 * 6. Trip is inside 4-hour allocation window
                 */

                $result =
                    $allocationService
                        ->allocateBestTrip(
                            $request->id
                        );


                /*
                |--------------------------------------------------------------------------
                | Allocated
                |--------------------------------------------------------------------------
                */

                if (
                    !empty(
                        $result['status']
                    )
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

                            'requested_trip_date' =>
                                $request
                                    ->requested_trip_date,

                            'requested_direction' =>
                                $request
                                    ->requested_direction,

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

                    /*
                    |--------------------------------------------------------------------------
                    | Still Pending
                    |--------------------------------------------------------------------------
                    |
                    | Examples:
                    |
                    | - No driver available
                    | - Trip still more than 4 hours away
                    | - No seats
                    | - Route mismatch
                    |
                    | Cron will retry after 10 minutes.
                    |
                    */

                    $message =
                        $result[
                            'message'
                        ]
                        ?? 'Unable to allocate';


                    $this->warn(
                        'Pending: '
                        . $request->id
                        . ' - '
                        . $message
                    );


                    Log::info(
                        'Rider allocation still pending',
                        [
                            'trip_rider_id' =>
                                $request->id,

                            'requested_trip_date' =>
                                $request
                                    ->requested_trip_date,

                            'requested_direction' =>
                                $request
                                    ->requested_direction,

                            'message' =>
                                $message,
                        ]
                    );
                }


            } catch (\Throwable $e) {

                /*
                |--------------------------------------------------------------------------
                | Error
                |--------------------------------------------------------------------------
                */

                Log::error(
                    'Rider cron allocation error',
                    [
                        'trip_rider_id' =>
                            $request->id,

                        'requested_trip_date' =>
                            $request
                                ->requested_trip_date
                                ?? null,

                        'requested_direction' =>
                            $request
                                ->requested_direction
                                ?? null,

                        'message' =>
                            $e->getMessage(),

                        'file' =>
                            $e->getFile(),

                        'line' =>
                            $e->getLine(),
                    ]
                );


                $this->error(
                    'Error: '
                    . $request->id
                    . ' - '
                    . $e->getMessage()
                );
            }
        }


        return
            self::SUCCESS;
    }
}