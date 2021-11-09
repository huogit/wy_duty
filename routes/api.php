<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// 前台

// 登陆模块
Route::post('/login','LoginController@login');                          /* 登录 */
Route::get('/get_openid','LoginController@get_openid');                 /* 获取openid */
Route::get('/auth','LoginController@auth');                             /* 回调授权 */
Route::get('/wechat_openid/{code}','LoginController@wechat_openid');    /* 微信openid */
Route::get('/index',"IndexController@index");
Route::group(['middleware' => 'CheckToken'], function() {

    // 值班模块
    Route::get('/duty/needToSign', 'DutyController@needToSign');          /* 是否需要签到 */
    Route::get('/duty/list', 'DutyController@list');                      /* 当天值班列表 */
    Route::post('/duty/sign_in', 'DutyController@sign_in');               /* 签到 */

    // 申请模块
    Route::get('/duty/apply', 'DutyController@apply');                    /* 申请页 */
    Route::get('/duty/apply/count', 'DutyController@count');              /* 申请时显示的请假、补班的人数 */
    Route::post('/duty/apply/complement', 'DutyController@complement');   /* 申请补班 */
    Route::post('/duty/apply/leave', 'DutyController@leave');             /* 申请请假 */
    Route::get('/duty/apply/me', 'DutyController@my_apply');              /* 我的申请 */
    Route::delete('/duty/apply/cancel', 'DutyController@cancel');         /* 取消申请 */

    Route::get('/duty/apply/audit', 'DutyController@audit')->middleware('CheckAdmin');          /* 审批页 */
    Route::post('/duty/apply/auditing', 'DutyController@auditing')->middleware('CheckAdmin');   /* 审批操作 */

    // 通讯录模块
    Route::get('/address_book/list', 'AddressBookController@list');                         /* 通讯录列表 */
    Route::get('/address_book/search', 'AddressBookController@search');                /* 搜索联系人 */
    Route::get('/address_book', 'AddressBookController@addressBook');             /* 联系人详细信息 */

    // 用户模块
    Route::get('/user/me','UserController@me');                             /* 我的信息 */
    Route::put('/user/me/update','UserController@update');                  /* 修改我的信息 */
    Route::get('/user/checkToken','UserController@checkToken');             /* 检查token是否有效 */
});


