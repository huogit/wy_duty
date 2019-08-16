<?php

namespace App\Http\Controllers\Admin;

use App\addressBook;
use GuzzleHttp\Psr7\Request;
use Maatwebsite\Excel\Validators\Failure;
use App\AddressImport;


class AddressBookController extends \App\Http\Controllers\Controller
{

    /**
     * 用户列表
     *
     * @return false|string
     * @throws \Illuminate\Validation\ValidationException
     */
    public function list()
    {
        # 验证
        $this->validate(request(),[
           'page' => 'required|numeric',
           'orderBy' => 'required',
           'order' => 'required'
        ]); 

        # 获取参数
        $orderBy = request('orderBy');
        $order = request('order');

        # 获取模型
        $addressBook = addressBook::select('id','username','real_name','department','class','phone','remark');

        # 排序
        if ($orderBy == "real_name" || $orderBy == "username") 
        
            $addressBook = $addressBook->orderByRaw("convert(substr(".$orderBy.",1,1) using 'GBK') ".$order)
                ->orderBy('class','desc')->orderBy('department');
    
        elseif ($orderBy == "class")
            $addressBook = $addressBook->orderBy('class',$order)->orderBy('department');
        else
             $addressBook = $addressBook->orderBy('department',$order)->orderBy('class','desc');
    

        # 分页
        $addressBook = $addressBook->paginate(10);
        
        return $this->response(200,'ok',$addressBook);
    }

   	/**
     * 批量导入
     * @return string
    */
    public function import()
    {
//        $this->validate(request(),[
//            'file' => 'required'
//        ]);
        //dd($request);

        //Excel::import($import, 'users.xlsx');
        $import = new AddressImport();
        //$import->import('users.xlsx');
        
        $import->import(request()->file('inputFile'));
        // excel导入数据库时的错误集
        $errors = array();
        foreach ($import->errors() as $error)  // 去掉因为重复导入的错误，重复导入的错误就不用输出了。
        {
            if ($error->errorInfo[1] != 1062) // 错误码1062的是重复导入，为什么是1062，因为打印出来就是1062
                array_push($errors, $error->errorInfo[2]);
        }

        if (count($errors) > 0) 
            return $this->response(400,'excel表有误，有啥问题，看errors',$errors);
        
        return $this->response(200,'ok');
    }

    /**
     * 添加成员，更改信息
     * @return string
     * @throws \Illuminate\Validation\ValidationException
     */
    public function add()
    {
        # 验证
        $this->validate(request(),[
            'real_name' => 'required',
            'department' => 'required|numeric|max:3|min:0',
            'class' => 'required|numeric',
            'phone' => 'required|regex:/^1[34578]\d{9}$/',
            'major' => 'exists:majors,major',
            'email' => 'email',
        ]);

        # 获取参数
        $id = request('id');
        $real_name = request('real_name');
        $department = request('department');
        $class = request('class');
        $phone = request('phone');
        $major = request('major');
        $wechat_id = request('wechat_id');
        $email = request('email');
        $remark = request('remark');
        $head_url = request('head_url');
        
        $params = compact('user_id','real_name','department','class','phone','major','wechat_id','email','remark','head_url');

        $address = addressBook::where('id',$id);

        # 判断用户是否存在，有则更新，无则添加
        if ($address->count() == 0)
            addressBook::create($params);
        else
            $address->update($params);

        return $this->response(200,'ok');
    }

    /**
    * 搜索成员
    * @return string
    */
    public function delete()
    {
    	$this->validate(request(),[
    		'id' => 'required|exists:addressBook,id'
    	]);

    	$id = request('id');

    	addressBook::find($id)->delete();

    	return $this->response(200,'ok');
    }

    /**
    * 搜索联系人
    * @return string
    */
    public function search()
    {
        // # 验证    
        // $this->validate(request(),[
        //     'search_word' => 'required'
        // ]);   

        # 获取参数
        $key = '%'.request('search_word').'%';

        $address = addressBook::select('id','username','real_name','department','class','phone','is_admin','remark')
            ->where('username','like',$key)
            ->orWhere('real_name','like',$key)
            ->orWhere('class','like',$key)
            ->orWhere('phone','like',$key)
            ->orWhere('remark','like',$key)
            ->orderBy('department')->orderBy('class','desc')
            ->paginate(10);

        if(count($address) == 0)
            return $this->response(204,'No Content');

        return $this->response(200,$address);
    }

    /**
     * 联系人详细信息
     * @return string
    */
    public function addressBook()
    {
        # 参数验证
        $this->validate(request(),[
           'id' => 'required|exists:addressBook,id'
        ]);

        $id = request('id');
        $result = addressBook::where('id',$id)
            ->select('id','real_name','department','class','phone','major','wechat_id','email','remark')
            ->first();

        return $this->response(200,'ok',$result);
    }

}
