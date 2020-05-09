<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PayTransactionLogs extends Model
{
    //
    protected $fillable = ['transaction_id','request','response','transaction_log_type'];
    protected $table = "pay_transaction_logs";
}
