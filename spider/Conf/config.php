<?php

return array(
    "SPIDER" => array(
        'name' => '看书海小说单个小说爬取',
        'log_show' => true,
        'tasknum' => 1,
        'max_try' => 5,
        'interval' => 700,
        'log_type' => "warn,error",
//    'multiserver' => true,
//    'save_running_state' => true,
        //爬取的域名列表
        'domains' => array(
            'mp.weixin.qq.com',
        ),
        'queue_config' => array(
            'host' => '127.0.0.1',
            'port' => 6379,
            'pass' => '',
            'db' => 5,
            'prefix' => 'phpspider',
            'timeout' => 30,
        ),
        //抓取的起点
        'scan_urls' => $GLOBALS['url'],
        //列表页实例
        'list_url_regexes' => $GLOBALS['url'],
        //内容页实例 要爬取的页面
        //  \d+  指的是变量
        'content_url_regexes' => $GLOBALS['url'],

        'user_agent' => array(
            //PC端的UserAgent
            "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.57 Safari/536.11",
            "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-us) AppleWebKit/534.50 (KHTML, like Gecko) Version/5.1 Safari/534.50",
            "Mozilla/5.0 (Windows NT 10.0; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0",
            "Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; .NET4.0C; .NET4.0E; .NET CLR 2.0.50727; .NET CLR 3.0.30729; .NET CLR 3.5.30729; InfoPath.3; rv:11.0) like Gecko",
            "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_8; en-us) AppleWebKit/534.50 (KHTML, like Gecko) Version/5.1 Safari/534.50",
            "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-us) AppleWebKit/534.50 (KHTML, like Gecko) Version/5.1 Safari/534.50",
            "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:2.0.1) Gecko/20100101 Firefox/4.0.1",
            "Mozilla/5.0 (Windows NT 6.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1",
            "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:2.0.1) Gecko/20100101 Firefox/4.0.1",
            "Mozilla/5.0 (Windows NT 6.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1",
            "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_0) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.56 Safari/535.11",
            "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_0) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.56 Safari/535",
        ),
        //爬虫爬取网页所使用的伪IP。随机伪造IP，用于破解防采集
        'client_ip' => array(
            '182.48.116.51:8080',
            '110.177.63.191:9999',
            '221.10.40.238:80',
            '221.10.40.236:83',
            '221.10.40.237:80',
            '221.10.102.199:82',
            '42.121.0.247:9999',
            '42.121.28.111:3128',
            '117.135.194.139:80',
            '119.147.91.21:80',
            '58.247.109.243:80',
            '118.233.36.246:8585'
        ),
        'fields' => array(
//            array(
//                'name' => "title",//标题
//                'selector' => "#activity-name",
//                'required' => true,
//                'selector_type' => 'css',
//            ),
//            array(
//                'name' => "info",//内容
//                'selector' => "#js_content",
//                'required' => true,
//                'selector_type' => 'css',
//            ),
//            array(
//                'name' => "spiderUrl",//爬虫地址
//            ),
        ),
    ),
    "db" => [
        'host'  => '47.105.183.155',
        'port'  => 3306,
        'user'  => 'root',
        'pass'  => 'root123',
        'name'  => 'qywx',
    ]
);

