<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = ['content','key','userID'];

    public function items () {
        return $this->hasMany('App\Models\CartItem', 'Cart_id');
    }


}
