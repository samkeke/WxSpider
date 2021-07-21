<?php
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

/**
 * PHP将网页上的图片攫取到本地存储
 * @param $imgUrl  图片url地址
 * @param string $saveDir 本地存储路径 默认存储在当前路径
 * @param null $fileName 图片存储到本地的文件名
 * @return mix
 */
function crabImage($imgUrl, $saveDir='./', $fileName=null)
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
