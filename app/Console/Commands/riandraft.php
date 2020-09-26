<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\Helper;
use App\Http\Controllers\AlController;

class riandraft extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'al:riandraft';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        // Test Command
        try {
           $params = ["code" => 1337,"data" => [],"message" => "HAHAHA BOOM"];
           Helper::saveLog('im triggered', 1223, json_encode($params), Helper::datesent());
        } catch (\Exception $e) {
            
        }
    }
}
