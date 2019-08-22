<?php

namespace App\Console\Commands;

use App\Duty;
use App\Http\Controllers\Controller;
use Illuminate\Console\Command;

class kaoqin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $controller = new Controller();
        // 假期
        $year = date('Y');
        $data = $controller->http_get("http://v.juhe.cn/calendar/year?year={$year}&key=355635c161094255ceb6cea0085c7792");
        $data = json_decode($data,true);
        $holiday_list = $data['result']['data']['holiday_list'];
        if (in_array($controller->nowDay(),[6,7]) || in_array(date('Y-n-j'),$holiday_list))
            return;

        // 没有签到
        $week = $controller->nowWeek();
        $day = $controller->nowDay();
        $time = $controller->nowTime();
        $dutys = Duty::whereWdtp(compact('week','day','time'))->where('sign_time',null)->get();
        print_r($dutys);
        foreach ($dutys as $duty) {
            $openid = $duty->user->wechat_openid;
            $data = [
                "first" => [
                    "value" => "还没有打考勤",
                    "color" => "#173177"
                ],
                "keyword1" => [
                    "value" => "你",
                    "color" => "#173177"
                ],
                "keyword2" => [
                    "value" => '签到',
                    "color" => "#173177"
                ],
                "remark" => [
                    "value" => "请及时进行考勤",
                    "color" => "#173177"
                ],
            ];
            $controller->sendTplMessage($openid, '考勤提醒', $data);
        }

    }
}
