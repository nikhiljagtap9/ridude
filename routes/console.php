<?php

use Illuminate\Support\Facades\Schedule;


Schedule::command(
    'riders:auto-allocate'
)
->everyMinute()
->withoutOverlapping();