<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameSubProvider extends Model
{
    //
    protected $table = "sub_providers";
    public function games(){
        return $this->hasMany(Game::class,"sub_provider_id","sub_provider_id");
    }
}
