<?php

namespace App;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $guarded = [];

    // JWT token 的两个函数
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }


    // 用户的值班申请
    public function duty()
    {
        return $this->hasMany('App\Duty','user_id','id');
    }

    // 用户的补班申请
    public function complement()
    {
        return $this->hasMany('App\Leave','user_id','id');
    }

}
