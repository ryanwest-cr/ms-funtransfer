<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    //
    protected $table = "games";
    public function provider(){
        return $this->belongsTo(GameProvider::class,"provider_id","provider_id");
    }
    public function game_type(){
        return $this->belongsTo(GameType::class,"game_type_id","game_type_id")->select('game_type_id','game_type_name');
    }
    public function game_sub_provider(){
        return $this->belongsTo(GameSubProvider::class,"sub_provider_id","sub_provider_id");
    }
}
