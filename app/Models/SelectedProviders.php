<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SelectedProviders extends Model
{
    //
    protected $table = "selected_providers";

    public function clientGameSubscribe(){
        return $this->belongsTo(ClientGameSubscribe::class,"cgs_id","cgs_id");
    }
}
