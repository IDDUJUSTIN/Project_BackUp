<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auditlogs extends Model
{
    protected $fillable = [
        'user_id',
        'activitylogs',
        'action',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
