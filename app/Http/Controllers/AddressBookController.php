<?php

namespace App\Http\Controllers;

use App\AddressBook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressBookController extends Controller
{
	/**
     * 通讯录列表
     *
     * @return string
     */
    public function list()
    {
        $addressBooks = AddressBook::select('id','class','real_name','phone','username','is_admin','remark','department')->orderBy('class','desc')->orderBy('department');
        $count = $addressBooks->count();
        $addressBooks = $addressBooks->get();
        $data = compact('count','addressBooks');

        return $this->response(200,'ok',$data);
    }

    /**
     * 搜索联系人
     *
     * @return string
     */
    public function search()
    {
        $search_word = request('search_word');
        $search_word = '%'.$search_word.'%';

        $result_list = AddressBook::where('real_name','like',$search_word)
            ->orWhere('phone','like',$search_word)
            ->select('id','class','real_name','phone')
            ->orderBy('class','desc')
            ->get();

        if (count($result_list) == 0)
            return $this->response(204,'No Content');

        return $this->response(200,'ok',$result_list);
    }

    /**
     * 用户详细信息
     *
     * @param Request $request
     * @return string
     */
    public function addressBook(Request $request)
    {
        $request->validate([
           'id' => 'required|exists:addressBook,id'
        ]);

        $id = request('id');

        $address = AddressBook::where('id',$id)
            ->select('head_url','username','real_name','department','class','major','phone','wechat_id','email')
            ->first()->toArray();

        // 添加学院
        $college_id = DB::table('majors')->where('major',$address['major'])->value('college_id');
        $college = DB::table('colleges')->where('id',$college_id)->value('college');
        unset($address['major']);

        $address = array_merge($address,['college' => $college]);

        return $this->response(200,'ok',$address);
    }

}
