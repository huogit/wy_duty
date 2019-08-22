<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\User;
use App\AddressBook;
use App\Duty_;
use Illuminate\Support\Facades\Redis;
use Maatwebsite\Excel\Facades\Excel;
use App\Leave;
use App\Complement;

class UserController extends \App\Http\Controllers\Controller
{
    /**
     * 用户列表
     *
     * @param Request $request
     * @return false|string
     */
    public function list(Request $request)
    {
        # 验证
        $request->validate([
           'page' => 'required|numeric',
           'orderBy' => 'required',
           'order' => 'required'
        ]); 

        # 获取参数
        $orderBy = request('orderBy');
        $order = request('order');

        # 获取模型
        $users = User::select('openid','username','real_name','department','class','phone','is_admin','remark');

        # 排序
        if ($orderBy == "real_name" || $orderBy == "username") 
        
            $users = $users->orderByRaw("convert(substr(".$orderBy.",1,1) using 'GBK') ".$order)
                ->orderBy('class','desc')->orderBy('department');
    
        elseif ($orderBy == "class")
            $users = $users->orderBy('class',$order)->orderBy('department');
        else
             $users = $users->orderBy('department',$order)->orderBy('class','desc');
    

        # 分页
        $users = $users->paginate(10);
        
        return $this->response(200,'ok',$users);
    }

    /**
    * 删除成员
    */
    public function delete(Request $request)
    {
        # 验证    
        $request->validate([
            'openid' => 'required|exists:users,openid'
        ]);

        # 获取参数
        $openid = request('openid');

        $user = User::where('openid',$openid);
        $token = $user->value('token');

        // 删除该用户的 登录状态
        Redis::del($token);

        // 删除该用户
        $user->delete();

        // 删除该用户的 排班，请假，补班
        Duty_::where('openid',$openid)->delete();
        Leave::where('openid',$openid)->delete();
        Complement::where('openid',$openid)->delete();


        return $this->response(200,'ok');
    }


    /**
    * 搜索成员
    * @return string
    */
    public function search()
    {
        // key不用验证，key为空时，即搜索所有

        # 获取参数
        $key = '%'.request('search_word').'%';

        $users = User::select('openid','username','real_name','department','class','phone','is_admin','remark')
            ->where('username','like',$key)
            ->orWhere('real_name','like',$key)
            ->orWhere('class','like',$key)
            ->orWhere('phone','like',$key)
            ->orWhere('remark','like',$key)
            ->orderBy('department')->orderBy('class','desc')
            ->paginate(10);

        if(count($users) == 0)
            return $this->response(204,'No Content');

        return $this->response(200,$users);
    }

    /**
     * 成员详细信息
     * @return string
    */
    public function user(Request $request)
    {
        # 验证
        $request->validate([
           'openid' => 'required|exists:users,openid'
        ]);

        $openid = request('openid');
        $result = User::where('openid',$openid)
            ->select('openid','username','real_name','department','class','major','phone','wechat_id','email','is_admin','remark')
            ->first();

        return $this->response(200,'ok',$result);
    }

    /**
     * 添加成员
     *
     * @return false|string
     * @throws \Illuminate\Validation\ValidationException
     */
    public function add()
    {
        # 参数验证
        $this->validate(request(),[
            'openid' => 'required',
            'real_name' => 'required',
            'is_admin' => 'required|boolean'
        ]);

        # 获取参数
        $openid = request('openid');
        $real_name = request('real_name');
        $is_admin = (boolean)request('is_admin');

        $params = compact('openid','real_name','is_admin');
        $user = User::where('openid',$openid);
        if ($user->count() == 0)
        {
            // 随机颜色
            $colors = ['#ee783a','#7199fd','#5e667d','#fb7967','#6ccb5c','#9c85d3','#25b4bf','#823fd3','#31db71','#4d4d4d',
                '#f9a69a','#1fdcea','#fbdc2c','#607d8b', '#7a35cd','#31d896','#4e64e4','#393a42'];
            $key = array_rand($colors);
            $color = $colors[$key];
            $params = array_merge($params,compact('color','token'));
            User::create($params);
        }
         else
         {
             $user->update($params);
         }

        return $this->response(200,'ok');
    }

    /** 
    * 值班人员 
    * @return string
    */
    public function needToDuty()
    {
        $users = User::select('openid','real_name','class')->get();

        return $this->response(200,'ok',$users);
    }


    /**
     * 导至通讯录
     *
     * @return false|string
     * @throws \Illuminate\Validation\ValidationException
     */
    public function to_addressBook()
    {
        # 参数验证
        $this->validate(request(),[
            'openid' => 'required|array',
            'openid.*' => 'exists:users,openid' // 验证数组里所有的数据
        ]);

        $openid = request('openid');

        $users = User::whereIn('openid',$openid)
            ->select('real_name','is_admin','department','class','phone','username','head_url','major','wechat_id','email','remark')
            ->selectRaw('openid as user_id') // 把openid 改名为 user_id 方便下面的create方法
            ->get()->toArray();

        //print_r($users);

        foreach ($users as $user)
        {
            $address = AddressBook::where('user_id',$user['user_id']);
            if ($address->count() == 0)   // 没有插入，有则更新
                AddressBook::create($user);
            else
               $address->update($user);
        }

        return $this->response(200,'ok');
    }
}