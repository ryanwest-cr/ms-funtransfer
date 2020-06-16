<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameType extends Model
{
    //
    protected $table = "game_types";
    public function game(){
        return $this->hasMany(Game::class,"game_type_id","game_type_id");
    }
}
