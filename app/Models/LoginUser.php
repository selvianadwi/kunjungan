<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class LoginUser extends Authenticatable
{
    protected $table = 'login_user';

    public $timestamps = false;

    protected $fillable = ['username', 'password'];

    protected $hidden = ['password'];
}
