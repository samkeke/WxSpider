<?php

namespace WxSpider;

use phpspider\core\db;
use WxSpider\Service\WeChatLoginController;

class ServiceController
{

    private $account;
    private $password;
    private $dbConfig;
    public function __construct($account , $password , $dbConfig = [])
    {
        $this->account = $account;
        $this->password = $password;
        $this->password = $password;
        $this->dbConfig = $dbConfig;
        // 数据库配置
        db::set_connect('default', $dbConfig);
        // 数据库链接
        db::_init();
    }

    public function weChatLogin()
    {
        $arr = array(
            'account' => $this->account,
            'password' => $this->password,
            'key' => "tmall",
        );
        $weChatLogin = new WeChatLoginController($this->dbConfig);
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
}

