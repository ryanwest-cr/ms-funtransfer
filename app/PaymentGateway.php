<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    //
    protected $fillable = ['name'];
    protected $table = "payment_gateway";
}
