<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $table = 'images';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'filename',
        'prediction',
        'confidence_level',
        'path',
        'user_id',
    ];
    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        return $this->path ? asset('storage/' . $this->path) : null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
