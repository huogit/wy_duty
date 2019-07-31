<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Complement extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    // 每条补班记录属于一个用户
    public function user()
    {
        return $this->belongsTo('App\User', 'openid', 'openid');
    }

    // 每条补班记录属于一条请假记录
    public function leave()
    {
        return $this->belongsTo('App\Leave', 'leave_id', 'id');
    }

    // 每条补班记录有一条值班记录
    public function duty_record()
    {
        return $this->hasOne('App\Duty_record', 'foreign_id', 'id');
    }

    // 每条补班记属于一个审核者
    public function auditor()
    {
        return $this->belongsTo('App\User', 'auditor_id', 'openid');
    }
}
