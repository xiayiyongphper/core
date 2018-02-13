<?php
namespace service\models;

use service\components\Tools;

class UniqueId
{
    //开始时间,固定一个小于当前时间的毫秒数即可
    const twepoch =  1483228800000;//2017-01-01 00:00:00
    //const twepoch =  1483228800;

    //机器标识占的位数
    const workerIdBits = 5;
    const machineIdBits = 5;

    //毫秒内自增数点的位数
    const sequenceBits = 12;

    protected $workId = 0;
    protected $machineId = 0;

    //要用静态变量
    static $lastTimestamp = -1;
    static $sequence = 0;


    function __construct($machineId,$workId){
        //机器ID、workerId范围判断
        $maxMachineId = -1 ^ (-1 << self::machineIdBits);
        if($machineId > $maxMachineId || $machineId< 0){
            throw new \Exception("workerId can't be greater than ".$maxMachineId." or less than 0");
        }
        $maxWorkerId = -1 ^ (-1 << self::workerIdBits);
        if($workId > $maxWorkerId || $workId< 0){
            throw new \Exception("workerId can't be greater than ".$maxWorkerId." or less than 0");
        }
        //赋值
        $this->machineId = $machineId;
        $this->workId = $workId;
    }

    //生成一个ID
    public function nextId(){
        $timestamp = $this->timeGen();
        $lastTimestamp = self::$lastTimestamp;
        //判断时钟是否正常
        if ($timestamp < $lastTimestamp) {
            throw new \Exception("Clock moved backwards.  Refusing to generate id for %d milliseconds", ($lastTimestamp - $timestamp));
        }
        //生成唯一序列
        if ($lastTimestamp == $timestamp) {
            $sequenceMask = -1 ^ (-1 << self::sequenceBits);
            self::$sequence = (self::$sequence + 1) & $sequenceMask;
            if (self::$sequence == 0) {
                $timestamp = $this->tilNextMillis($lastTimestamp);
            }
        } else {
            self::$sequence = 0;
        }
        self::$lastTimestamp = $timestamp;

        //时间毫秒/数据中心ID/机器ID/workerId,要左移的位数
        $timestampLeftShift = self::sequenceBits + self::workerIdBits + self::machineIdBits;
        $machineIdShift     = self::sequenceBits + self::workerIdBits;
        $workerIdShift      = self::sequenceBits;
        //Tools::log(getmypid().'-'.$this->workId.'-'.self::$sequence,'pid.log');
        //组合3段数据返回: 时间戳.工作机器.序列
        $nextId = (($timestamp - self::twepoch) << $timestampLeftShift) | ($this->machineId << $machineIdShift) | ($this->workId << $workerIdShift) | self::$sequence;
        return $nextId;
    }

    //取当前时间毫秒
    protected function timeGen(){
        $timestramp = (float)sprintf("%.0f", microtime(true) * 1000);
        return  $timestramp;
    }

    //取下一毫秒
    protected function tilNextMillis($lastTimestamp) {
        $timestamp = $this->timeGen();
        while ($timestamp <= $lastTimestamp) {
            $timestamp = $this->timeGen();
        }
        return $timestamp;
    }
}