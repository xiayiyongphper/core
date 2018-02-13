#!/bin/sh
#将核心文件拷贝到其他的系统中，避免各个系统中核心文件不一致。
cd ~/work/svn/swoole

#copy to customer
cp core/trunk/service/Application.php customer/trunk/service/
cp core/trunk/service/Server.php customer/trunk/service/
cp core/trunk/service/Response.php customer/trunk/service/
cp core/trunk/service/Request.php customer/trunk/service/
cp core/trunk/service/ErrorHandler.php customer/trunk/service/

#copy to merchant
cp core/trunk/service/Application.php merchant/trunk/service/
cp core/trunk/service/Server.php merchant/trunk/service/
cp core/trunk/service/Response.php merchant/trunk/service/
cp core/trunk/service/Request.php merchant/trunk/service/
cp core/trunk/service/ErrorHandler.php merchant/trunk/service/

#copy to route
cp core/trunk/service/Application.php route/trunk/service/
cp core/trunk/service/Server.php route/trunk/service/
cp core/trunk/service/Response.php route/trunk/service/
cp core/trunk/service/Request.php route/trunk/service/
cp core/trunk/service/ErrorHandler.php route/trunk/service/
