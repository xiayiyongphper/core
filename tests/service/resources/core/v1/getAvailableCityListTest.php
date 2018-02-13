<?php
namespace tests\service\resources\core\v1;

use framework\message\Message;
use service\message\common\SourceEnum;
use service\message\core\AvailableCityListResponse;
use service\resources\core\v1\getAvailableCityList;
use tests\service\ApplicationTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-1-17
 * Time: 下午2:23
 */
class getAvailableCityListTest extends ApplicationTest
{
    public function getModel()
    {
        return new getAvailableCityList();
    }

    public function testGetFetch()
    {
        $this->assertInstanceOf('service\resources\core\v1\getAvailableCityList', $this->model);
    }

    public function testGetConfigRequest()
    {
        $this->assertTrue(getAvailableCityList::request());
    }

    public function testGetConfigResponseResponse()
    {
        $this->assertInstanceOf('service\message\core\AvailableCityListResponse', getAvailableCityList::response());
    }

    public function testGetHeader()
    {
        $this->assertInstanceOf('service\message\common\Header', $this->header);
    }

    public function testGetRequest()
    {
        $this->assertInstanceOf('framework\Request', $this->request);
    }

    public function testRun()
    {
        $this->request->setRemote(true);
        $request = getAvailableCityList::request();
        $this->header->setRoute('core.getAvailableCityList');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var AvailableCityListResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
        $this->assertInstanceOf('service\message\core\AvailableCityListResponse', $data);
        $this->assertGreaterThan(0, $data->getCityCount(), '不能没有开通城市');
        if ($data->getCityCount() > 0) {
            foreach ($data->getCity() as $city) {
                $this->assertInstanceOf('\service\message\common\City', $city);
                $this->assertNotEmpty($city->getCityName());
                $this->assertNotEmpty($city->getCityCode());
                $this->assertNotEmpty($city->getProvinceName());
                $this->assertNotEmpty($city->getProvinceCode());
            }
        }
    }
}