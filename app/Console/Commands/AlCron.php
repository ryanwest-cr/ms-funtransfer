<?php

namespace App\Console\Commands;

use App\Http\Controllers\IAESportsController;
use App\Helpers\Helper;
use Illuminate\Console\Command;

/**
 *  TEST RIANDRAFT
 */
class AlCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'al:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Let it rain';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // $params = ["code" => 999,"data" => [],"message" => "CRON JOB"];
        // Helper::saveLog('RIANDAFT', 1223, json_encode($params), Helper::datesent());
        // return $params;
        // $schedule->call('App\Http\Controllers\IAESportsController@GG')->everyMinute();
        // call('App\Http\Controllers\IAESportsController@SettleRounds');
        $ia = new IAESportsController();
        $ia->SettleRounds();
        // IAESportsController::SettleRounds();
    }
}
