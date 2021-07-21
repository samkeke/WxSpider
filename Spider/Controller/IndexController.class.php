<?php

namespace Spider\Controller;

use Think\Controller;

class IndexController extends Controller
{

    public function index()
    {
        $arr = array(
            'account' => 'lampwxy@163.com',
            'password' => 'wxy1314520',
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

