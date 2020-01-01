<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// 后台

// 登录模块
Route::post('/admin/login','Admin\LoginController@login');
Route::post('/admin/logout','Admin\LoginController@logout');

// 首页模块（大家都可以进）
Route::get('/admin/start', 'Admin\DutyController@start');                     /* 首页 */
Route::post('/admin/start/change', 'Admin\DutyController@start_change');      /* 更改首页 */

Route::group(['middleware' => 'auth:admin'], function() {

    // 值班模块
    Route::get('/admin/duty', 'Admin\DutyController@index');                    /* 排班管理页 */
    Route::get('/admin/duty/records', 'Admin\DutyController@records');         /* 签到记录 */
    Route::get('/admin/duty/list', 'Admin\DutyController@list');               /* 当周值班表*/
    Route::get('/admin/duty/member', 'Admin\DutyController@member');           /* 该天该地点的值班人员 */
    Route::post('/admin/duty/change', 'Admin\DutyController@change');          /* 改变排班 */
    Route::post('/admin/duty/check', 'Admin\DutyController@check');            /* 添加未签到记录 */

    // 申请模块
    Route::get('/admin/duty/apply/records', 'Admin\DutyController@apply_records');            /* 申请记录 */
    Route::get('/admin/duty/apply', 'Admin\DutyController@apply_detail');                     /* 申请详情 */
    Route::delete('/admin/duty/apply/delete', 'Admin\DutyController@apply_delete');           /* 删除申请 */
    Route::get('/admin/duty/apply/getAllLeaveCount','Admin\DutyController@getAllLeaveCount'); /* 请补次数 */

    // 用户模块
    Route::get('/admin/user/list', 'Admin\UserController@list');                     /* 用户列表 */
    Route::get('/admin/user/needToDuty', 'Admin\UserController@needToDuty');         /* 所有值班人员 */
    Route::get('/admin/user', 'Admin\UserController@user');                          /* 用户详细信息 */
    Route::get('/admin/user/search', 'Admin\UserController@search');                 /* 搜索用户 */
    Route::post('/admin/user/add', 'Admin\UserController@add');                      /* 添加用户 */
    Route::delete('/admin/user/delete', 'Admin\UserController@delete');              /* 删除用户 */
    Route::post('/admin/user/to_addressBook', 'Admin\UserController@to_addressBook');/* 导至通讯录 */

    // 通讯录模块
    Route::get('/admin/address_book/list', 'Admin\AddressBookController@list');          /* 通讯录列表 */
    Route::post('/admin/address_book/add', 'Admin\AddressBookController@add');           /* 添加联系人 */
    Route::get('/admin/address_book', 'Admin\AddressBookController@addressBook');        /* 联系人详细信息 */
    Route::get('/admin/address_book/search', 'Admin\AddressBookController@search');      /* 搜索联系人 */
    Route::delete('/admin/address_book/delete', 'Admin\AddressBookController@delete');   /* 删除联系人 */
    Route::post('/admin/address_book/import', 'Admin\AddressBookController@import');     /* 批量导入 */
});

