<?php
namespace tests\service;

use framework\Application;
use framework\components\ProxyAbstract;
use framework\Request;
use framework\resources\ApiAbstract;
use service\message\common\Header;
use service\message\common\SourceEnum;
use service\message\contractor\ContractorAuthenticationRequest;
use service\message\contractor\ContractorResponse;
use service\resources\ResourceAbstract;
use tests\AbstractTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-10-27
 * Time: 下午6:08
 * Email: henryzxj1989@gmail.com
 */
abstract class ApplicationTest extends AbstractTest
{
    /**
     * @var ResourceAbstract
     */
    protected $model;

    /**
     * @var Header
     */
    protected $header;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Application
     */
    protected $application;

    /**
     * 用户ID
     * @var integer
     */
    protected $customerId = 35;

    /**
     * TOKEN
     * @var string
     */
    protected $authToken = 'KBovpuxTtPUbhq28';

    /**
     * 用户ID
     * @var integer
     */
    protected $contractorId = 25;

    /**
     * TOKEN
     * @var string
     */
    protected $contractorAuthToken = 'mAMTADPUFgzbZrkd';

    protected $wholesalerId = 3;

    protected $orderId = 234809;
    protected $receiptConfirmOrderId = 234808;
    /**
     * @var ContractorResponse
     */
    protected $contractor;


    /**
     * @return ApiAbstract
     */
    abstract protected function getModel();

    /**
     * Set up
     */
    public function setUp()
    {
        parent::setUp();
        $this->model = $this->getModel();
        $this->header = new Header();
        $this->request = new Request();
        $this->application = new Application($this->config);
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->model = null;
        $this->header = null;
        $this->request = null;
    }

    public function getContractor()
    {
        if (!$this->contractor) {
            $contractorRequest = new ContractorAuthenticationRequest();
            $contractorRequest->setContractorId($this->contractorId);
            $contractorRequest->setAuthToken($this->contractorAuthToken);
            $header = new Header();
            $header->setRoute('contractor.contractorAuthentication');
            $header->setSource(SourceEnum::CORE);
            $contractorResponse = ProxyAbstract::sendRequest($header, $contractorRequest);
            $this->contractor = new ContractorResponse();
            $this->contractor->parseFromString($contractorResponse->getPackageBody());
        }
        return $this->contractor;
    }
}