<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\AlController;
use App\Helpers\PaymentHelper;
class UpdateCurrency extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:currency';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This is to update the currency every hour';

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
        //
        PaymentHelper::gameCurrencyConverter();
    }
}
