#!/bin/bash
ps -eaf |grep "RPC core Server" | grep -v "grep"| awk '{print $2}'|xargs kill -9