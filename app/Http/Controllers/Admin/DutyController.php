<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/1
 * Time: 13:41
 */

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Duty_table;
use App\Complement;
use App\Leave;
use App\User;

class DutyController extends \App\Http\Controllers\Controller
{

    /**
     * 首页
     * @return mixed
     */
    public function start()
    {
        $top_bg_color = config('startpage.top_bg_color'); //Redis::exists('top_bg_color') ? Redis::get('top_bg_color') : '#CCCCCC';
        $top_word_color = config('startpage.top_word_color'); //Redis::exists('top_word_color') ? Redis::get('top_word_color') : '#FFFFFF';
        $bottom_word_color = config('startpage.bottom_word_color'); //Redis::exists('bottom_word_color') ? Redis::get('bottom_word_color') : '#FFFFFF';
        $bottom_button_color = config('startpage.bottom_button_color'); //Redis::exists('bottom_button_color') ? Redis::get('bottom_button_color') : '#CCCCCC';

        $start_img_url = config('startpage.start_img_url');//Redis::exists('start_img_url') ? Redis::get('start_img_url') : null;

        return $this->response(200,'ok',compact('top_bg_color','top_word_color','bottom_word_color','bottom_button_color','start_img_url','session'));
   }

    /**
     * 更改首页样式
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
            Redis::set('top_bg_color',request('top_bg_color'));

        if (!empty($twc))
            Redis::set('top_word_color',request('top_word_color'));
        if (!empty($bbc))
            Redis::set('bottom_word_color',request('bottom_button_color'));
        if (!empty($bwc))
            Redis::set('bottom_button_color',request('bottom_word_color'));

        if (!empty($siu))
            Redis::set('start_img_url',request('start_img_url'));

        return $this->response(200,'ok');
    }


    /**
     * 值班管理页
     * @return string
     */
    public function index()
    {
        $current_week = $this->nowWeek();
        $users = User::where('status',1)->select('real_name');

        $count = $users->count();
        $users= $users->get();

        return $this->response(200,'ok',compact('users','count','current_week'));
    }


    /**
     * 签到记录
     * @return string
     */
    public function records(Request $request)
    {
        # 验证
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
            'page' => 'required|numeric'
        ]);

        # 获取参数
        $start = request('start');
        $end = request('end');
        $end = date("Y-m-d",strtotime("+1 day",strtotime($end)));

        // 先 join 再 union
        $complements = DB::table('duty_records')
            ->where('type',1)
            ->whereBetween('duty_records.created_at',[$start,$end])
            ->join('complements','duty_records.foreign_id','=','complements.id')
            ->join('users','duty_records.openid','=','users.openid')
            ->select('complements.day','complements.place','complements.time','duty_records.created_at','users.real_name');

        $dutys = DB::table('duty_records')
            ->where('type',0)
            ->whereBetween('duty_records.created_at',[$start,$end])
            ->join('duty_table','duty_records.foreign_id','=','duty_table.id')
            ->join('users','duty_records.openid','=','users.openid')
            ->select('duty_table.day','duty_table.place','duty_table.time','duty_records.created_at','users.real_name')
            ->union($complements)
            ->orderByRaw('date(created_at) DESC')   // 用原生 按日期排序而不是按时间排序
            ->orderBy('place')
            ->paginate(10);

        return $this->response(200,'ok',$dutys);
    }

    /**
     * 该周值班列表
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

        $duty_table = Duty_table::where('week',$week)
            ->with('user:openid,real_name,color')
            ->select('openid','day','place','time')
            ->get();

        return $this->response(200,'ok',$duty_table);
    }

    /**
     * 申请记录
     * @return string
    */
    public function apply_records(Request $request)
    {
        # 验证
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
            'page' => 'required|numeric'
        ]);

        # 获取参数
        $start = request('start');
        $end = request('end');
        $end = date("Y-m-d",strtotime("+1 day",strtotime($end))); // end 加+1天，因为传过来的日期是指今天0点，但是需要的是今天24点，即明天的0点

        $complements = Complement::select('openid','auditor_id','id','created_at','audit_time','duty_status')
            ->whereBetween('created_at',[$start,$end])    
            ->with('user:openid,real_name')->with('auditor:openid,real_name')
            ->selectRaw("1 as type");
        
        $applies = Leave::select('openid','auditor_id','id','created_at','audit_time','duty_status')
            ->with('user:openid,real_name')->with('auditor:openid,real_name')
            ->whereBetween('created_at',[$start,$end])
            ->where('type',0)
            ->selectRaw("0 as type")
            ->union($complements)
            ->orderBy('created_at','desc')
            ->paginate(10);

        return $this->response(200,'ok',$applies);
    }

    /**
     * 申请详情
     * @return string
    */
    public function apply_detail(Request $request)
    {
        # 验证
        $request->validate([
            'id' => 'required|numeric',
            'type' => 'required|boolean'
        ]);

        # 获取参数
        $id = request('id');
        $type = request('type');

        if ($type == 0) 
            $result = Leave::where('leaves.id',$id)
                ->join('duty_table','leaves.duty_id','=','duty_table.id')
                ->select('leaves.openid','duty_table.week','duty_table.day','duty_table.time','duty_table.place','leaves.duty_status')
                ->with('user:openid,real_name,department')
                ->get();
        elseif ($type == 1) 
            $result = Complement::where('id',$id)
                ->with('user:openid,real_name,department')
                ->select('openid','week','day','time','place','duty_status')
                ->selectRaw("null as reason")
                ->get();

        return $this->response(200,'ok',$result);
    }

    /**
     * 删除申请
     * @return string
    */
    public function apply_delete(Request $request)
    {
        # 验证
        $request->validate([
            'id' => 'required|numeric',
            'type' => 'required|boolean'
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
     * 该天该地点值班人员
     * @return string
    */
    public function member(Request $request)
    {
        # 验证
        $request->validate([
            'day' => 'required|numeric|max:5|min:1',
            'place' => 'required|boolean',
            'time' => 'required|boolean'
        ]);

        # 获取参数
        $week = $this->nowWeek();
        $day = request('day');
        $place = request('place');
        $time = request('time');

        $users = Duty_table::with('User:openid,real_name,color')
            ->where('week',$week)->where('day',$day)->where('place',$place)->where('time',$time)
            ->select('openid','day','place','time')
            ->get();

        return $this->response(200,'ok',$users);
    }

    /**
     * 编辑排班
     * @return string
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
                $count = Duty_table::where('openid',$add['openid'])
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
                    Duty_table::create([
                        'openid' => $add['openid'],
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
                Duty_table::where('openid',$delete['openid'])
                    ->where('day',$delete['day'])
                    ->where('place',$delete['place'])
                    ->where('time',$delete['time'])
                    ->where('week','>=',$week)
                    ->delete();
            }
        }

        return $this->response(200,'ok');
    }

     /**
     * 查询是否未签到，添加未签到记录
     * @return string
    */
    public function check()
    {
        //echo date('H:i:s');
        # 获取当前时间
        $week = $this->nowWeek();
        $day = $this->nowDay();

//        $week = 6;
//        $day = 3;

        # 当周当天，值班补班的
        $dutys = Duty_table::where('week',$week)->where('day',$day);
        $complements = Complement::where('week',$week)->where('day',$day);

        // 判断现在的时间,16:30:00后才可以添加5-6节未签到记录，18:00后才可以添加7-8节的未签到记录。
        if (date('H:i') >= '16:30' && date('H:i') <= '18:00')
        {
            // 没有签到记录 并且 没有未签到记录，添加未签到记录
            $dutys = $dutys->where('time',0)->doesntHave('duty_record');
            $complements = $complements->where('time',0)->doesntHave('duty_record');
            echo '56节';
        }
        elseif (date('H:i') > '18:00' && date('H:i') < '21:00')
        {
            // 没有签到记录，添加未签到记录
            $dutys = $dutys->where('time',1)->doesntHave('duty_record');
            $complements = $complements->where('time',1)->doesntHave('duty_record');
            echo '78节';
        }
        else
        {
            return;
        }

        // 未值班
        if ($dutys->count() > 0)
        {
            echo '有未签到记录'.$dutys->count().'条'."\n";
            foreach ($dutys->get() as $duty) 
            {
                $qingjia = Leave::where('duty_id',$duty->id)->where('type',0);// 请假记录
                if ($qingjia->count() == 1)// 有请假记录，不用添加未签到记录
                {
                    echo '但请假了'."\n";
                    continue;
                }
                $leave = Leave::where('duty_id',$duty->id)->where('type',1); // 未签到记录
                if ($leave->count() == 0) // 没有未签到记录,添加未签到记录
                {
                    $duty_id = $duty->id;
                    $type = 1;
                    $openid = $duty->openid;
                    Leave::create(compact('openid','type','duty_id'));
                    echo '添加未签到记录成功'."\n";
                }
                echo '已有未签到记录'."\n";
            }
        }
        else
        {
            echo '没有未签到'."\n";
        }

        // 未补班
        if ($complements->count() > 0)
        {
            echo '有未补班记录'.$complements->count().'条'."\n";
            // 判断是否已有未签到记录
            foreach ($complements->get() as $complement)
            {
                $leave = Leave::where('duty_id', $complement->duty_id)->where('type', 1); // 未签到记录
                if ($leave->count() == 0)
                {
                    $duty_id = $complement->duty_id;
                    $type = 1;
                    $openid = $complement->openid;
                    Leave::create(compact('openid', 'type', 'duty_id'));
                    echo '添加未签到记录成功'."\n";
                }
            }
        }
        else
        {
            echo '没有未补班'."\n";
        }
    }

}