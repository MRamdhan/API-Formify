<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    use HasFactory;
    public $timestamps = false;

    function user() {
        return $this->belongsTo(User::class);
    }
    public function questions()
    {
        return $this->hasMany(Question::class);
    }
    public function allowed_domains()
    {
        return $this->hasMany(AllowedDomain::class);
    }
}
