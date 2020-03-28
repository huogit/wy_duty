# 值班表接口文档

修订日期：2020-03-28

## 重要说明

1. 在.env有个SCOOL_BEGIN_DATE 每个学期开学设置一下，用于计算当前为第几周，因为开学日期好像不太固定
2. 添加用户后，需要到官网把wechat_openid复制到值班表的数据库中https://duty.wangyuan.info/wechat_openid.html可以获取个人的公众号openid用户公众号推送，unionid方式需要微信开放平台的认证。
3. 后台账号 admin Wyzxgzs308



## 接口说明
### 1. token
> 小程序端的接口皆需传token参数来验证身份，格式'Bearer {token}' 注意 Bearer和token之间有个空格

```javascript
header: {
   Authorization: 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczpcL1wvZHV0eS5saXpoZW4xMjMuY25cL2FwaVwvdGVzdCIsImlhdCI6MTU1ODcwMzk1OCwiZXhwIjoxNTU4NzA3NTU4LCJuYmYiOjE1NTg3MDM5NTgsImp0aSI6IlJXa0tIcUU4dldyQVBSOVoiLCJzdWIiOjYsInBydiI6Ijg3ZTBhZjFlZjlmZDE1ODEyZmRlYzk3MTUzYTE0ZTBiMDQ3NTQ2YWEifQ.7fUqaI656K21Ril9VRJ-zKlK6uzfWg2Gao-piXAngB1Ye1aszHmiZFoczEYk3P03oMGhu3tL5MXO9r4fsHlNpzGXs63'
}
```

### 2. ajax请求
> 请求头加上这两个后laravel才会判定为ajax请求。加了后，当请求参数出现错误的时候才会正确返回。


```javascript
header: {
   'Content-Type': 'application/x-www-form-urlencoded',
   'X-Requested-With': 'XMLHttpRequest'
}
```

加了请求头的返回如下：

```json
{
   "message": "The given data was invalid.",
   "errors": {
       "page": [
           "page 必须是数字"
       ]
   }
}
```

没加请求的返回如下：

```json
Certificate test passed!
```

> 这是因为没加请求头 laravel当成了web请求而不是ajax请求，请求参数出现错误是，就跳转到了微信服务器的根目录，而不是返回json数据



## API根目录：[https://duty.wangyuan.info/api](http://duty.lizhen123.cn/api)
## 前台


## 登录
### 1.   /get_openid (获取openid)
#### 请求
请求方式：GET
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| code |  | 小程序wx.login方法返回的code | 是 |

#### 返回
返回参数：

| 参数名 | 值 | 说明 |
| :---: | :--- | :---: |
| openid | dfjoi23iofjiq9f03wikger | openid |

返回示例：

```javascript
{
   "code": 200,
   "message": "ok",
   "data": {
       "openid": "dfjoi23iofjiq9f03wikger"
   }
}
```

### 2.   /login   (登录)
#### 请求
请求方式：POST
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :---: | :---: | :---: |
| code |  | 小程序wx.login方法返回的code | 是 |
| gender |  |  | 是 |
| nickName |  |  | 是 |
| city |  |  | 是 |
| provinces |  |  | 是 |
| country |  |  | 是 |
| avatarUrl |  |  | 是 |

#### 返回
返回参数：

| 参数名 | 值 | 说明 |
| :---: | :--- | :---: |
| token |  | 登录令牌 |

返回示例：

```javascript
{
    "code": 200,
    "message": "ok",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczpcL1wvZHV0eS5saXpoZW4xMjMuY25cL2FwaVwvbG9naW4xIiwiaWF0IjoxNTU4NzA3MzE0LCJleHAiOjE1NTkzMTIxMTQsIm5iZiI6MTU1ODcwNzMxNCwianRpIjoibnZlaHYzTDIwYldBbGVndSIsInN1YiI6OSwicHJ2IjoiODdlMGFmMWVmOWZkMTU4MTJmZGVjOTcxNTNhMTRlMGIwNDc1NDZhYSJ9.GIffG_jNB9Jo9garBD1O78sJ_rjL_UUkefFkqUwc5ZU"
    }
}
```

### 3.   /user/checkToken  (判断此token是否存在) 
#### 请求
请求方式：GET
请求参数：无
#### 
#### 返回
返回参数：
返回示例：

```json
{
   "code": 200,
   "message": "token有效",
}
```

```json
{
   "code": 204,
   "message": "token无效",
}
```


## 值班页
### 1.   /duty/needToSign   (判断是否需要值班)
#### 请求
请求方式：GET
请求参数：无
#### 返回
返回参数：

| 参数名 | 值 | 说明 |
| :---: | :--- | :---: |
| needToSign | true or false | 是否需要值班 |

返回示例：

```json
{
    "code": 200,
    "message": "ok",
    "data": {
        "needToDuty": false
    }
}
```

### 2.   /duty/list   (当天值班列表)
#### 请求
请求方式：GET
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :---: | :---: | :---: |
| week | 1-20 | 第几周 | 是 |
| day | 1-5 | 星期几 | 是 |

#### 返回
返回参数：

| 参数名 | 值 | 说明 |
| :---: | :--- | :---: |
| real_name | "王永丰" | 姓名 |
| place | 0（网园）   1（行政楼） | 地点 |
| time | 0（5-6节） 1（7-8节） | 班次 |
| status | 0（待签到）1（已签到）2（已请假）3（未签到） | 状态 |

返回示例：

```json
{
    "code": 200,
    "message": "ok",
    "data": [
        {
            "real_nmae": "刘明杰",
            "place": 0,
            "time": 0,
            "status": 2
        },
        {
            "real_nmae": "冼志斌",
            "place": 0,
            "time": 0,
            "status": 1
        },
 }
```

### 3.   /duty/sign_in （签到）
#### 请求
请求方式：POST
请求参数：无
#### 返回
返回参数：无
返回示例：

```json
{
    "code": 200,
    "message": "已签到成功",
    "data": {
        "week": 1,
        "day": 1,
        "time": 0
    }
}
{
    "code": 403,
    "message": "不在你的签到时间",
    "data": {
        "week": 1,
        "day": 1,
        "time": 0
    }
}
```

## 请假补班页
### 1.   /duty/apply（请假补班页）
#### 请求
请求方式：GET
请求参数：无
#### 返回
返回参数：

| 参数名 | 值 | 说明 |
| :---: | :--- | :---: |
| current_week | 13 | 本周第几周 |
| auditor | 慧倩、银珊 | 审核人 |

返回示例：
```json
{
    "code": 200,
    "message": "ok",
    "data": {
        "auditor": [
            {
                "real_name": "谢慧倩",
                "real_name": "张银珊",
            }
        ],
        "current_week": 13
    }
}
```

### 2.   /duty/apply/count （该节多少人值班、请假） 
#### 请求
请求方式：GET
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| week | 1-20 | 第几周 | 是 |
| day | 1-5 | 星期几 | 是 |
| place | 0（网园）   1（行政楼） | 地点 | 是 |
| time | 0（5-6节） 1（7-8节） | 班次 | 是 |

#### 返回
返回参数：

| 参数名 | 值 | 说明 |
| :---: | :--- | :--- |
| duty_count | int | 值班总人数 |
| leave_count | int | 请假人数 |

返回示例：

```json
{
    "code": 200,
    "message": "ok",
    "data": {
        "duty_count": 4,
        "leave_count": 1
    }
}
```

### 3.   /duty/apply/leave （请假申请）
#### 请求
请求方式：POST
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| week | 1-20 | 第几周 | 是 |
| day | 1-5 | 星期几 | 是 |
| place | 0（网园）1（行政楼） | 地点 | 是 |
| time | 0（5-6节）1（7-8节） | 班次 | 是 |
| reason | "回家吃饭" | 原因 | 是 |

#### 返回
返回参数：无
返回示例：

```json
{
    "code": 201,
    "message": "Created,申请请假成功"
}
```

```json
{
    "code": 204,
    "message": "No Content,该班次你不需要值班，无法请假"
}
```

```json
{
    "code": 403,
    "message": "该班次已值班，无法请假"
}
```

### 4.   /duty/apply/complement （补班申请）
#### 请求
请求方式：POST
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| week | 1-20 | 第几周 | 是 |
| day | 1-5 | 星期几 | 是 |
| place | 0（网园）1（行政楼） | 地点 | 是 |
| time | 0（5-6节）1（7-8节） | 班次 | 是 |

#### 返回
返回参数：无 
返回示例：

```json
{
    "code": 201,
    "message": "Created,申请补班成功"
}
```

```json
{
    "code": 409,
    "message": "与值班时间冲突，无法申请补班"
}
```

## 我发起的
### 1.   /duty/apply/me （我发起的）
#### 请求
请求方式：GET
请求参数：无
#### 返回
返回参数：

| 参数名 | 值 | 说明 |
| :---: | :--- | :---: |
| type | 0（请假）1（补班） | 类型 |

返回示例：
```json
{
   "code": 204,
   "message": "No Content"
}
```

```json
{
    "code": 200,
    "message": "ok",
    "data": [
        {
            "id": 29,
            "openid": "oYjJN5YKsxYUlCvkG6LqP0-qhnQk",
            "week": 8,
            "day": 3,
            "place": 0,
            "time": 0,
            "auditor_id": "oYjJN5XIs_TolvHETy-10xRW3yAU",
            "audit_status": 1,
            "created_at": "2019-04-17 14:07:50",
            "type": 0,
            "auditor": {
                "openid": "oYjJN5XIs_TolvHETy-10xRW3yAU",
                "real_name": "吴琼"
            },
            "user": {
                "openid": "oYjJN5YKsxYUlCvkG6LqP0-qhnQk",
                "real_name": "李振"
            }
        },
        {
            "id": 412,
            "openid": "oYjJN5YKsxYUlCvkG6LqP0-qhnQk",
            "auditor_id": "oYjJN5YKsxYUlCvkG6LqP0-qhnQk",
            "audit_status": 1,
            "created_at": "2019-06-02 23:02:25",
            "duty_id": 1205,
            "reason": "测试no 审核",
            "type": 1,
            "week": 17,
            "day": 5,
            "place": 0,
            "time": 1,
            "auditor": {
                "openid": "oYjJN5YKsxYUlCvkG6LqP0-qhnQk",
                "real_name": "李振"
            },
            "user": {
                "openid": "oYjJN5YKsxYUlCvkG6LqP0-qhnQk",
                "real_name": "李振"
            }
        }
    ]
}
```


### 2.   /duty/apply/cancel （取消申请）
#### 请求
请求方式：DELETE
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| id |  | 请假条或补班条数据库中的id | 是 |
| type | 0（取消请假）1（取消补班） | 类型 | 是 |

#### 返回
返回参数：无
返回示例：

```json
{
   "code": 200,
   "message": "取消申请成功"
}
```

```json
{
   "code": 403,
   "message": "值班时间已过，取消请假申请失败"
}
```
### 
## 审批页
### 1.   /duty/apply/audit （审批页）
#### 请求
请求方式：GET
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| type | 0（待审批）1（已审批） | 类型 | 是 |

#### 返回
返回参数：

| 参数名 | 值 | 说明 |
| :--- | :--- | :---: |
| id |  | 请假条或补班条数据库中的id |
| audit_status | 1（通过） 2（拒绝）3 (已确认) | 审核状态 |
| auditor_name |  | 审核人的名字 |
| type | 0（请假） 1（补班）2（未签到） | 类型 |
| created_at |  | 申请时间 |
| week | 1-20 | 第几周 |
| day | 1-5 | 星期几 |
| time | 0（5-6节）1（7-8节） | 班次 |
| place | 0（网园）1（行政楼） | 地点 |
| real_name |  | 申请人姓名 |
| reason | 吃饭 | 申请原因 |

返回示例：

```json
{
    "code": 403,
    "message": "非管理员,无权限"
}
```

```json
{
    "code": 204,
    "message": "No Content"
}
```

```json
{
    "code":200,
    "message":"ok",
    "data":[
        {
            "real_name":"王勇峰",
            "week":1,
            "day":2,
            "place":0,
            "time":0,
            "reason":"吃饭",
            "created_at":"2019-02-15 14:09:03",
            "audit_status":0,
            "type":2,
            "auditor_name":"谢慧倩",
        },
        {
            "real_name":"王勇峰",
            "week":1,
            "day":2,
            "place":0,
            "time":0,
            "reason":"吃饭",
            "created_at":"2019-02-15 14:09:03",
            "audit_status":0,
            "type":1,
            "auditor_name":"张银珊"
        }
    ]
}
```

### 2.   /duty/apply/auditing （审核操作）
#### 请求
请求方式：POST
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| id |  | 请假条或补班条数据库中的id | 是 |
| type | 0（审核请假）1（审核补班）2（审核未签到） | 类型 | 是 |
| dispose | 1（通过）   2（拒绝） 3（扣钱） | 审核操作操作 | 是 |

#### 返回
返回参数：无
返回示例：

```json
{
    "code": 403,
    "message": "非管理员,无权限"
}
```

```json
{
    "code": 200,
    "message": "ok"
}
```

## 通讯录页
### 1.   /address_book/list （通讯录页）
#### 请求
请求方式：GET
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| orderBy | 'username' ，'real_name' , 'department' , 'class' | 以什么排序 | 是 |
| order | 'asc'（小到大） , 'desc'（大到小） | 正逆序 | 是 |
| page | int | 页码 | 是 |

#### 返回
返回参数：

| 参数名 | 值 | 说明 |
| :---: | :--- | :---: |
| current_page | int | 当前页 |
| last_page | int | 总页数 |
| total | int | 数据总数 |

返回示例：

```json
{
    "code": 200,
    "message": "ok",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 12,
                "username": "wyf",
                "real_name": "王永丰",
                "department": 3,
                "class": 2017,
                "phone": "1111111111",
                "is_admin": 0,
                "remark": null
            },
            {
                "id": 15,
                "username": null,
                "real_name": "667",
                "department": 1,
                "class": 2019,
                "phone": "13800138002",
                "is_admin": 0,
                "remark": "文秘部部长2"
            },
            {
                "id": 14,
                "username": null,
                "real_name": "666",
                "department": 1,
                "class": 2018,
                "phone": "13800138001",
                "is_admin": 0,
                "remark": "文秘部部长1"
            },
            {
                "id": 13,
                "username": "xhq",
                "real_name": "谢慧倩",
                "department": 0,
                "class": 2017,
                "phone": "1111111111",
                "is_admin": 1,
                "remark": null
            },
            {
                "id": 11,
                "username": "zys",
                "real_name": "张银珊",
                "department": 0,
                "class": 2017,
                "phone": "1111111111",
                "is_admin": 1,
                "remark": null
            }
        ],
        "first_page_url": "http://duty.lizhen123.cn/api/admin/address_book/list?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://duty.lizhen123.cn/api/admin/address_book/list?page=1",
        "next_page_url": null,
        "path": "http://duty.lizhen123.cn/api/admin/address_book/list",
        "per_page": 10,
        "prev_page_url": null,
        "to": 5,
        "total": 5
    }
}
```

### 2.   /address_book/search （搜索联系人）
#### 请求
请求方式：GET
请求参数：

| 参数名 | 值 | 说明 |
| :---: | :--- | :---: |
| search_word |  | 姓名 或 手机号 |

#### 返回
返回参数：↓
返回示例：

```json
{
    "code": 204,
    "message": "No Content"
}
```

```json
{
    "code": 200,
    "message": "ok",
    "data": [
        {
            "count": 5,
            "Address_books": [
                {
                    "id": 15,
                    "class": 2019,
                    "real_name": "667",
                    "phone": "13800138002"
                },
                {
                    "id": 14,
                    "class": 2018,
                    "real_name": "666",
                    "phone": "13800138001"
                },
                {
                    "id": 13,
                    "class": 2017,
                    "real_name": "谢慧倩",
                    "phone": "1111111111"
                },
                {
                    "id": 11,
                    "class": 2017,
                    "real_name": "张银珊",
                    "phone": "1111111111"
                },
                {
                    "id": 12,
                    "class": 2017,
                    "real_name": "王永丰",
                    "phone": "1111111111"
                }
            ]
        }
    ]
}
```

### 3.   /address_book（联系人详细信息）
#### 请求
请求方式：GET
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| id |  | 上面返回的id | 是 |

#### 返回
返回参数：

| 参数名 | 值 | 说明 |
| :---: | :--- | :---: |
| department | 0（文秘部）1（设计部）2（页面部）3（编程部） | 部门 |
| 。。。 | 。。。 | 。。。 |

返回示例：

```json
{
    "code": 200,
    "message": "ok",
    "data": {
        "head_url": "https://www.wyfeng.fun/image/logo.png",
        "username": "wyf",
        "real_name": "王永丰",
        "department": 3,
        "class": 2017,
        "phone": "1111111111",
        "wechat_id": "111",
        "email": "123@qq.com",
        "college": "信息科学与工程学院"
    }
}
```

## 我的页面
### 1.   /user/me （我的页面）
#### 请求
请求方式：GET
请求参数：
请求参数：
#### 返回
返回参数：↓
返回示例：

```json
{
    "code": 200,
    "message": "ok",
    "data": {
        "user": {
            "department": 3,
            "class": 2018,
            "major": "软件工程",
            "email": "123@qq.com",
            "phone": "13111111111",
            "wechat_id": "6666",
            "duty_count": 0,
            "complements_count": 0,
            "leaves_count": 0
        }, 
    }
}
```


### 2.   /user/me/update （修改我的信息）
#### 请求
请求方式：PUT
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| head_url |  | 微信头像 | 是 |
| username |  | 微信昵称 | 是 |
| department | 0（文秘部）1（设计部）2（页面部）3（编程部） | 管理员 | 如修改，是 |
| class | 2017 | 年级 | 如修改，是 |
| major | 软件工程 | 专业 | 如修改，是 |
| phone |  | 手机号 | 如修改，是 |
| email | [123@qq.com](mailto:123@qq.com) | 邮箱 | 如修改，是 |
| wechat_id | 文秘部部长 | 微信号 | 如修改，是 |

#### 返回
返回参数：无
返回示例：

```json
{
    "code": 200,
    "message": "ok"
}
```

## 后台

##  
> 以下接口若未登录则返回


```json
{
    "message": "Unauthenticated."
}
```

## 登录管理
### 1. /admin/login （登录）
#### 请求
请求方式：POST
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| username | min:2 max:5 | 用户名 | 是 |
| password | min:6 max:16 | 密码 | 是 |
| is_remember | 0：不记住 1：记住 | 记住我 | 是 |

#### 返回
返回参数：无
返回示例：

```json
{
    "code":200,
    "message":"登录成功"
}
```

```json
{
    "code": 403,
    "message": "用户名或密码错误"
}
```

### 2.   /admin/logout  （登出) 
#### 请求
请求方式：POST
请求参数：无
#### 返回
返回参数：无
返回示例：

```json
{
    "code": 200,
    "message": "退出登录成功",
}
```

## 值班管理
### 1.   /admin/duty/records （签到记录）
#### 请求
请求方式：GET
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| start | 2019-11-22 | 开始 | 是 |
| end | 2019-11-23 | 结束 | 是 |
| page | int | 页码 | 是 |

#### 返回
返回参数：

| 参数名 | 值 | 说明 |
| :---: | :--- | :---: |
| current_page | int | 当前页 |
| last_page | int | 总页数 |
| total | int | 数据总数 |

> 其它多出来的不用管，
> 数据已经按日期和地点排好序了

返回示例：
```json
{
    "code": 200,
    "message": "ok",
    "data": {
        "current_page": 3,
        "data": [
            {
                "day": 4,
                "place": 1,
                "time": 0,
                "created_at": "2019-02-12 14:47:08",
                "name": "刘明杰"
            },
            {
                "day": 4,
                "place": 1,
                "time": 0,
                "created_at": "2019-02-12 14:44:08",
                "name": "王永丰"
            }
        ],
        "first_page_url": "http://duty.test.cn:82/api/admin/duty_records?page=1",
        "from": 3,
        "last_page": 5,
        "last_page_url": "http://duty.test.cn:82/api/admin/duty_records?page=5",
        "next_page_url": "http://duty.test.cn:82/api/admin/duty_records?page=4",
        "path": "http://duty.test.cn:82/api/admin/duty_records",
        "per_page": 1,
        "prev_page_url": "http://duty.test.cn:82/api/admin/duty_records?page=2",
        "to": 3,
        "total": 5
    }
}
```

### 2.   /admin/duty （排班管理页）
#### 请求
请求方式：GET
请求参数：无
#### 返回
返回参数：
返回示例：
```json
{
    "code": 200,
    "message": "ok",
    "data": {
        "users": [
            {
                "real_name": "李振"
            },
            {
                "real_name": "谢慧倩"
            },
            {
                "real_name": "张银珊"
            },
            {
                "real_name": "王永丰"
            },
            {
                "real_name": "梁小玲"
            },
            {
                "real_name": "冼志斌"
            },
            {
                "real_name": "刘明杰"
            }
        ],
        "count": 7,
        "current_week": 1
    }
}
```

### 3.   /admin/duty/list（该周值班列表）
#### 请求
请求方式：GET
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| week | 1-20 | 第几周 | 是 |

#### 返回
返回参数：

| 参数名 | 值 | 说明 |
| :---: | :--- | :---: |
| duty_status | 0（未补班）1（已补班） | 申请条id |

返回示例：

```json
{
    "code": 200,
    "message": "ok",
    "data": [
        {
            "openid": "ozRoB5cCvbWYTjvcljHhMMXV6AhY",
            "day": 3,
            "place": 0,
            "time": 1,
            "user": {
                "openid": "ozRoB5cCvbWYTjvcljHhMMXV6AhY",
                "real_name": "王永丰",
                "color": "#fffff"
            }
        },
        {
            "openid": "wiefiwej23pofjweopfw",
            "day": 2,
            "place": 0,
            "time": 0,
            "user": {
                "openid": "wiefiwej23pofjweopfw",
                "real_name": "谢慧倩",
                "color": "#fffff"
            }
        },
        }
    ]
}
```

### 4.   /admin/duty/member（该日期该地点值班人员）
#### 请求
请求方式：GET
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| day | 1-5 | 星期几 | 是 |
| place | 0（网园）1（行政楼） | 地点 | 是 |

#### 返回
返回参数：
返回示例：
```json
{
    "code": 200,
    "message": "ok",
    "data": [
        {
            "openid": "fierigopo43934jg",
            "real_name": "梁小玲"
        },
        {
            "openid": "4w3ojf320f923jg0",
            "real_name": "王永丰"
        }
    ]
}
```

### 5.   /admin/user/needToDuty（所有值班人员）
#### 请求
请求方式：GET
请求参数：
#### 返回
返回参数：
返回示例：

```json
{
    "code": 200,
    "message": "ok",
    "data": [
        {
            "openid": "ozRoB5cCvbWYTjvcljHhMMXV6AhY",
            "real_name": "李振",
            "class": 2017
        },
        {
            "openid": "wiefiwej23pofjweopfw",
            "real_name": "谢慧倩",
            "class": 2017
        },
        {
            "openid": "giwei3ijo0f34jpgwwe",
            "real_name": "张银珊",
            "class": 2017
        },
        {
            "openid": "sf2309jf3wpokfw9e0ri",
            "real_name": "王永丰",
            "class": 2017
        },
        {
            "openid": "fierigopo43934jg",
            "real_name": "梁小玲",
            "class": 2016
        },
        {
            "openid": "wiwfhwuieht3i34",
            "real_name": "冼志斌",
            "class": 2017
        },
        {
            "openid": "vioeiogjwioejigojweio",
            "real_name": "刘明杰",
            "class": 2017
        }
    ]
}
```

### 6.   /admin/duty/change（编辑排班）
#### 请求
请求方式：POST
请求参数：

| 参数名 | 类型 | 值 | 说明 | 是否必填 |
| :--- | :--- | :--- | :---: | :---: |
| add | array |  | 添加的排班 | 否 |
| delete | array |  | 删除的排班 | 否 |
| update_at | bool | 0（立即更新）1（本周日） | 更新时间 | 是 |
| add[0]->openid | string |  | openid | 是 |
| add[0]->day | int | 1-5 | 星期几 | 是 |
| add[0]->place | bool | 0（网园）1 （行政楼） | 值班地点 | 是 |
| add[0]->time | bool | 0（5-6节）1（7-8节） | 值班时间 | 是 |

#### 返回
返回参数：无
返回示例：

```json
{
    "code": 200,
    "message": "ok",
}
```

```json
{
    "code": 409,
    "message": "该排班已存在，无法操作！",
}
```

### 7. /admin/duty/apply/getAllLeaveCount（请补统计）
#### 请求
请求方式：GET
请求参数：无
#### 返回
返回参数：无
返回示例：

```json
{
    "code": 200,
    "message": "ok",
    "data": [
        {
            "real_name": "谢慧倩",
            "complements_count": 0,
            "leaves_count": 1
        },
        {
            "real_name": "王永丰",
            "complements_count": 0,
            "leaves_count": 0
        }
    ]
}
```


##  成员管理
### 1.    /admin/user/list   (成员管理页)
#### 请求
请求方式：GET
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| orderBy | 'username' ，'real_name' , 'department' , 'class' | 以什么排序 | 是 |
| order | 'asc'（小到大） , 'desc'（大到小） | 正逆序 | 是 |
| page | int | 页码 | 是 |

#### 返回
返回参数：

| 参数名 | 值 | 说明 |
| :---: | :--- | :---: |
| current_page | int | 当前页 |
| last_page | int | 总页数 |
| total | int | 数据总数 |

返回示例：
```json
{
    "code": 200,
    "message": "ok",
    "data": {
        "current_page": 1,
        "data": [
            {
                "openid": "wiwfhwuieht3i34",
                "username": "xnzbin",
                "real_name": "冼志斌",
                "department": 3,
                "class": 2017,
                "phone": 1111111111,
                "is_admin": 0,
                "remark": null
            },
            {
                "openid": "vioeiogjwioejigojweio",
                "username": "lmj",
                "real_name": "刘明杰",
                "department": 3,
                "class": 2017,
                "phone": 1111111111,
                "is_admin": 0,
                "remark": null
            },
            {
                "openid": "sf2309jf3wpokfw9e0ri",
                "username": "66丰",
                "real_name": "王永丰",
                "department": 2,
                "class": 2017,
                "phone": 1111111111,
                "is_admin": 0,
                "remark": null
            },
        ],
        "first_page_url": "http://duty.lizhen123.cn/api/admin/user_list?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://duty.lizhen123.cn/api/admin/user_list?page=1",
        "next_page_url": null,
        "path": "http://duty.lizhen123.cn/api/admin/user_list",
        "per_page": 10,
        "prev_page_url": null,
        "to": 7,
        "total": 7
    }
}
```

### 2.   /admin/user/add   (添加成员) 
#### 请求
请求方式：POST
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| openid |  | openid | 是 |
| real_name |  | 真实姓名 | 是 |
| is_admin | 0（非管理员）1（管理员） | 管理员 | 是 |

#### 返回
返回参数: 无
返回示例：

```json
{
    "code":200,
    "message":"ok"
}
```

### 3.   /admin/user/delete   （删除成员）
#### 请求
请求方式：DELETE
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :--- | :---: |
| openid |  | 用户的openid | 是 |

#### 返回
返回参数：无
返回示例：
```json
{
    "code":200,
    "message":"ok"
}
```

### 4.   /admin/user/search （搜索成员）
#### 请求
请求方式：GET
请求参数：

| 参数名 | 值 | 说明 |
| :---: | :--- | :---: |
| search_word | /用户名/真实姓名/年级/手机号/备注 | 搜索关键字 |

#### 返回
返回参数：
返回示例：

```json
{
    "code": 200,
    "message": {
        "current_page": 1,
        "data": [
            {
                "openid": "giwei3ijo0f34jpgwwe",
                "username": "zys",
                "real_name": "张银珊",
                "department": 0,
                "class": 2017,
                "phone": 1111111111,
                "is_admin": 1,
                "remark": null
            }
        ],
        "first_page_url": "http://duty.lizhen123.cn/api/admin/user/search?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://duty.lizhen123.cn/api/admin/user/search?page=1",
        "next_page_url": null,
        "path": "http://duty.lizhen123.cn/api/admin/user/search",
        "per_page": 10,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    }
}
```

### 5.   /admin/user   (成员详细信息)
#### 请求
请求方式：GET
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| openid |  | openid | 是 |

#### 返回
返回参数：
返回示例：

```json
{
    "code": 200,
    "message": "ok",
    "data": {
        "openid": "wgeijwenvleojowjeognooe3",
        "head_url": null,
        "real_name": "张三",
        "department": 0,
        "class": 2017,
        "major": null,
        "phone": 13123123123,
        "wechat_id": null,
        "email": null,
        "is_admin": 0
    }
}
```

### 6. /admin/user/to_addressBook（导至通讯录）
#### 请求
请求方式：POST
请求参数：

| 参数名 | 数据类型 | 值 | 说明 | 是否必填 |
| :---: | :--- | :--- | :---: | :---: |
| openid | array |  | openid | 是 |

#### 返回
返回参数：无

```json
{
    "code": 200,
    "message": "ok",
}
```

## 通讯录管理
### 1.  /admin/address_book/list （通讯录页） 
#### 请求
请求方式：GET
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| orderBy | 'username' ，'real_name' , 'department' , 'class' | 以什么排序 | 是 |
| order | 'asc'（小到大） , 'desc'（大到小） | 正逆序 | 是 |
| page | int | 页码 | 是 |

#### 返回
返回参数：

| 参数名 | 值 | 说明 |
| :---: | :--- | :---: |
| current_page | int | 当前页 |
| last_page | int | 总页数 |
| total | int | 数据总数 |

返回示例：

```json
{
    "code": 200,
    "message": "ok",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 12,
                "username": "wyf",
                "real_name": "王永丰",
                "department": 3,
                "class": 2017,
                "phone": "1111111111",
                "is_admin": 0,
                "remark": null
            },
            {
                "id": 13,
                "username": "xhq",
                "real_name": "谢慧倩",
                "department": 0,
                "class": 2017,
                "phone": "1111111111",
                "is_admin": 1,
                "remark": null
            },
            {
                "id": 11,
                "username": "zys",
                "real_name": "张银珊",
                "department": 0,
                "class": 2017,
                "phone": "1111111111",
                "is_admin": 1,
                "remark": null
            }
        ],
        "first_page_url": "http://duty.lizhen123.cn/api/admin/Address_book/list?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://duty.lizhen123.cn/api/admin/Address_book/list?page=1",
        "next_page_url": null,
        "path": "http://duty.lizhen123.cn/api/admin/Address_book/list",
        "per_page": 10,
        "prev_page_url": null,
        "to": 3,
        "total": 3
    }
}
```

### 2.   /admin/address_book/add（添加联系人 or 更改信息）
#### 请求
请求方式：POST
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| id |  | 接口1返回的id | 如有id，是。添加联系人的时候是没有id，更新的时候是有id的嘛。 |
| real_name |  | 真实姓名 | 是 |
| department | 0（文秘部）1（设计部）2（页面部）3（编程部） | 管理员 | 是 |
| class | 2017 | 年级 | 是 |
| phone |  | 手机号 | 是 |
| major | 1 | 专业的id | 否 |
| wechat_id |  | 微信号 | 否 |
| email | [123@qq.com](mailto:123@qq.com) | 邮箱 | 否 |
| remark | 文秘部部长 | 备注 | 否 |
| head_url |  | 头像地址 | 如有上传，是 |

#### 返回
返回参数:  

| 参数名 | 值 | 说明 |
| :---: | :--- | :---: |
| duty_count |  | 值班次数 |
| complements_count |  | 补班次数 |
| leaves_count |  | 请假次数 |

返回示例：

```json
{
    "code": 200,
    "message": "ok",
    "data": {
        "user": {
            "department": 3,
            "class": 2017,
            "major": "软件工程",
            "email": "123@qq.com",
            "phone": "1111111111",
            "wechat_id": "111",
            "duty_count": 0,
            "complements_count": 0,
            "leaves_count": 0
        },
        "majors": [
            {
                "major": "软件工程"
            },
            {
                "major": "数字媒体技术"
            },
            {
                "major": "生物工程"
            },
            {
                "major": "电子信息科学与技术"
            },
            {
                "major": "自动化"
            },
            {
                "major": "信息管理"
            }
        ]
    }
}
```

### 3.    （上传头像）
### [https://duty-1256628303.cos.ap-guangzhou.myqcloud.com](https://duty-1256628303.cos.ap-guangzhou.myqcloud.com)
#### 请求
请求方式：POST
请求参数：
formdata ↓

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| file |  | 文件 | 是 |
| key | head_img/{openid}_{时间戳}.jpg
 如：head_img/wgeijwenvleojowjeognooe3_1538491409000.jpg | 上传到服务器的路径 | 是 |

#### 返回
返回参数：
响应头 ↓

| 参数名 | 值 | 说明 |
| :---: | :--- | :---: |
| Location | [http://duty-1256628303.cos.ap-guangzhou.myqcloud.com/head_img/wgeijwenvleojowjeognooe3_1538491409000.jpg](http://duty-1256628303.cos.ap-guangzhou.myqcloud.com/head_img/wgeijwenvleojowjeognooe3_1538491409000.jpg) | 头像地址，用于下面的请求参数head_url |

### 4.   /admin/address_book/delete（删除联系人）
#### 请求
请求方式：DELETE
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| id |  | 接口1的返回的id | 是 |

返回参数:  无
返回示例：

```json
{
    "code": 200,
    "message": "ok"
}
```

### 5.   /admin/address_book/search（搜索联系人）
#### 请求
请求方式：GET
请求参数：

| 参数名 | 值 | 说明 |
| :---: | :--- | :---: |
| search_word | /用户名/真实姓名/年级/手机号/备注 | 搜索关键字 |

#### 返回
返回参数：
返回示例：

```json
{
    "code": 200,
    "message": {
        "current_page": 1,
        "data": [
            {
                "id": 13,
                "username": "xhq",
                "real_name": "谢慧倩",
                "department": 0,
                "class": 2017,
                "phone": "1111111111",
                "is_admin": 1,
                "remark": null
            },
            {
                "id": 11,
                "username": "zys",
                "real_name": "张银珊",
                "department": 0,
                "class": 2017,
                "phone": "1111111111",
                "is_admin": 1,
                "remark": null
            },
            {
                "id": 15,
                "username": null,
                "real_name": "667",
                "department": 1,
                "class": 2019,
                "phone": "13800138002",
                "is_admin": 0,
                "remark": "文秘部部长2"
            },
            {
                "id": 14,
                "username": null,
                "real_name": "666",
                "department": 1,
                "class": 2018,
                "phone": "13800138001",
                "is_admin": 0,
                "remark": "文秘部部长1"
            },
            {
                "id": 12,
                "username": "wyf",
                "real_name": "王永丰",
                "department": 3,
                "class": 2017,
                "phone": "1111111111",
                "is_admin": 0,
                "remark": null
            }
        ],
        "first_page_url": "http://duty.lizhen123.cn/api/admin/address_book/search?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://duty.lizhen123.cn/api/admin/address_book/search?page=1",
        "next_page_url": null,
        "path": "http://duty.lizhen123.cn/api/admin/address_book/search",
        "per_page": 10,
        "prev_page_url": null,
        "to": 5,
        "total": 5
    }
}
```

### 6.   （联系人详细信息）
#### 请求
请求方式：GET
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| id |  | 接口1返回的id | 是 |

#### 返回
返回参数：
返回示例：

```json
{
    "code": 200,
    "message": "ok",
    "data": {
        "id": 11,
        "real_name": "张银珊",
        "department": 0,
        "class": 2017,
        "phone": "1111111111",
        "major": "数字媒体",
        "wechat_id": "111",
        "email": "123@qq.com",
        "remark": null,
        "is_admin": 1,
        "head_url": "https://www.wyfeng.fun/image/logo.png"
    }
}
```

### 7.   （下载EXCEL） 
[https://duty-1256628303.cos.ap-guangzhou.myqcloud.com/address_book.xlsx](https://duty-1256628303.cos.ap-guangzhou.myqcloud.com/address_book.xlsx)
### 8.   /admin/address_book/import （批量导入）
#### 请求
请求方式：POST
请求参数：

| 参数名 | 类型 | 值 | 说明 | 是否必填 |
| :---: | :--- | :--- | :---: | :---: |
| file | file |  | excel文件 | 是 |

#### 返回
返回参数：无
返回示例：
```json
{
    "code": 200,
    "message": "ok",
}
```

## 审批管理
### 1.   /admin/duty/apply/records （申请记录）
#### 请求
请求方式：GET
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| start | 2019-11-22 | 开始 | 是 |
| end | 2019-11-23 | 结束 | 是 |
| page | int | 页码 | 是 |

#### 返回
返回参数：

| 参数名 | 值 | 说明 |
| :---: | :--- | :---: |
| id |  | 请假条或补班条的id |
| created_at |  | 发起时间 |
| audit_time |  | 完成时间 |
| duty_status | 0（未补班）1（已补班）2（已回拒） | 值班状态 |
| type | 0（请假）1（补班） | 申请条类型 |

返回示例：

```json
{
    "code": 200,
    "message": "ok",
    "data": [
        {
            "openid": "ozRoB5cCvbWYTjvcljHhMMXV6AhY",
            "auditor_id": "giwei3ijo0f34jpgwwe",
            "id": 1,
            "created_at": "2019-02-15 10:25:05",
            "audit_time": "2019-02-15 10:25:05",
            "duty_status": 0,
            "type": 0,
            "user": {
                "openid": "ozRoB5cCvbWYTjvcljHhMMXV6AhY",
                "real_name": "李振"
            },
            "auditor": {
                "openid": "giwei3ijo0f34jpgwwe",
                "real_name": "张银珊"
            }
        },
        {
            "openid": "fierigopo43934jg",
            "auditor_id": null,
            "id": 14,
            "created_at": "2019-02-15 16:05:17",
            "audit_time": null,
            "duty_status": 0,
            "type": 1,
            "user": {
                "openid": "fierigopo43934jg",
                "real_name": "梁小玲"
            },
            "auditor": null
        },
    ]
}
```

### 2.   /admin/duty/apply/delete（删除申请记录）
#### 请求
请求方式：DELETE
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| id |  | 申请条id | 是 |
| type | 0（删除请假）1（删除补班） | 类型 | 是 |

#### 返回
返回参数：无
返回示例：

```json
{
    "code":200,
    "message":"ok"
}
```

### 3.   /admin/duty/apply （申请记录详情）
#### 请求
请求方式：GET
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| id |  | 申请条id | 是 |
| type | 0（请假）1（补班） | 类型 | 是 |

#### 返回
返回参数：

| 参数名 | 值 | 说明 |
| :---: | :--- | :---: |
| duty_status | 0（未补班）1（已补班） | 申请条id |

返回示例：

```json
{
    "code": 200,
    "message": "ok",
    "data": [
        {
            "openid": "ozRoB5cCvbWYTjvcljHhMMXV6AhY", // 不用管
            "week": 1,
            "day": 4,
            "time": 0,
            "place": 0,
            "duty_status": 0,
            "reason": null,
            "user": {
                "openid": "ozRoB5cCvbWYTjvcljHhMMXV6AhY", // 不用管
                "real_name": "李俊豪",
                "department": 3
            }
        }
    ]
}
```

## 启动页
### 1.   /admin/start （启动页）
#### 请求
请求方式：GET
请求参数：无
#### 返回
返回参数：

| 参数名 | 值 | 说明 |
| :---: | :--- | :---: |
| top_bg_color |  | 顶部栏的背景颜色 |
| top_word_color |  | 顶部栏的字体颜色 |
| bottom_word_color |  | 底部按钮背景颜色 |
| bottom_button_color |  | 底部按钮字体颜色 |
| start_img_url |  | 下载该图 |

返回示例：

```json
{
    "code": 200,
    "message": "ok",
    "data": {
        "top_bg_color": "#fff",
        "top_word_color": "#fff",
        "bottom_word_color": "#fff",
        "bottom_button_color": "#fff",
        "start_img_url": ""
    }
}
```

### 2. （上传启动页背景图）   
### [https://duty-1256628303.cos.ap-guangzhou.myqcloud.com](https://duty-1256628303.cos.ap-guangzhou.myqcloud.com)
#### 请求
请求方式：POST
请求参数：
formdata ↓

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| file |  | 文件 | 是 |
| key | start_img/{时间戳}.jpg 
如：start_img/1538491409000.jpg | 上传到服务器的路径 | 是 |

#### 返回
返回参数：
响应头 ↓

| 参数名 | 值 | 说明 |
| :---: | :--- | :---: |
| Location | [http://duty-1256628303.cos.ap-guangzhou.myqcloud.com/start_img/1538491409000.jpg](http://duty-1256628303.cos.ap-guangzhou.myqcloud.com/start_img/1538491409000.jpg) | 头像地址，用于下面的请求参数head_url |

### 3.   /admin/start/change （更改启动页颜色和背景图片）
#### 请求
请求方式：POST
请求参数：

| 参数名 | 值 | 说明 | 是否必填 |
| :---: | :--- | :---: | :---: |
| top_bg_color |  | 顶部栏的背景颜色 | 否 |
| top_word_color |  | 顶部栏的字体颜色 | 否 |
| bottom_word_color |  | 底部按钮背景颜色 | 否 |
| bottom_button_color |  | 底部按钮字体颜色 | 否 |
| start_img_url |  | 上传首页背景图时返回的响应头地址 | 如有上传，是 |

#### 返回
返回参数：无
返回示例：
```json
{
    "code": 200,
    "message": "ok",
}
```

