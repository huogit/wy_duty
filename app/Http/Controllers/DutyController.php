<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Duty_record;
use App\Duty_table;
use App\Complement;
use App\Leave;


class DutyController extends Controller
{
    /**
     * 是否需要签到 （用于前端显示隐藏签到按钮）
     *
     * @return string
     */
    public function needToSign()
    {
        $week = $this->nowWeek();
        $day = $this->nowDay();
        $time = $this->nowTime();

        $openid = (request('jwt_user'))->openid;

        $duty = Duty_table
            ::where('openid',$openid)->where('week',$week)->where('day', $day)->where('time', $time)
            ->doesntHave('duty_record');//->doesntHave('leaves');

        // 要值班
        if ($duty->count() == 1)
            return $this->response(200,'ok',['needToDuty' => true]);

        $complement = Complement
            ::where('openid',$openid)->where('week',$week)->where('day',$day)->where('time',$time)->where('duty_status',0)
            ->doesntHave('duty_record');

        // 要补班
        if ($complement->count() == 1)
            return $this->response(200,'ok',['needToDuty' => true]);

        // 都不用
        return $this->response(200,'ok',['needToDuty' => false]);
    }

    /**
     * 签到
     *
     * @return string
     */
    public function sign_in()
    {
        $week = $this->nowWeek();
        $day = $this->nowDay();
        $time = $this->nowTime();
        $openid = (request('jwt_user'))->openid;

        $duty = Duty_table::where('openid', $openid)->where('week',$week)->where('day', $day)->where('time', $time);
        $complement = Complement::where('openid',$openid)->where('week',$week)->where('day',$day)->where('time',$time)->where('duty_status',0);

        // 需要值班
        if ($duty->count() == 1)
        {
            $duty = $duty->first();
            $record = $duty->duty_record()->where('type', 0)->where('created_at', '>', date('Y-m-d'));

            // 没有签到记录，可签到
            if ($record->count() == 0)
            {
                // 添加签到记录
                $record = new Duty_record();
                $record->foreign_id = $duty->id;
                $record->type = 0;
                $record->openid = $openid;
                $record->save();

                // 值班次数 +1
                DB::table('users')->where('openid',$openid)->increment('duty_count');
            }
            return $this->response(200, '已签到成功');
        }
        // 需要补班
        elseif ($complement->count() == 1)
        {
            $complement = $complement->first();
            $record = $complement->duty_record()->where('type', 1)->where('created_at', '>', date('Y-m-d'));

            // 没有签到记录，可签到
            if ($record->count() == 0)
            {
                // 添加签到记录
                $record = new Duty_record();
                $record->foreign_id = $complement->id;
                $record->type = 1;
                $record->openid = $openid;
                $record->save();

                // 更新 补班,请假 状态
                $complement->update(['duty_status' => 1]);
                Leave::where('duty_id',$complement->duty_id)->where('duty_status',0)->update(['duty_status'=>1]);

                // 值班次数 +1, 补班次数 +1
                $user = DB::table('users')->where('openid',$openid);
                $user->increment('duty_count');
                $user->increment('complements_count');
            }
            return $this->response(200, '已签到成功');
        }
        else
            return $this->response(200, '已签到成功');

    }

    /**
     * 请假补班页
     *
     * @return string
     */
    public function apply()
    {
        $auditor = DB::table('users')->where('is_admin',1)->select('real_name')->get()->toArray();
        $current_week = $this->nowWeek();
        $data = compact('auditor','current_week');

        return $this->response(200,'ok',$data);
    }

    /**
     * 该节有多少人 值班，请假
     *
     * @param Request $request
     * @return string
     */
    public function count(Request $request)
    {
        $request->validate([
            'week' => 'required|max:20|min:1',
            'day' => 'required|max:5|min:1',
            'place' =>  'required|boolean',
            'time' =>  'required|max:1|boolean'
        ]);

        $week = request('week');
        $day = request('day');
        $place = request('place');
        $time = request('time');

        $dutys = Duty_table::where('week',$week)->where('day',$day)->where('place',$place)->where('time',$time);
        $complements = Complement::where('week',$week)->where('day',$day)->where('place',$place)->where('time',$time);
        $leaves = Leave::whereHas('duty_table', function ($query) {
            $query->where('week', request('week'))->where('day', request('day'))->where('place', request('place'))->where('time', request('time'));
        });

        // 值班总人数 = 值班 + 补班
        $duty_count = $dutys->count() + $complements->count();

        // 请假人数
        $leave_count = $leaves->count();

        return $this->response(200, 'ok', compact('duty_count', 'leave_count'));
    }

    /**
     * 请假申请
     *
     * @param Request $request
     * @return string
     */
    public function leave(Request $request)
    {
        $request->validate([
            'week' => 'required|max:20|min:1',
            'day' => 'required|max:5|min:1',
            'place' =>  'required|boolean',
            'time' =>  'required|boolean',
            'reason' => 'required|max:255',
        ]);

        $openid = (request('jwt_user'))->openid;
        $week = request('week');
        $day = request('day');
        $place = request('place');
        $time = request('time');
        $reason = request('reason');

        // 不需要值班，无法请假
        $duty = Duty_table::where('openid',$openid)->where('week',$week)->where('place',$place)->where('day',$day)->where('time',$time);
        if ($duty->count() == 0)
            return $this->response(204,'该班次你不需要值班，无法请假');
        else
            $duty_id = $duty->first()->id;

        // 值班时间已过，无法请假
        if ($this->duty_date($week,$day) < date('Y-m-d')) // 值班日期 < 当前日期
        {
            return $this->response(200,'值班时间已过，无法请假');
        }
        elseif($this->duty_date($week,$day) == date('Y-m-d'))
        {
            $His = date('H:i:s'); // 当前时间
            if ($time == 0 && $His > '16:25')
                return $this->response(200,'值班时间已过，无法请假');
            elseif($time == 1 && $His > '18:05')
                return $this->response(200,'值班时间已过，无法请假');
        }

        // 已请假
        $leave = Leave::where('openid',$openid)->where('duty_id',$duty_id)->where('duty_status',0);
        if ($leave->count() == 1)
            return $this->response(409, '已请假，请不要重复操作');

        // 添加请假记录
        $type = 0;
        $params = compact('duty_id','openid','type','reason');
        if(Leave::create($params)) {
            // 请假次数 + 1
            DB::update('update users set leaves_count = leaves_count + 1 where openid =?', [$openid]);

            // 发送审核提醒
            $tplMask = '审核提醒';
            $user = DB::table('users')->where('openid',$openid)->first();
            $real_name = $user->real_name;
            $wechat_openid = $user->wechat_openid;
            $data = [
                "first" => [
                    "value" => "你好，你有一个申请需要审核",
                    "color" => "#173177"
                ],
                "keyword1" => [
                    "value" => "请假",
                    "color" => "#173177"
                ],
                "keyword2" => [
                    "value" => $real_name,
                    "color" => "#173177"
                ],
                "keyword3" => [
                    "value" => date('Y-m-d H:i:s'),
                    "color" => "#173177"
                ],
                "remark" => [
                    "value" => "请尽快审核",
                    "color" => "#173177"
                ],
            ];
            $data = $this->sendTplMessage($wechat_openid,$tplMask,$data,'index');

            return $this->response(201, '申请请假成功');
        }


    }

    /**
     * 补班申请
     *
     * @param Request $request
     * @return string
     */
    public function complement(Request $request)
    {
        # 验证
        $request->validate([
            'week' => 'required|max:20|min:1',
            'day' => 'required|max:5|min:1',
            'place' =>  'required|boolean',
            'time' =>  'required|boolean',
        ]);

        # 获取参数
        $openid = (request('jwt_user'))->openid;
        $week = request('week');
        $day = request('day');
        $time = request('time');
        $place = request('place');

        # 查看是否需要补班
        $leaves = Leave::where('openid',$openid)->where('duty_status',0)->where('audit_status',1);
        if ($leaves->count() == 0)
            return $this->response(403, '没有请假或者文秘小姐姐审核不通过，无法申请补班');
        else
        {
            $leave  = $leaves->first();
            $leave_id = $leave->id;
            $duty_id = $leave->duty_id;
        }


        # 获取值班和补班记录
        $duty = Duty_table::where('openid',$openid)->where('week',$week)->where('day',$day)->where('time',$time);
        $complement = Complement::where('openid',$openid)->where('week',$week)->where('day',$day)->where('time',$time);

        if($duty->count() == 1) // 与值班时间冲突
            return $this->response(409, '与值班时间冲突，无法申请补班');

        if($complement->count() == 0) // 没有申请过，创建申请记录
        {
            $params = compact('openid', 'week', 'day', 'time', 'place', 'duty_id', 'leave_id');
            Complement::create($params);
        }

        return $this->response(201, 'created，申请补班成功');
    }

    /**
     * 我的申请
     * @return string
     */
    public function my_apply()
    {
        $openid = (request('jwt_user'))->openid;

        $complements = Complement::where('openid',$openid)
            ->select('id','openid','week','day','place','time','auditor_id','audit_status','created_at')
            ->selectRaw('0 as type')
            ->with('auditor:openid,real_name')
            ->with('user:openid,real_name')
            ->get();

        $leaves = Leave::where('openid',$openid)->where('type',0) // 0请假 1未值班
            ->select('id','openid','auditor_id','auditor_id','audit_status','created_at','duty_id','reason')
            ->selectRaw('1 as type')
            ->with('duty_table:id,week,day,place,time')
            ->with('auditor:openid,real_name')
            ->with('user:openid,real_name')
            ->get();

        if (count($complements) == 0 && count($leaves) == 0)
            return $this->response('204','No Content');

        foreach ($leaves as $leave)
        {
            $leave->week = $leave->duty_table['week'];
            $leave->day = $leave->duty_table['day'];
            $leave->place = $leave->duty_table['place'];
            $leave->time = $leave->duty_table['time'];

            unset($leave->duty_table);
        }

        $data = array_merge($leaves->toArray(),$complements->toArray());

        $data = collect($data)->sortByDesc('created_at'); // 排序了要使用->values()->all()才能得到真的排序后的数组
        $data = $data->values()->all();

        return $this->response(200,'ok',$data);
    }

    /**
     * 取消申请
     *
     * @param Request $request
     * @return string
     */
    public function cancel(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'type' =>  'required|boolean'
        ]);

        $id = request('id'); // 假条、补班条id
        $type = request('type');
        $openid = (request('jwt_user'))->openid;

        // 取消请假
        if ($type == 0)
        {
            $leave = Leave::where('openid',$openid)->where('id',$id)->first();
            $duty = $leave->duty_table;

            // 值班时间已过,无法取消
            if ($this->duty_date($duty->week,$duty->day) < date('Y-m-d')) // 值班日期 < 当前日期
            {
                return $this->response(403,'值班时间已过，无法取消请假申请');
            }
            elseif($this->duty_date($duty->week,$duty->day) == date('Y-m-d'))
            {
                $His = date('H:i:s');
                if ($duty->time == 0 && $His > '16:25')
                    return $this->response(403,'值班时间已过，无法取消请假申请');
                if($duty->time == 1 && $His > '18:05')
                    return $this->response(403,'值班时间已过，无法取消请假申请');
            }
            else
            {
                if ($leave->delete()) {
                    DB::table('users')->where('openid', $openid)->decrement('leaves_count');
                    return $this->response(200, '取消成功');
                }
            }

        }
        // 取消补班
        else
        {
            DB::table('users')->where('openid',$openid)->decrement('complements_count');
            $complement = Complement::where('openid',$openid)->where('id',$id)
                ->whereHas('duty_table',function ($query){                               // 查询未过值班时间的请假
                    $time = $this->nowTime() == -1 ? 0 : $this->nowTime();
                    $nowDateTime = $this->nowWeek().$this->nowDay().$time;
                    $query->whereRaw('CONCAT(week,day,time) >= '.$nowDateTime);
                });

            // 补班时间未过，可以取消
            if ($complement->count() == 1)
            {
                DB::table('users')->where('openid',$openid)->decrement('complements_count');
                $complement->delete();
                return $this->response(200,'取消成功');
            }
            else
                return $this->response(403,'值班时间已过，无法取消请假申请');
        }
    }

    /**
     * 审批页
     *
     * @param Request $request
     * @return string
     */
    public function audit(Request $request)
    {
        $request->validate([
            'type' =>  'required|boolean'
        ]);

        $type = request('type');

        $data = array();

        # 获取模型
        $complements = Complement::where('audit_status',$type)->get();
        $leaves = Leave::where('audit_status',$type)->where('type',0)->get();

        # 没有内容
        if (count($complements) == 0 && count($leaves) == 0)
            return $this->response(204,'No Content');

        # 处理一下格式
        if (count($leaves) > 0) 
        {
            foreach ($leaves as $leave) 
            {
                $arr = [
                    'id' => $leave->id,
                    'real_name' => $leave->user->real_name,
                    'week' => $leave->duty_table['week'],
                    'day' => $leave->duty_table['day'],
                    'place' => $leave->duty_table['place'],
                    'time' => $leave->duty_table['time'],
                    'reason' => $leave->reason,
                    'created_at' => $leave->created_at,
                    'audit_time' => $leave->audit_time,
                    'audit_status' => $leave->audit_status,
                ];

                if ($leave->audit_status != 0) 
                    $arr = array_merge($arr,['auditor_name' => $leave->auditor->real_name]);

                $type = $leave->type == 0 ? 0:2;
                $arr = array_merge($arr,['type' => $type]);

                array_push($data, $arr);
            }
        }

        if (count($complements) > 0)
        {
            foreach ($complements as $complement) 
            {
                $arr = [
                    'id' => $complement->id,
                    'real_name' => $complement->user->real_name,
                    'week' => $complement->week,
                    'day' => $complement->day,
                    'place' => $complement->place,
                    'time' => $complement->time,
                    'reason' => null,
                    'created_at' => $complement->created_at,
                    'audit_time' => $complement->audit_time,
                    'audit_status' => $complement->audit_status,
                    'type' => 1
                ];

                if ($complement->audit_status != 0) 
                    $arr = array_merge($arr,['auditor_name' => $complement->auditor->real_name]);

                array_push($data, $arr);
            }
        }

        $data = collect($data)->sortByDesc('created_at')->toArray(); // 排序
        $data = array_values($data); // 去掉键名

        return $this->response(200,'ok',$data);
    }

    /**
     * 审核操作
     *
     * @param Request $request
     * @return string
     */
    public function auditing(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'type' =>  'required|max:2|min:0',
            'dispose' => 'required|max:3|min:1',
        ]);
        
        $id = request('id');
        $type = request('type');
        $audit_status = request('dispose');
        $audit_time = date('Y-m-d H:i:s');
        $auditor_id =  (request('jwt_user'))->openid;;

        switch ($type)
        {
            // 请假
            case 0:
            // 未签到
            case 2:
                $leave = Leave::find($id);
                $leave->update(compact('auditor_id','audit_status','audit_time'));
                break;
            // 补班
            case 1:
                $complement = Complement::find($id);
                $complement->update(compact('auditor_id','audit_status','audit_time'));
                break;
        }

        return $this->response(200,'ok');
    }










    /**
     * 当天值班表
     *
     * @param Request $request
     * @return string
     */
    public function list(Request $request)
    {
        $request->validate([
            'week' => 'required|integer|between:1,17',
            'day' => 'required|integer|between:1,5'
        ]);

        $week = request('week');
        $day = request('day');
        $His = date('H:i:s'); // 当前时间
        $data = array();

        # 当天“默认要值班”的人
        $dutys = Duty_table::where('week',$week)->where('day',$day)->get();

        # 当天“要补班”的人
        $complements = Complement::where('week',$week)->where('day',$day)->get();

        # 当天“已签到”的人
        $records = Duty_record
            ::whereHas('duty_table',function ($query){
                $query->where('week',request('week'))->where('day',request('day'));
            })
            ->orWhereHas('complement',function ($query){
                $query->where('week',request('week'))->where('day',request('day'));
            })
            ->get();

        # 当天“请假”的人
        $leaves = Leave
            ::where('audit_status',1)
            ->whereHas('duty_table',function ($query){
                $query->where('week',request('week'))->where('day',request('day'));
            })->get();


        // 添加签到状态 （多捞啊 我的代码）

        // 值班
        foreach ($dutys as $duty)
        {
            $place = $duty->place;
            $time = $duty->time;
            $real_name = $duty->user->real_name;
            $status = 0;   // 签到状态，默认待签到

            // 时间已过，未签到
            if ($this->duty_date($duty->week,$duty->day) < date('Y-m-d')) // 值班日期 < 当前日期
            {
                $status = 3;
            }
            elseif($this->duty_date($duty->week,$duty->day) == date('Y-m-d'))
            {
                if ($duty->time == 0 && $His > '16:25')
                    $status = 3;
                if($duty->time == 1 && $His > '18:05')
                    $status = 3;
            }

            // 有签到记录，已签到
            foreach ($records as $record)
                if ($record->type == 0 && $duty->id == $record->foreign_id)
                {
                    $status = 1;
                    break;
                }

            // 有请假记录，已请假
            foreach ($leaves as $leave)
                if ($leave->type == 0 && $duty->id == $leave->duty_id)
                {
                    $status = 2;
                    break;
                }

            array_push($data,compact('real_name','place','time','status'));
        }

        // 补班
        foreach ($complements as $complement)
        {
            $place = $complement->place;
            $time = $complement->time;
            $real_name = $complement->user->real_name;
            $status = 0;   // 签到状态，默认待签到

            // 时间已过，未签到
            if ($this->duty_date($complement->week,$complement->day) < date('Y-m-d')) // 值班日期 < 当前日期
            {
                $status = 3;
            }
            elseif($this->duty_date($complement->week,$complement->day) == date('Y-m-d'))
            {
                if ($complement->time == 0 && $His > '16:25')
                    $status = 3;
                if($complement->time == 1 && $His > '18:05')
                    $status = 3;
            }

            // 有签到记录，已签到
            foreach ($records as $record)
                if ($record->type == 1 && $complement->id == $record->foreign_id)
                {
                    $status = 1;
                    break;
                }

            array_push($data,compact('real_name','place','time','status'));
        }

        $data = collect($data)->sortByDesc('place')->sortByDesc('time')->sortBy('status'); // 排序了要使用->values()->all()才能得到真的排序后的数组
        $data = $data->values()->all();

        return $this->response(200,'ok',$data);
    }

}
