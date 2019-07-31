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


    // 每个用户有多条值班表记录
    public function duty_table()
    {
        return $this->hasMany('App\Duty_table','openid','openid');
    }

    // 每个用户有多条补班记录
    public function complement()
    {
        return $this->hasMany('App\Complement','openid','openid');
    }

}
