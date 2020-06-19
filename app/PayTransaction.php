<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PayTransaction extends Model
{
    //
<<<<<<< HEAD
    protected $fillable = ['token_id','identification_id','payment_id','amount','entry_id','trans_type_id','trans_update_url','status_id','orderId'];
=======
    protected $fillable = ['token_id','identification_id','reference_number','payment_id','amount','entry_id','trans_type_id','trans_update_url','status_id','orderId'];
>>>>>>> 259aae13b75909c07546cbae8664951598a3fe9d
    protected $table = "pay_transactions";
}
