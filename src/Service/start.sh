#!/bin/bash
ps -ef|grep SpiderController.php |grep -v grep  # 判断脚本是否启动
if [ $? -ne 0 ]		#如果没有
then
echo "start process....."
nohup php -f /Users/wangxiaoyu/www/dellsec/application/Spider/Controller/SpiderController.php start >> /Users/wangxiaoyu/www/dellsec/data/runtime/Logs/wechatSpider.log 2>&1 &      # 执行启动脚本命令,nohup输出是追加到日志文件,这样不会覆盖掉之前的日志文件 换地址----
else
echo "runing....."
fi

