<?php
/**
 * Created by PhpStorm.
 * User: wangxiaoyu
 * Date: 2020-09-07
 * Time: 20:57
 */
namespace WxSpider\Service;
// 严格开发模式
error_reporting(E_ALL);
ini_set("memory_limit", "1024M");
// 永不超时
ini_set('max_execution_time', 0);
set_time_limit(0);
/* Do NOT delete this comment */
/* 不要删除这段注释 */

use phpspider\core\log;
use phpspider\core\phpspider;
use phpspider\core\db;
use phpspider\core\requests;
use phpspider\core\selector;

class Spider2Controller
{
    public function startSpider(){
        //实例化爬虫脚本
        $spider = new phpspider($GLOBALS['config']['SPIDER']);

        /**
         * @auto 方法名
         * @param $phpspider 爬虫对象
         * @return .
         * @author 王晓宇
         * @data 2019/5/15
         * @time 11:00
         */
        $spider->on_start = function ($phpspider) {
            requests::set_header("Referer", "https://mp.weixin.qq.com");
        };

        /**
         * @param $status_code 当前网页的请求返回的HTTP状态码
         * @param $url 当前网页URL
         * @param $content 当前网页内容
         * @param $phpspider 爬虫对象
         * @return $content 返回处理后的网页内容，不处理当前页面请返回false
         *
         */
        $spider->on_status_code = function ($status_code, $url, $content, $phpspider) {
            echo $status_code;
            exit;
            if ($status_code == 200) {
                //判断是列表页
                if (in_array($url, $GLOBALS['url'])) {
                    if (!empty($content)) {
                        $table = "qywx_spider_list";
                        $find_sql = "select * from `" . $table . "` where `link` = " . "'" . $url . "'";
                        $row = db::get_one($find_sql);
                        if(!empty($row)){
                            if (!strpos($content , "<!-- 只要是视频落地页，一定是新版 -->")){
                                $info = selector::select($content , '//*[@id="js_content"]');
                                $title = trim(selector::select($content , '//*[@id="activity-name"]'));
                                $source = trim(selector::select($content , '//*[@id="js_name"]'));
                            }else{
                                $info = selector::select($content , '//*[@id="js_content"]');
                                $title = trim(selector::select($content , '//*[@id="js_base_container"]/h2'));
                                $source = trim(selector::select($content , '//*[@id="profile_share2"]/strong'));
                            }

                            // 在data目录下生成图片
                            $filepath = UPLOADPATH . "/pic/".date("Ymd") . "/";
                            mymkdir($filepath);
                            /*                    $info = preg_replace_callback("/(<[img|IMG].*?src=[\'|\"])(.*?(?:[\.jpg|\.jpeg|\.png|\.gif|\.bmp]))([\'|\"].*?[\/]?>)/", function($matches) use($filepath){*/
//                        if (!empty($matches) && strstr($matches[2] ,"mmbiz.qpic.cn")){
//                            // 以纳秒为单位生成随机数
//                            $ext = get_remote_file_ext($matches[2]);
//                            $filename = date('Ymd') . uniqid() . '.' . $ext;//文件名
//                            $crabImage = crabImage($matches[2] , $filepath , $filename);
//                            $matches[2] = sprintf('"http://wechatspider.com/data/upload/attachment/%s'  , "pic/".date("Ymd") . "/".$filename . '"');
//                        }
//
//                        # 处理src
//                        return sprintf("%s src=" ,rtrim($matches[1] , 'data-src=""')).$matches[2].ltrim($matches[3] , '"');
//                    }, $info);
                            $info = preg_replace_callback('/<img.*?data-src=[\"|\']?(.*?)[\"|\']?\s.*?>/i', function($matches) use($filepath){
                                $newPath = $matches[1];
                                if (!empty($matches) && strstr($matches[1] ,"mmbiz.qpic.cn")){
                                    // 以纳秒为单位生成随机数
                                    $crabImage = crabImage($matches[1] , $filepath);
                                    $newPath = sprintf('"http://wechatspider.com/data/upload/attachment/%s'  , "pic/".date("Ymd") . "/".$crabImage['file_name'] . '"');
                                }
                                $strRe = str_replace(sprintf('data-src="%s"' , $matches[1]) , sprintf('src=%s' , $newPath) , $matches[0]);
                                return $strRe;
                            }, $info);

                            //处理缩略图
                            $crabImageCover = crabImage($row['cover'] , $filepath);
                            $coverPath = sprintf("/data/upload/attachment/%s" , "pic/".date("Ymd") . "/".$crabImageCover['file_name']);

                            //把章节存入html中
                            $file = UPLOADPATH . sprintf("/article%s%s%s", DIRECTORY_SEPARATOR,date("Y-m-d") , DIRECTORY_SEPARATOR);
                            mymkdir($file);
                            $file = fopen($file . md5($title) . ".html", "w");
                            $file_path = "";
                            if (fwrite($file, '<meta charset="UTF-8">' . $info)) {
                                $file_path = sprintf("/data/upload/attachment/article/%s/", date("Y-m-d")) . md5($title) . ".html";
                            }
                            fclose($file);

                            db::update($table, [
                                'status' => 2,
                                'path' => $file_path,
                                "update_time" => date("Y-m-d H:i:s" , time()),
                                'source' => $source,
                                'cover'  => $coverPath,
                            ], "id = " . $row['id']);//更新
                            echo date("Y-m-d H:i:s").$url . "已爬完" . "\r\n";
                        }
                    }
                }
                // 如果状态码为429，说明对方网站设置了不让同一个客户端同时请求太多次
                if ($status_code == '429') {
                    // 将url插入待爬的队列中,等待再次爬取
                    $phpspider->add_url($url);
                    // 当前页先不处理了
                    return false;
                }
            }
            // 不拦截的状态码这里记得要返回，否则后面内容就都空了
            return $content;
        };

        $spider->start();//启动脚本
    }


    /**
     * PHP将网页上的图片攫取到本地存储
     * @param $imgUrl  图片url地址
     * @param string $saveDir 本地存储路径 默认存储在当前路径
     * @param null $fileName 图片存储到本地的文件名
     * @return mix
     */
    private function crabImage($imgUrl, $saveDir = './')
    {
        if (empty($imgUrl)) {
            return false;
        }

        //开始攫取
        $headers = array(
            'User-Agent:Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.47 Safari/536.11Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.47 Safari/536.11',
        );
        $ch = curl_init();
        //设置一个cURL传输选项。
        curl_setopt($ch, CURLOPT_URL, $imgUrl);                    //目标
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        $values = curl_exec($ch);
        if ($values === false) {
            echo 'Curl error: ' . curl_error($ch);
        }
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);//获取头信息中content_type
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);//获取response头size

        curl_close($ch);
        $imgInfo = ($values);
        if (!file_exists($saveDir)) {
            mkdir($saveDir, 0777, true);
        }
        $fileName = uniqid(time(), true) . ".png";
        if (empty($content_type)) {  //生成唯一的文件名
            $arr = explode('/',$content_type);//获取图片后缀名
            $fileName = uniqid(time(), true) .'.'.$arr['1'];
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
     * 微信文章列表 + 内容
     */
    private function getWxArticleList()
    {
        $getSpiderInfo = db::get_one("Select * From `qywx_spider` Where `status`='0' and token != '' order by id desc");
        if (empty($getSpiderInfo) || empty($getSpiderInfo['token'])) {
            return false;
        }
        db::query("Update `qywx_spider` Set `status`=1 Where `id`=".$getSpiderInfo['id']);//更新状态为爬取中
        $input['proxy'] = $GLOBALS['config']['SPIDER']['client_ip'][$getSpiderInfo['proxy_key']];
        $input['useragent'] = $GLOBALS['config']['SPIDER']['user_agent'][$getSpiderInfo['ua_key']];

        $token = $getSpiderInfo['token'];
        $fakeid = [
            "MjM5MzA4NTc2MA==",
            "MzA4NzI5MjI4NQ==",
            "MzA3MDYxMDAyMA==",
        ];
        $time = date("Y-m-d H:i:s" , time());
        $endTime = 1590940800;
        $links = [];
        foreach ($fakeid as $fake) {
            $begin = 0;
            $count = 5;
            while (true) {
                $begin += $count;
                //异步任务
                $url = sprintf("https://mp.weixin.qq.com/cgi-bin/appmsg?action=list_ex&type=9&query=&lang=zh_CN&f=json&ajax=1&token=%s&begin=%s&count=%s&fakeid=%s", $token, $begin, $count, $fake);
                $getArticleList = curl($url, $input);
                if ($getArticleList['base_resp']['ret'] != 0 || empty($getArticleList['app_msg_list'])) {
                    $msgError = "调用微信列表异常".json_encode($getArticleList);
                    log::error($msgError);
                    echo $msgError;
                    break;
                }
                foreach ($getArticleList['app_msg_list'] as $key => $value) {
                    if ($value['create_time'] < $endTime) {
                        break 2;
                    }
                    $getList = db::get_one("Select * From `qywx_spider_list` Where `aid`= '" . $value['aid'] . "'");
                    if (empty($getList)) {
                        $articelListData = [
                            "aid" => $value['aid'],
                            "appmsgid" => $value['appmsgid'],
                            "cover" => $value['cover'],
                            "wx_create_time" => date('Y-m-d H:i:s' , $value['create_time']),
                            "digest" => $value['digest'],
                            "link" => $value['link'],
                            "title" => $value['title'],
                            "wx_update_time" => date('Y-m-d H:i:s' , $value['update_time']),
                            "create_time" => $time,
                        ];
                        array_push($links , $value['link']);
                        db::insert("qywx_spider_list", $articelListData);
                    }
                }
                $msgInfo = "列表爬取成功   请求:" .json_encode(['url' => $url , "input" => $input]) . "\n返回参数：".json_encode($getArticleList);
                log::info($msgInfo);
                echo $msgInfo;

                sleep(rand(20 , 100));
            }
        }

        $GLOBALS['url'] = $links;
        $GLOBALS['config']['SPIDER']['scan_urls'] = $links;
        $GLOBALS['config']['SPIDER']['list_url_regexes'] = $links;

        log::info("列表数据已爬完");
        return true;
    }

    private function _getCookieFile()
    {
        $path = COOKIEPATH . "spider/";
        mymkdir($path);
        return $path . "cookie_{$GLOBALS['_key']}.text";
    }

    /**
     * @param $url
     * @param null $_input
     * @param string $data_type
     * @return mixed
     * $_input= ["post"=>[],"refer"=>"",cookiefile='']
     */
    private function curl($url, $_input = null, $data_type = 'json')
    {
        $ch = curl_init();
//        curl_setopt( $ch, CURLOPT_HTTPHEADER, $this->_headers); //设置HTTP头字段的数组
        $useragent = $_input['useragent'];
        $proxy = $_input['proxy'];
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
        curl_setopt($ch, CURLOPT_POST, isset($_input['post']));
        if (isset($_input['post'])) curl_setopt($ch, CURLOPT_POSTFIELDS, $_input['post']);
        if (isset($_input['refer'])) curl_setopt($ch, CURLOPT_REFERER, $_input['refer']);
        curl_setopt($ch, CURLOPT_USERAGENT, !empty($useragent) ? $useragent : 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.2) Gecko/20100101 Firefox/10.0.2');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (isset($_input['timeout']) ? $_input['timeout'] : 5));
        curl_setopt($ch, CURLOPT_COOKIEJAR, (isset($_input['cookiefile']) ? $_input['cookiefile'] : _getCookieFile()));
        curl_setopt($ch, CURLOPT_COOKIEFILE, (isset($_input['cookiefile']) ? $_input['cookiefile'] : _getCookieFile()));
        $result = curl_exec($ch);
        curl_close($ch);
        if ($data_type == 'json') {
            $result = json_decode($result, true);
        }
        return $result;
    }


}
