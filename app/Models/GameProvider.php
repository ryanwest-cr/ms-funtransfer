<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameProvider extends Model
{
    //
    protected $table = "providers";
    public function games(){
        return $this->hasMany(Game::class,"provider_id","provider_id");
    }
}
