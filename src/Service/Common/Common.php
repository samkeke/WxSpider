<?php

namespace WxSpider\Service\Common;

class Common
{
    public static function getConfig(){
        return [
            'name' => '爬虫',
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
//            'scan_urls' => $GLOBALS['url'],
            //列表页实例
//            'list_url_regexes' => $GLOBALS['url'],
            //内容页实例 要爬取的页面
            //  \d+  指的是变量
//            'content_url_regexes' => $GLOBALS['url'],

            'user_agent' => array(
                //PC端的UserAgent
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
            ),
        ];
    }

    /**
     * @auto 创建文件目录
     * @param
     * @return .
     * @author 王晓宇
     * @data 2019/4/29
     * @time 17:12
     */
    public static function mymkdir($path,$mode=0755){
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

    /**
     * PHP将网页上的图片攫取到本地存储
     * @param $imgUrl  图片url地址
     * @param string $saveDir 本地存储路径 默认存储在当前路径
     * @param null $fileName 图片存储到本地的文件名
     * @return mix
     */
    public static function crabImage($imgUrl, $saveDir='./', $fileName=null)
    {
        if (empty($imgUrl)) {
            return false;
        }

        //获取图片信息大小
        $imgSize = getImageSize($imgUrl);
        if (!in_array($imgSize['mime'], array('image/jpg', 'image/gif', 'image/png', 'image/jpeg'), true)) {
            return false;
        }

        //获取后缀名
        $_mime = explode('/', $imgSize['mime']);
        $_ext = '.' . end($_mime);

        if (empty($fileName)) {  //生成唯一的文件名
            $fileName = uniqid(time(), true) . $_ext;
        }

        //开始攫取
        ob_start();
        readfile($imgUrl);
        $imgInfo = ob_get_contents();
        ob_end_clean();

        if (!file_exists($saveDir)) {
            mkdir($saveDir, 0777, true);
        }
        $fp = fopen($saveDir . $fileName, 'a');
        $imgLen = strlen($imgInfo);    //计算图片源码大小
        $_inx = 1024;   //每次写入1k
        $_time = ceil($imgLen / $_inx);
        for ($i = 0; $i < $_time; $i++) {
            fwrite($fp, substr($imgInfo, $i * $_inx, $_inx));
        }
        fclose($fp);
        return array('file_name' => $fileName, 'save_path' => $saveDir . $fileName);
    }

    /**
     * @auto 远程请求图片地址
     * @param
     * @return .
     * @author 王晓宇
     * @data 2019/5/16
     * @time 16:58
     */
    public static function curlGet($url)
    {
        $headers = array(
            'User-Agent:Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.47 Safari/536.11Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.47 Safari/536.11',
        );
        $ch = curl_init();
        //设置一个cURL传输选项。
        curl_setopt($ch, CURLOPT_URL, $url);                    //目标
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        $values = curl_exec($ch);
        if ($values === false) {
            echo 'Curl error: ' . curl_error($ch);
        }
        curl_close($ch);
        return ($values);
    }

    /**
     * 特殊字符转换
     * @author bignerd
     * @since  2016-08-16T17:30:52+0800
     * @param  $string
     * @return $string
     */
    public static function htmlTransform($string)
    {
        $string = str_replace('&quot;', '"', $string);
        $string = str_replace('&amp;', '&', $string);
        $string = str_replace('amp;', '', $string);
        $string = str_replace('&lt;', '<', $string);
        $string = str_replace('&gt;', '>', $string);
        $string = str_replace('&nbsp;', ' ', $string);
        $string = str_replace("\\", '', $string);
        return $string;
    }

    public static function getRemoteFileExt($url)
    {
        $ext = 'png';
        if($url){
            $queryParts = explode("&" , explode("?" , $url)[1]);

            $params = array();
            foreach ($queryParts as $param) {
                $item = explode('=', $param);
                $params[$item[0]] = $item[1];
            }
            $ext = isset($params['wx_fmt']) ? $params['wx_fmt'] : "png";
        }
        return $ext;
    }
}

