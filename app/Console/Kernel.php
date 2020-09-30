<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        'App\Console\Commands\AlCron',        
        'App\Console\Commands\riandraft',
        'App\Console\Commands\UpdateCurrency',        
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->call('UserController@deleteInactiveUsers')->everyMinute();
        // $schedule->command('al:cron')->everyMinute();
        // $schedule->call('App\Http\Controllers\IAESportsController@GG')->everyMinute();
        // $schedule->call('App\Http\Controllers\IAESportsController@SettleRounds')->everyThirtyMinutes();
        $schedule->call('App\Http\Controllers\IAESportsController@SettleRounds');
    }
}
