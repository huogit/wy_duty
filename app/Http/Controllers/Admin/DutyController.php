<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/1
 * Time: 13:41
 */

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use App\Duty;
use App\Leave;
use App\User;

class DutyController extends \App\Http\Controllers\Controller
{
    /**
     * 首页
     *
     * @return mixed
     */
    public function start()
    {
        $top_bg_color = Cache::get('top_bg_color');
        $top_word_color = Cache::get('top_word_color');
        $bottom_word_color = Cache::get('bottom_word_color');
        $bottom_button_color = Cache::get('bottom_button_color');

        $start_img_url = Cache::get('start_img_url');

        return $this->response(200,'ok',compact('top_bg_color','top_word_color','bottom_word_color','bottom_button_color','start_img_url'));
   }


   // TODO 存到缓存

    /**
     * 更改首页样式
     *
     * @return mixed
     */
    public function start_change()
    {
        $tbc = request('top_bg_color');
        $twc = request('top_word_color');
        $bbc = request('bottom_button_color');
        $bwc = request('bottom_word_color');
        $siu = request('start_img_url');

        if (!empty($tbc))
            Cache::forever('top_bg_color',request('top_bg_color'));

        if (!empty($twc))
            Cache::forever('top_word_color',request('top_word_color'));

        if (!empty($bbc))
            Cache::forever('bottom_word_color',request('bottom_button_color'));

        if (!empty($bwc))
            Cache::forever('bottom_button_color',request('bottom_word_color'));

        if (!empty($siu))
            Cache::forever('start_img_url',request('start_img_url'));

        return $this->response(200,'ok');
    }


    /**
     * 值班管理页
     *
     * @return string
     */
    public function index()
    {
        $current_week = $this->nowWeek();
        $users = User::select('real_name');

        $count = $users->count();
        $users= $users->get();

        return $this->response(200,'ok',compact('users','count','current_week'));
    }


    /**
     * 签到记录
     *
     * @return string
     */
    public function records(Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
            'page' => 'required|numeric'
        ]);

        # 获取参数
        $start = request('start');
        $end = request('end');
        $end = date("Y-m-d",strtotime("+1 day",strtotime($end)));

        $records = Duty::whereBetween('sign_time',[$start,$end])->with('user:id,real_name')
            ->select('user_id','day','place','time')->selectRaw('sign_time as created_at');

        $records = Leave::whereBetween('sign_time',[$start,$end])->with('user:id,real_name')
            ->select('user_id','day','place','time')->selectRaw('sign_time as created_at')
            ->union($records)->orderBy('created_at')->orderBy('place')//->orderBy('date(created_at) DESC')
            ->paginate(10);

        return $this->response(200,'ok',$records);
    }

    /**
     * 该周值班列表
     *
     * @return string
     */
    public function list(Request $request)
    {
        # 验证
        $request->validate([
            'week' => 'required|numeric|max:20|min:1',
        ]);

        # 获取参数
        $week = request('week');

        $duty = Duty::where('week',$week)
            ->with('user:id,real_name,color')
            ->select('id','user_id','day','place','time')
            ->get();

        return $this->response(200,'ok',$duty);
    }

    /**
     * 申请记录
     *
     * @return string
    */
    public function apply_records(Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
            'page' => 'required|numeric'
        ]);

        $start = request('start');
        $end = request('end');
        $end = date("Y-m-d",strtotime("+1 day",strtotime($end))); // end 加+1天，因为传过来的日期是指今天0点，但是需要的是今天24点，即明天的0点

        $complements = Leave::select('user_id','auditor_id','id','audit_time','sign_time','audit_status')
            ->selectRaw('complement_created_at as created_at')
            ->where('week','!=',null)->whereBetween('complement_created_at',[$start,$end])
            ->with('user:id,real_name')->with('auditor:id,real_name')
            ->selectRaw("1 as type")->get()->toArray();
        
        $applies = Leave::select('user_id','auditor_id','id','created_at','audit_time','sign_time','audit_status')
            ->whereBetween('created_at',[$start,$end])
            ->with('user:id,real_name')->with('auditor:id,real_name')
            ->selectRaw("0 as type")->get()->toArray();

        $applies = array_merge($applies,$complements);
        foreach ($applies as $key => $apply){
            if ($applies[$key]['audit_status'] == 2) {
                $applies[$key]['duty_status'] = 2;
            }else{
                $applies[$key]['duty_status'] = $applies[$key]['sign_time'] == null ? 0 : 1;
            }
        }
        $data = collect($applies)->sortBy('created_at');


        return $this->response(200,'ok',$data);
    }


    /**
     * 申請詳情
     *
     * @param Request $request
     * @return false|string
     */
    public function apply_detail(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'type' => 'required|in:0,1'
        ]);

        $id = request('id');
        $type = request('type');

        if ($type == 0){
            $leave = Leave::where('id',$id)->with('user:id,real_name,department')
                ->select('reason','duty_id','user_id')->first();

            $data = $leave->toArray();
            $data['week'] = $leave->duty->week;
            $data['day'] = $leave->duty->day;
            $data['place'] = $leave->duty->place;
            $data['time'] = $leave->duty->time;

            $data['duty_stauts'] = $leave->sign_time == null ? 0 : 1;
        }else{
            $data = Leave::where('id',$id)->with('user:id,real_name,department')
                ->select('user_id','week','day','time','place')->first();

            $data->duty_stauts = $data->sign_time == null ? 0 : 1;
        }

        return $this->response(200,'ok',$data);
    }

    /**
     * 刪除申請
     *
     * @param Request $request
     * @return false|string
     */
    public function apply_delete(Request $request)
    {
        # 验证
        $request->validate([
            'id' => 'required|numeric',
            'type' => 'required|in:0,1'
        ]);

        # 获取参数
        $id = request('id');
        $type = request('type');

        if ($type == 0) 
            Leave::find($id)->delete();
        elseif ($type == 1) 
            Complement::find($id)->delete();

        return $this->response(200,'ok');
    }

    /**
     * 該天該地點值班人員
     *
     * @param Request $request
     * @return false|string
     */
    public function member(Request $request)
    {
        $request->validate([
            'day' => 'required|integer|between:1,5',
            'place' => 'required|in:0,1',
            'time' => 'required|in:0,1'
        ]);

        $week = $this->nowWeek();
        $day = request('day');
        $place = request('place');
        $time = request('time');

        $users = Duty::with('User:id,real_name,color')
            ->where('week',$week)->where('day',$day)->where('place',$place)->where('time',$time)
            ->select('id','user_id','day','place','time')
            ->get();

        return $this->response(200,'ok',$users);
    }

    /**
     * 編輯排班
     *
     * @param Request $request
     * @return false|string
     */
    public function change(Request $request)
    {
        # 验证
        $request->validate([
            'add' => 'array',
            'delete' => 'array',
            'add.*.day' => 'numeric|max:5|min:1',
            'add.*.place' => 'boolean',
            'add.*.time' => 'boolean',
            'delete.*.day' => 'numeric|max:5|min:1',
            'delete.*.place' => 'boolean',
            'delete.*.time' => 'boolean',
            'update_at' => 'required|boolean',
        ]);

        # 获取参数
        $adds = request('add');
        $deletes = request('delete');
        $update_at = request('update_at');
        $week = $this->nowWeek();

        $week = $update_at == 0 ? $week : $week + 1; // 本周更新 ： 下周更新

        if (isset($adds))
        {
            foreach ($adds as $add)
            {
                // 判断是否已存在此排班
                $user_id = User::where('openid',$add['openid'])->first()->id;
                $count = Duty::where('user_id',$user_id)
                    ->whereIn('week',[$week,$week + 1])
                    ->where('day',$add['day'])
                    ->whereIn('place',[0,1])
                    ->where('time',$add['time'])
                    ->count();

                if ($count > 0)
                    return $this->response(409,'该排班已存在，无法操作！');

                // 添加排班
                for($i = $week; $i <= 20; $i++)
                {
                    Duty::create([
                        'user_id' => $user_id,
                        'week' => $i,
                        'day' => $add['day'],
                        'place' => $add['place'],
                        'time' => $add['time'],
                    ]);
                }
            }
        }

        if (isset($deletes))
        {
            foreach ($deletes as $delete)
            {
                $user_id = User::where('openid',$add['openid'])->first()->id;
                Duty::where('user_id',$user_id)
                    ->where('day',$delete['day'])
                    ->where('place',$delete['place'])
                    ->where('time',$delete['time'])
                    ->where('week','>=',$week)
                    ->delete();
            }
        }

        return $this->response(200,'ok');
    }
}