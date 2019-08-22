<?php

namespace App;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // 标题行，加了这个才能$row['openid']这样，不然只能$row[0]
use Maatwebsite\Excel\Concerns\SkipsOnError; // 跳过错误，比如插入具有相同的openid的两行
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Illuminate\Support\Facades\DB;


// 这不是一个模型，只是这像是操作数据库的一个类，就放在这里\APP下了
class AddressImport implements ToModel, WithHeadingRow, SkipsOnError
{
	use Importable, SkipsErrors;


  /**
   * @param array $row
   *
   * @return AddressBook
   */
  public function model(array $row)
  {
        // 去掉第一条例子
        if ($row['department'] == '吃瓜部')
          return null;

        // 去掉重复导入的(标识：通讯录表一般没有openid,因为老成员是没有openid的，所以手机号作为标识)
        $address  = DB::table('AddressBook')->where('phone',$row['phone']);
        // 手机号已存在，pass
        if ($address->count() ==  1)
          return null;

        switch ($row['department'])
        {
          case '文秘部':$row['department'] = 0;break;
          case '设计部':$row['department'] = 1;break;
          case '页面部':$row['department'] = 2;break;
          case '编程部':$row['department'] = 3;break;
        }

        return new AddressBook([
           'user_id'   => $row['openid'],
           'real_name' => $row['real_name'],
           'department' => $row['department'],
           'class' => $row['class'],
           'phone' => $row['phone'],
           'major' => $row['major'],
           'wechat_id' => $row['wechat_id'],
           'email' => $row['email'],
           'remark' => $row['remark'],
        ]);
  }

}