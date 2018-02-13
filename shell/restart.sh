#!/bin/bash
cur_dir=$(cd "$(dirname "$0")"; pwd)
ps -eaf |grep "RPC core Server" | grep -v "grep"| awk '{print $2}'|xargs kill -9
ps -eaf |grep "swoole_core" | grep -v "grep"| awk '{print $2}'|xargs kill -9
cd $cur_dir
cd ..
php swoole_core.php
