<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientGameSubscribe extends Model
{
    //
    protected $table = "client_game_subscribe";

    public function selectedProvider(){
        return $this->hasMany(SelectedProviders::class,"cgs_id","cgs_id");
    }
    public function gameExclude(){
        return $this->hasMany(GameExclude::class,"cgs_id","cgs_id");
    }
    public function subProviderExcluded(){
        return $this->hasMany(ExcludedSubProvider::class,"cgs_id","cgs_id");
    }
}
