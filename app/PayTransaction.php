<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PayTransaction extends Model
{
    //
    protected $fillable = ['token_id','identification_id','payment_id','amount','entry_id','trans_type_id','trans_update_url','status_id'];
    protected $table = "pay_transactions";
}
