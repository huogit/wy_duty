<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    public $timestamps = false;
    protected $table = 'leaves';
    protected $guarded = [];

    // 每条请假记录属于一条值班表记录
    public function duty_table()
    {
        return $this->belongsTo('App\Duty_table','duty_id','id');
    }

    public function user()
    {
        return $this->belongsTo('App\User','openid','openid');
    }

    public function auditor()
    {
        return $this->belongsTo('App\User','auditor_id','openid');
    }

  
}
