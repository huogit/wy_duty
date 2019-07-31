<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Duty_record extends Model
{
    public $timestamps = false;
    protected $table = "duty_records";
    protected $guarded = [];
    // 每条值班记录属于一个值班表记录
    public function duty_table()
    {
        return $this->belongsTo('App\Duty_table','foreign_id','id');
    }

    // 每条值班记录属于一条补班申请记录
    public function complement()
    {
        return $this->belongsTo('App\Complement','foreign_id','id');
    }

    // 每条值班记录属于一条补班申请记录
    public function user()
    {
        return $this->belongsTo('App\User','openid','openid');
    }
}
