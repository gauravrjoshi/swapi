<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('reminders:dispatch')->everyMinute();
Schedule::command('notifications:dispatch-scheduled')->everyMinute();