<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trip Defaults
    |--------------------------------------------------------------------------
    */

    'default_available_seats' => 3,

    'default_fare_per_rider' => 12.40,

    /*
     * Home -> Work
     * Default departure before shift starts
     */
    'home_to_work_lead_minutes' => 45,

    /*
     * Work -> Home
     * Default departure after shift ends
     */
    'work_to_home_delay_minutes' => 10,

];