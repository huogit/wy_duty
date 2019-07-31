<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Duty_table extends Model
{
    public $timestamps = false;
    protected $table = "duty_table";

    protected $guarded = [];

    // 该值班属于的用户
    public function user()
    {
        return $this->belongsTo('App\User','openid','openid');
    }

    // 该值班的签到记录
    public function duty_record()
    {
        return $this->hasOne('App\Duty_record','foreign_id','id');
    }

    // 远程一对多，每条值班记录可能有一条补班记录
    public function complement()
    {
        return $this->hasManyThrough('App\Complement','App\Leave','duty_id','leave_id','id','id');
    }

    // 该值班下的请假
    public function leave()
    {
        return $this->hasOne('App\Leave','duty_id','id');
    }
}
