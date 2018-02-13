<?php
namespace service\models\client;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/8
 * Time: 14:33
 */
class ClientAbstract
{
    /**
     * @var \swoole_client
     */
    protected $_client;

    public function __construct()
    {
        $this->_client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
        $clientConfig = \Yii::$app->params['soa_client_config'];
        $this->_client->set($clientConfig);
        //print_r($clientConfig);
        $this->_client->on('Connect', array($this, 'onConnect'));
        $this->_client->on('Receive', array($this, 'onReceive'));
        $this->_client->on('Close', array($this, 'onClose'));
        $this->_client->on('Error', array($this, 'onError'));
    }

    public function connect($host, $port, $timeout = 0.1)
    {
        $fp = $this->_client->connect($host, $port, $timeout);
        if (!$fp) {
            echo "Error:{$fp->errMsg} {$fp->errCode}" . PHP_EOL;
            return;
        }
    }

    public function onConnect($client)
    {
        echo "client connected" . PHP_EOL;
    }

    public function onReceive($client, $data)
    {
        echo $data . PHP_EOL;
    }

    public function onClose($client)
    {
        echo "client close connection" . PHP_EOL;
    }

    public function onError()
    {

    }

    public function send($data)
    {
        $this->_client->send($data);
    }

    public function pack(){

    }
}