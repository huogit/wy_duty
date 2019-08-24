<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Duty extends Model
{ 
    public $timestamps = false;
    protected $table = "duty";

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo('App\User','user_id','id');
    }

    public function auditor()
    {
        return $this->belongsTo('App\User','auditor_id','id');
    }

    public function leave()
    {
        return $this->hasOne('App\Leave','duty_id','id');
    }

    public static function whereWdtp($params)
    {
        $duty = self::where('week',$params['week'])->where('day', $params['day'])->where('time', $params['time'])->where('status',1);
        if (isset($params['place']))
            $duty = $duty->where('place',$params['place']);

        return $duty;
    }
}
