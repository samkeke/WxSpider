<?php
/**
 * Created by PhpStorm.
 * User: wangxiaoyu
 * Date: 2020-09-05
 * Time: 22:31
 */

namespace Spider\Controller;

use function Sodium\add;
use Think\Controller;
use Think\Log;

class WeChatLoginController extends Controller
{

    /**
     * @auto 创建文件目录
     * @param
     * @return .
     * @author 王晓宇
     * @data 2019/4/29
     * @time 17:12
     */
    function mymkdir($path,$mode=0755){
        if (is_dir($path)){
            return true;
        }else{ //不存在则创建目录
            $re=mkdir($path,$mode,true);
            //第三个参数为true即可以创建多极目录
            if ($re){
                return true;
            }else{
                return false;
            }
        }
    }
    //--------------------------------------------------------LOGIN START
    public $_apis = [
        "host" => "https://mp.weixin.qq.com/",
        "login" => "https://mp.weixin.qq.com/cgi-bin/bizlogin?action=startlogin",
        "qrcode" => "https://mp.weixin.qq.com/cgi-bin/loginqrcode?action=getqrcode&parm=4300",
        "loginqrcode" => "https://mp.weixin.qq.com/cgi-bin/loginqrcode?action=ask&token=&lang=zh_CN&f=json&ajax=1",
        "loginask" => "https://mp.weixin.qq.com/cgi-bin/loginqrcode?action=ask&token=&lang=zh_CN&f=json&ajax=1&random=",
        "loginauth" => "https://mp.weixin.qq.com/cgi-bin/loginauth?action=ask&token=&lang=zh_CN&f=json&ajax=1",
        "bizlogin" => "https://mp.weixin.qq.com/cgi-bin/bizlogin?action=login&lang=zh_CN",
        "articlelist" => "https://mp.weixin.qq.com/cgi-bin/appmsg?action=list_ex&type=9&query=&lang=zh_CN&f=json&ajax=1"
    ];
    private static $_redirect_url = "";
    private $_key = "tmall";
    public $spiderId = 0;

    public $proxy = [];

    public $useragents = [];

    private function _getCookieFile()
    {
        $path = C("UPLOADPATH") . "spider/";
        $this->mymkdir($path);
        return $path . "cookie_{$this->_key}.text";
    }

    private function _getSavePath()
    {
        $path = C("UPLOADPATH") . "spider/";
        $this->mymkdir($path);
        return $path . $this->_qrcodeName();
    }

    private function _qrcodeName()
    {
        return "qrcode_{$this->_key}" . ".png";
    }

    private function _log($msg)
    {
        \phpspider\core\log::info("[微信调度:" . date("Y-m-d H:i:s") . "] ======: {$msg}");
    }

    public function getToken()
    {
//        $isLogin = session("token_".$this->_key);
//        if(!empty($isLogin)){
//            return $isLogin;
//        }
        $getToken = M("Spider")->where(['id' => $this->spiderId])->find();
        if (!empty($getToken)) {
            return $getToken['token'];
        }
        return "";
    }

    public function setToken($token)
    {
//        session("token_".$this->_key , $token);
        M("Spider")->where(['id' => $this->spiderId])->save([
            "token" => $token,
            "cookie_file" => $this->_getCookieFile(),
            "update_time" => date("Y-m-d H:i:s", time())
        ]);
    }

    public function setProxy(){
        $proxyKey = rand(0, count($this->proxy)-1);
        M("Spider")->where(['id' => $this->spiderId])->save([
            "proxy" => $this->proxy[$proxyKey],
            "update_time" => date("Y-m-d H:i:s", time())
        ]);
    }

    public function getProxy(){
        $proxyKey = M("Spider")->field("proxy_key")->where(['id' => $this->spiderId])->find();
        if (!empty($proxyKey)) {
            return $this->proxy[$proxyKey['proxy_key']];
        }
        return "";
    }

    public function setUseragents(){
        $uaKey = rand(0, count($this->useragents)-1);
        M("Spider")->where(['id' => $this->spiderId])->save([
            "ua_key" => $uaKey,
            "update_time" => date("Y-m-d H:i:s", time())
        ]);
    }

    public function getUseragents(){
        $uaKey = M("Spider")->field("ua_key")->where(['id' => $this->spiderId])->find();
        if (!empty($uaKey)) {
            return $this->useragents[$uaKey['ua_key']];
        }
        return "";
    }

    public function init($options)
    {
        if (!isset($options["key"])) {
            return false;
        }
        $this->_key = $options["key"];
        if ($this->getToken()) {
            echo("HAS Token !");
            return true;
        } else {
            $time = date("Y-m-d H:i:s", time());
            $config = C("SPIDER");
            $this->useragents = $config['user_agent'];
            $this->proxy = $config['client_ip'];
            //初始化ua和ip
            $uaKey = rand(0, count($this->useragents)-1);
            $proxyKey = rand(0, count($this->proxy)-1);
            $addSpider = M("Spider")->add([
                "create_time" => $time,
                "update_time" => $time,
                "proxy_key" => $proxyKey,
                "ua_key" => $uaKey,
            ]);
            if ($addSpider) {
                unlink($this->_getCookieFile());
                $this->setProxy();
                $this->setUseragents();
                $this->spiderId = $addSpider;
                //先要获取首页!!!
                $this->curl($this->_apis['host'], "", "text");
                $this->_log("start login!!");
                return $this->start_login($options);
            }
            return false;
        }
    }

    private function start_login($options)
    {
        $_res = $this->_login($options["account"], $options["password"]);
        if ($_res["base_resp"]['ret'] != 0) {
            $this->_log($_res);
            return false;
        }
        //保存二维码
        $this->_saveQRcode();
        $_ask_api = $this->_apis["loginask"];

        $_input["refer"] = self::$_redirect_url;
        $_index = 1;
        while (true) {
            if ($_index > 30) {
                $this->_log("亲，超时了");
                break;
            }
            $_res = $this->curl($_ask_api . $this->getWxRandomNum(), $_input);
            $_status = $_res["status"];
            if ($_status == 1) {
                if ($_res["user_category"] == 1) {
                    $_ask_api = $this->_apis["loginauth"];
                } else {
                    $this->_log("Login success");
                    break;
                }
            } else if ($_status == 4) {
                $this->_log("已经扫码");
            } else if ($_status == 2) {
                $this->_log("管理员拒绝");
                break;
            } else if ($_status == 3) {
                $this->_log("登录超时");
                break;
            } else {
                if ($_ask_api == $this->_apis["loginask"]) {
                    $this->_log("请打开二维码，用微信扫码");
                } else {
                    $this->_log("等待确认");
                }
            }
            sleep(2);
            $_index++;
        }

        $this->_log("开始验证");
        $_input["post"] = ["lang" => "zh_CN", "f" => "json", "ajax" => 1, "random" => $this->getWxRandomNum(), "token" => ""];
        $_input["refer"] = self::$_redirect_url;
        $_res = $this->curl($this->_apis["bizlogin"], $_input);
        $this->_log(print_r($_res, true));
        if ($_res["base_resp"]["ret"] != 0) {
            $this->_log("error = " . $_res["base_resp"]["err_msg"]);
            return false;
        }
        $redirect_url = $_res["redirect_url"];//跳转路径
        if (preg_match('/token=([\d]+)/i', $redirect_url, $match)) {//获取cookie
            $this->setToken($match[1]);
            $this->_log("验证成功,token: " . $this->getToken());
            return true;
        }
        $this->_log("token验证失败");
        return false;
    }

    //下载二维码
    private function _saveQRcode()
    {
        $_input["refer"] = self::$_redirect_url;
        $_res    = $this->curl($this->_apis["qrcode"],$_input,"text");
        $path = $this->_getSavePath();
        $fp     = fopen($path, "w+") or die("open fails");
        fwrite($fp,$_res) or die("fwrite fails");
        fclose($fp);
        if($fp){
            M("Spider")->where(['id' => $this->spiderId])->save([
                "qrimg" => $path
            ]);
            $this->_log("下载二维码成功");
        }else{
            $this->_log("下载二维码失败");
        }

    }

    private function _login($_username, $_password)
    {
        $_input["post"] = array(
            'username' => $_username,
            'pwd' => md5($_password),
            'f' => 'json',
            'imgcode' => ""
        );
        $_input["refer"] = "https://mp.weixin.qq.com";
        $_res = $this->curl($this->_apis["login"], $_input);
        if ($_res["base_resp"]["ret"] !== 0) {
            $this->_log("登陆异常:".$_res["base_resp"]["err_msg"]);
            return $this->error($_res["base_resp"]["err_msg"]);
        }
        self::$_redirect_url = "https://mp.weixin.qq.com" . $_res["redirect_url"];//跳转路径
        return $_res;
    }

    function getWxRandomNum()
    {
        return "0." . mt_rand(1000000000000000, 9999999999999999);
    }

    function getUaIp(){
        $getSpiderInfo = M("Spider")->where(['status' => 0, 'token' => ['neq', 'not null']])->order('id desc')->find();

    }

    /**
     * @param $url
     * @param null $_input
     * @param string $data_type
     * @return mixed
     * $_input= ["post"=>[],"refer"=>"",cookiefile='']
     */
    function curl($url, $_input = null, $data_type = 'json')
    {
        $ch = curl_init();
//        curl_setopt( $ch, CURLOPT_HTTPHEADER, $this->_headers); //设置HTTP头字段的数组
        $useragent = $this->getUseragents();
        $proxy = $this->getProxy();
        if(!empty($proxy)){
            $header = array(
                'CLIENT-IP:'.$proxy,
                'X-FORWARDED-FOR:'.$proxy,
            );
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $header); //设置HTTP头字段的数组
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_POST, isset($_input['post']));
        if (isset($_input['post'])) curl_setopt($ch, CURLOPT_POSTFIELDS, $_input['post']);
        if (isset($_input['refer'])) curl_setopt($ch, CURLOPT_REFERER, $_input['refer']);
        curl_setopt($ch, CURLOPT_USERAGENT, !empty($useragent) ? $useragent : 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.2) Gecko/20100101 Firefox/10.0.2');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (isset($_input['timeout']) ? $_input['timeout'] : 30));
        curl_setopt($ch, CURLOPT_COOKIEJAR, (isset($_input['cookiefile']) ? $_input['cookiefile'] : $this->_getCookieFile()));
        curl_setopt($ch, CURLOPT_COOKIEFILE, (isset($_input['cookiefile']) ? $_input['cookiefile'] : $this->_getCookieFile()));
        $result = curl_exec($ch);
        curl_close($ch);
        if ($data_type == 'json') {
            $result = json_decode($result, true);
        }
        return $result;
    }
    //--------------------------------------------------------LOGIN END
}