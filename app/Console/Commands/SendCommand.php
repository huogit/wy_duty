<?php

namespace App\Console\Commands;

use App\Http\Controllers\SendController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Leave;
use App\Duty;
use App\Http\Controllers\Controller;


class SendCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '签到提醒';

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
//        dd('66');
        $controller = new Controller();
        // 假期
        $year = date('Y');
        $data = $controller->http_get("http://v.juhe.cn/calendar/year?year={$year}&key=355635c161094255ceb6cea0085c7792");
        $data = json_decode($data,true);
        $holiday_list = $data['result']['data']['holiday_list'];
        if (in_array($controller->nowDay(),[6,7]) || in_array(date('Y-n-j'),$holiday_list) || $controller->nowWeek() > 17)
            return;
        // 没有签到
        $week = $controller->nowWeek();
        $day = $controller->nowDay();
        $time = $controller->nowTime();

        $dutys = Duty::whereWdtp(compact('week', 'day', 'time'))->doesntHave('leave')->where('sign_time', null)
            ->select('user_id');
        $noSgins = Leave::whereWdtp(compact('week', 'day', 'time'))->where('sign_time', null)
            ->select('user_id')->union($dutys)->get();

        Log::info("week:{$week},day:{$day},time:{$time},有".count($dutys)."条未签到");
        foreach ($noSgins as $noSgin) {
            $openid = $noSgin->user->openid;
            $sendController = new SendController();
            $sendController->sendMessage($openid);
        }
    }
}

