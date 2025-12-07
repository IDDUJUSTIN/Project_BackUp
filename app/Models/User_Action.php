<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User_Action extends Model
{
    protected $table = 'user_actions';

    protected $fillable = [
        'user_id',
        'action',
        'details',
    ];
}
