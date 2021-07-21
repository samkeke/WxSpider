<?php

namespace Spider\Controller;

use Think\Controller;

class IndexController extends Controller
{

    public function index()
    {
        $arr = array(
            'account' => 'zgmy_0810@163.com',
            'password' => '20170810_',
            'key' => "tmall",
        );
        $weChatLogin = new WeChatLoginController();
        $token = $weChatLogin->getToken();
        if(!empty($token)){
            return $token;
        }
        $status = $weChatLogin->init($arr);
        if(!$status){
            return false;
        }
        $token = $weChatLogin->getToken();
        if (!$token) {
            return false;
        }
        return $token;
    }

    public function uploadWechat(){

    }
}

