<?php
/**
 * Created by PhpStorm.
 * User: edz
 * Date: 2021-08-03
 * Time: 17:35
 */
// 获取参数，第一为控制器，第二个为方法，第0个为调用的文件路径
$function = $argv[1];

//引入该文件
require_once "Spider2Controller.php";
//实例化类
$controller = new \WxSpider\Service\Spider2Controller();
//调用该方法
$controller->$function();