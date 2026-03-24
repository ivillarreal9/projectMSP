<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MspCredential extends Model
{
    protected $fillable = [
        'username',
        'password',
        'base_url',
    ];

    protected $hidden = ['password'];
}