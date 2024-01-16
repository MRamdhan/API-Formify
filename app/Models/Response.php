<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Response extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = ['user_id', 'form_id','value', ];

    function user() {
        return $this->belongsTo(User::class);
    }

    function answers() {
        return $this->hasMany(Answer::class, 'response_id');
    }
}
