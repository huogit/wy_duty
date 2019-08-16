<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    public $timestamps = false;
    protected $table = 'leaves';
    protected $guarded = [];

    public function duty()
    {
        return $this->belongsTo('App\Duty','duty_id','id');
    }

    public function user()
    {
        return $this->belongsTo('App\User','user_id','id');
    }

    public function auditor()
    {
        return $this->belongsTo('App\User','auditor_id','id');
    }

    public function complement_auditor()
    {
        return $this->belongsTo('App\User','complement_auditor_id','id');
    }

    public static function whereWdtp($params)
    {
        $duty = self::where('week',$params['week'])->where('day', $params['day'])->where('time', $params['time']);
        if (isset($params['place']))
            $duty = $duty->where('place',$params['place']);

        return $duty;
    }
  
}
