<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VCPayment extends Model
{
    //
    protected $table = 'v_c_payments';

    protected $fillable = ['cardnumber','amount','point','status','user_id','purchase_id'];


}
