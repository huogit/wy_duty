<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Duty;
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

        $id = (request('jwt_user'))->id;

        // 要值班
        $duty = Duty::whereWdtp(compact('week','day','time'))->where('user_id',$id)->where('sign_time',null);
        if ($duty->count())
            return $this->response(200,'ok',['needToDuty' => true]);

        // 要补班
        $leave = Leave::whereWdtp(compact('week','day','time'))->where('user_id',$id)->where('sign_time',null);
        if ($leave->count())
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
        $id = (request('jwt_user'))->id;

        // 值班
        $duty = Duty::whereWdtp(compact('week','day','time'))->where('user_id',$id)->where('sign_time',null);
        if ($duty->count())
        {
            $duty = $duty->first();
            $duty->update(['sign_time'=>date('Y-m-d H:i:s')]);
            User::where('id',$id)->increment('duty_count');
            return $this->response(200, '已签到成功');
        }

        // 补班
        $leave = Leave::whereWdtp(compact('week','day','time'))->where('user_id',$id)->where('sign_time',null);
        if ($leave->count()) {
            $leave = $leave->first();
            $leave->update(['sign_time'=>date('Y-m-d H:i:s')]);
            User::where('id',$id)->increment('complements_count');
            return $this->response(200, '已签到成功');
        }

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
            'week' => 'required|integer|between:1,17',
            'day' => 'required|between:1,5',
            'place' =>  'required|in:0,1',
            'time' =>  'required|in:0,1'
        ]);

        $params = request(['week','day','time','place']);

        $duty = Duty::whereWdtp($params);
        $complements = Leave::whereWdtp($params);
        $leaves = Leave::whereHas('duty', function ($query) use ($params) {
            $query->where('week', $params['week'])->where('day', $params['day'])->where('place', $params['place'])->where('time', $params['time']);
        });

        // 值班总人数 = 值班 + 补班
        $duty_count = $duty->count() + $complements->count();

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
            'week' => 'required|integer|between:1,17',
            'day' => 'required|between:1,5',
            'time' => 'required|in:0,1',
            'place' =>  'required|in:0,1',
            'reason' => 'required|max:255',
        ]);

        $user_id = (request('jwt_user'))->id;
        $week = request('week');
        $day = request('day');
        $time = request('time');
        $reason = request('reason');

        $params = request(['week','day','time','place']);

        // 不需要值班，无法请假
        $duty = Duty::whereWdtp($params)->where('user_id',$user_id);
        if ($duty->count() == 0)
            return $this->response(204,'该班次你不需要值班，无法请假');
        else
            $duty_id = $duty->first()->id;

        // 值班时间已过，无法请假
        if ($this->isPastDue($week,$day,$time)) // 值班日期 < 当前日期
        {
            return $this->response(200,'值班时间已过，无法请假');
        }

        // 已请假，无法请假
        $leave = Leave::where('user_id',$user_id)->where('duty_id',$duty_id);
        if ($leave->count() == 1)
            return $this->response(409, '已请假，请不要重复操作');

        // 添加请假记录
        $params = compact('duty_id','user_id','type','reason');
        if(Leave::create($params)) {
            // 请假次数 + 1
            User::where('id',$user_id)->increment('leaves_count');

//           // 发送审核提醒
//            $wechat_openid = User::where('is_admin',1)->pluck('wechat_openid');
//            $tplMask = '审核提醒';
//            $user = User::find($user_id);
//            $real_name = $user->real_name;
//            foreach ($wechat_openid as $openid) {
//                $data = [
//                    "first" => [
//                        "value" => "你好，你有一个申请需要审核",
//                        "color" => "#173177"
//                    ],
//                    "keyword1" => [
//                        "value" => "请假",
//                        "color" => "#173177"
//                    ],
//                    "keyword2" => [
//                        "value" => $real_name,
//                        "color" => "#173177"
//                    ],
//                    "keyword3" => [
//                        "value" => date('Y-m-d H:i:s'),
//                        "color" => "#173177"
//                    ],
//                    "remark" => [
//                        "value" => "请尽快审核",
//                        "color" => "#173177"
//                    ],
//                ];
//                $this->sendTplMessage($openid, $tplMask, $data, 'pages/examine/examine');
//            }

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
          $request->validate([
              'week' => 'required|integer|between:1,17',
              'day' => 'required|between:1,5',
              'time' => 'required|in:0,1',
              'place' =>  'required|in:0,1',
              'reason' => 'max:255',
          ]);

        $user_id = (request('jwt_user'))->id;
        $params = request(['week','day','time','place']);
        $params['complement_created_at'] = date('Y-m-d H:i:s');
        $params['complement_audit_status'] = 1;

        // 没有请假 或者 文秘小姐姐没有审核 或者 文秘小姐姐审核不通过
        $leaves = Leave::where('user_id',$user_id)->where('sign_time',null)->where('audit_status',1);
        if ($leaves->count() == 0)
            return $this->response(403, '没有请假或者文秘小姐姐审核不通过，无法申请补班');

        // 申请时间与值班时间冲突
        $leave  = $leaves->first();
        $duty = Duty::whereWdtp($params)->where('user_id',$user_id);
        $complement = Leave::whereWdtp($params)->where('user_id',$user_id);
        if($duty->count() == 1)
            return $this->response(409, '与值班时间冲突，无法申请补班');

        // 补班已存在
        if($complement->count() == 1)
            return $this->response(409, '补班已存在，请不要重复操作');

        // 添加补班信息
        $params['complement_auditor_id'] = $leave->auditor_id;
        $leave->update($params);

        return $this->response(201, '申请补班成功');
    }

    /**
     * 我的申请
     * 
     * @return string
     */
    public function my_apply()
    {
        $user_id = (request('jwt_user'))->id;

        $complements = leave::where('user_id',$user_id)
            ->where('week','!=',null)
            ->select('id','user_id','week','day','place','time','auditor_id','audit_status','created_at')
            ->selectRaw('0 as type')
            ->with('auditor:id,real_name')
            ->with('user:id,real_name')
            ->get();

        $leaves = Leave::where('user_id',$user_id)
            ->select('id','user_id','auditor_id','auditor_id','audit_status','created_at','duty_id','reason')
            ->selectRaw('1 as type')
            ->with('duty:id,week,day,place,time')
            ->with('auditor:id,real_name')
            ->with('user:id,real_name')
            ->get();

        if (count($complements) == 0 && count($leaves) == 0)
            return $this->response('204','No Content');

        foreach ($leaves as $leave)
        {
            $leave->week = $leave->duty['week'];
            $leave->day = $leave->duty['day'];
            $leave->place = $leave->duty['place'];
            $leave->time = $leave->duty['time'];

            unset($leave->duty);
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
            'type' =>  'required|in:0,1'
        ]);

        $id = request('id'); // 假条、补班条id
        $type = request('type');
        $user_id = (request('jwt_user'))->id;

        // 取消请假
        if ($type == 0)
        {
            $leave = Leave::where('user_id',$user_id)->where('id',$id);
            if($leave->count())
                $duty = $leave->first()->duty;
            else
                return $this->response(403,'不存在此id');

            // 值班时间已过,无法取消
            if ($this->isPastDue($duty['week'],$duty['day'],$duty['time'])
                return $this->response(403,'值班时间已过，无法取消请假申请');

            // 文秘小姐姐已拒绝，无法取消
            if ($leave->first()->audit_status == 2)
                return $this->response(403,'申请已拒绝，无法取消');

            // 删除请假申请
            if ($leave->delete()) {
                $user = User::find($user_id);
                $user->decrement('leaves_count');
                return $this->response(200, '取消成功');
            }
        }

        // 取消补班
        if ($type == 1)
        {
            $complement = Leave::where('user_id',$user_id)->where('id',$id)->where('sign_time',null);
            if($complement->count())
                $complement = $complement->first();
            else
                return $this->response(403,'不存在此id');

            // 补班时间已过，无法取消
            if($this->isPastDue($complement->week,$complement->day,$complement->time))
                $this->response(403,'值班时间已过，无法取消请假申请');

            // 删除补班
            $complement->update(['week'=>null,'day'=>null,'time'=>null,'place'=>null]);
//            User::find($user_id)->decrement('complements_count');

            return $this->response(200,'取消成功');
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
            'type' =>  'required|in:0,1'
        ]);

        $type = request('type');

        $condition = $type == 0 ? '=' : '!=';
        $data = [];
        $leaves = Leave::where('audit_status',$condition,0)->get();
        $complements = Leave::where('complement_audit_status',$condition,0)->where('week','!=',null)->get();
        $noSigns = Duty::where('sign_time',null)->where('audit_status',$condition,0)->get();

        // 处理一下格式
        if (count($leaves) > 0)
        {
            foreach ($leaves as $leave)
            {
                $arr = [
                    'id' => $leave->id,
                    'real_name' => $leave->user->real_name,
                    'week' => $leave->duty->week,
                    'day' => $leave->duty->day,
                    'place' => $leave->duty->place,
                    'time' => $leave->duty->time,
                    'reason' => $leave->reason,
                    'created_at' => $leave->created_at,
                    'audit_time' => $leave->audit_time,
                    'audit_status' => $leave->audit_status,
                    'type' => 0
                ];
                if ($leave->audit_status != 0)
                    $arr = array_merge($arr,['auditor_name' => $leave->auditor->real_name]);

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
                    'created_at' => $complement->complement_created_at,
                    'audit_time' => $complement->complement_audit_time,
                    'audit_status' => $complement->complement_audit_status,
                    'auditor_name' => $complement->auditor->real_name,//$complement->complement_auditor->real_name,
                    'type' => 1
                ];

                if ($complement->complement_audit_status != 0)
                    $arr = array_merge($arr,['auditor_name' => $complement->complement_auditor->real_name]);

                array_push($data, $arr);
            }
        }

        if (count($noSigns) > 0)
        {
            foreach ($noSigns as $noSign)
            {
                if (!$this->isPastDue($noSign->week,$noSign->day,$noSign->time)) // 超过值班日期时间的才是未签到
                    continue;
                $arr = [
                    'id' => $noSign->id,
                    'real_name' => $noSign->user->real_name,
                    'week' => $noSign->week,
                    'day' => $noSign->day,
                    'place' => $noSign->place,
                    'time' => $noSign->time,
                    'reason' => null,
                    'created_at' => $this->duty_dateTime($noSign->week,$noSign->day,$noSign->time),
                    'audit_time' => $noSign->audit_time,
                    'audit_status' => $noSign->audit_status,
                    'type' => 2
                ];
                if ($noSign->audit_status != 0)
                    $arr = array_merge($arr,['auditor_name' => $noSign->auditor->real_name]);

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
            'type' =>  'required|in:0,1,2',
            'dispose' => 'required|in:0,1,2,3',
        ]);
        
        $id = request('id');
        $type = request('type');
        $audit_status = request('dispose');
        $audit_time = date('Y-m-d H:i:s');
        $auditor_id =  (request('jwt_user'))->id;;

        switch ($type)
        {
            // 请假
            case 0:
                $leave = Leave::find($id);
                $leave->update(compact('auditor_id','audit_status','audit_time'));
                if ($audit_status == 2){
                    User::find($leave->user->id)->decrement('leaves_count');
                }
                $data = [
                    "first" => [
                        "value" => "亲，您提交申请现已处理。",
                        "color" => "#173177"
                    ],
                    "keyword1" => [
                        "value" => "你",
                        "color" => "#173177"
                    ],
                    "keyword2" => [
                        "value" => $audit_status == 1 ? "通过" : "拒绝",
                        "color" => "#173177"
                    ],
                    "keyword3" => [
                        "value" => "刚刚",
                        "color" => "#173177"
                    ],
                    "remark" => [
                        "value" => "审核通过后可通过公众号进入系统",
                        "color" => "#173177"
                    ],
                ];
                $this->sendTplMessage($leave->user->wechat_openid, '审核结果通知', $data,'pages/schedule/schedule');
                break;
            // 补班
            case 1:
                $complement = Leave::find($id);
                $complement->update(compact('complement_auditor_id','complement_audit_status','complement_audit_time'));
                break;
            // 未签到
            case 2:
                $duty = Duty::find($id);
                $duty->update(compact('auditor_id','audit_status','audit_time'));
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
        $data = array();

        $dutys = Duty::where('week',$week)->where('day',$day)->get();
        $complements = Leave::where('week',$week)->where('day',$day)->get();

        // 值班
        foreach ($dutys as $duty)
        {
            $place = $duty->place;
            $time = $duty->time;
            $real_name = $duty->user->real_name;
            $status = 0;

            if ($duty->sign_time != null){ // 已签到
                $status = 1;
            }elseif($duty->leave != null && $duty->leave->audit_status == 1){ // 已请假
                $status = 2;
            }elseif($this->isPastDue($week,$day,$duty->time) && $duty->sign_time == null) { // 未签到
                $status = 3;
            }

            array_push($data,compact('real_name','place','time','status'));
        }

        // 补班
        foreach ($complements as $complement)
        {
            $place = $complement->place;
            $time = $complement->time;
            $real_name = $complement->user->real_name;
            $status = 0;

            if ($complement->sign_time != null) { // 已签到
                $status = 1;
            }elseif($this->isPastDue($week,$day,$duty->time) && $duty->sign_time == null) { // 未签到
                $status = 3;
            }

            array_push($data,compact('real_name','place','time','status'));
        }

        $data = collect($data)->sortByDesc('place')->sortByDesc('time')->sortBy('status'); // 排序了要使用->values()->all()才能得到真的排序后的数组
        $data = $data->values()->all();

        return $this->response(200,'ok',$data);
    }

}
