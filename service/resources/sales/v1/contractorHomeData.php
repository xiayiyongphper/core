<?php
namespace service\resources\sales\v1;

use common\models\SalesFlatOrder;
use service\components\Tools;
use service\message\common\ContractorStatics;
use service\message\contractor\ContractorHomeDataRequest;
use service\resources\ResourceAbstract;
use yii\helpers\ArrayHelper;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/8/23
 * Time: 15:09
 */
class contractorHomeData extends ResourceAbstract
{

    public $customer_ids;
    public $contractor_id;
    public $role;
    public $city_list;
    public $is_admin;
    public $dayDate;
    public $monthDate;
    public $dailyGMV;
    public $dailyOrderCount;
    public $dailyCustomerCount;
    public $monthlyGMV;
    public $monthlyCustomerCount;
    public $todayNewOrderCustomer_count;

    public function run($data)
    {
        //请求参数
        /** @var ContractorHomeDataRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $this->customer_ids = $request->getStoreIds();
        //普通业务员按业务员ID进行查询
        $this->contractor_id = $request->getContractorId();
        //非普通业务员按所属城市列表查询
        $this->role = $request->getRole();
        $this->city_list = $request->getCityList();
        //Tools::log($this->city_list,'wangyang.log');
        //Tools::log($this->role,'wangyang.log');
        //时间范围
        $this->dayDate = date('Y-m-d 16:00:00', strtotime("-1 day"));
        $this->monthDate = date('Y-m-01 00:00:00');
        //计算数据
        self::dailyOrderCount();
        self::dailyCustomerCount();
        self::todayNewOrderCustomer_count();
        self::monthlyData();
        //返回数据
        $responseData = [
            'sales_count' => [
                'title' => '今日业绩',
                'sales_amount' => number_format($this->dailyGMV, 2, null, ''),
                'update_time' => '实时数据，刷新时间' . date('Y-m-d H:i:s', strtotime('+8 hours')),
            ],
            'datas' => [
                [
                    'name' => '统计',
                    'data' => [
                        [
                            'key' => '当日订单数',
                            'value' => $this->dailyOrderCount,
                        ],
                        [
                            'key' => '当日下单用户数',
                            'value' => $this->dailyCustomerCount,
                        ],
                        [
                            'key' => '当日新增下单用户数',
                            'value' => $this->todayNewOrderCustomer_count,
                        ],
                        [
                            'key' => '当月GMV',
                            'value' => $this->monthlyGMV ? $this->monthlyGMV : 0,
                        ],
                        [
                            'key' => '当月下单用户数',
                            'value' => $this->monthlyCustomerCount,
                        ],

                    ]
                ]
            ]
        ];
        //接口验证用户
        $response = self::response();
        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    /**
     * 今日业绩   当日订单数
     */
    public function dailyOrderCount()
    {
        $dailyOrders = SalesFlatOrder::find()->where(['>', 'created_at', $this->dayDate]);
        if ($this->role == self::COMMON_CONTRACTOR) {
            $dailyOrders = $dailyOrders->andWhere(['contractor_id' => $this->contractor_id]);
        } else {
            $dailyOrders = $dailyOrders->andWhere(['city' => $this->city_list]);
        }
//        Tools::log($dailyOrders->createCommand()->getRawSql(),'wangyang.log');
        $this->dailyGMV = $dailyOrders->sum('grand_total');
        $this->dailyOrderCount = $dailyOrders->count();
    }

    /**
     * 当日下单用户数
     */
    public function dailyCustomerCount()
    {
        if ($this->role != self::COMMON_CONTRACTOR) {
            $dailyCustomerCount = SalesFlatOrder::find()->where(['city' => $this->city_list]);
        } else {
            $dailyCustomerCount = SalesFlatOrder::find()->where(['contractor_id' => $this->contractor_id]);
        }
        $this->dailyCustomerCount = $dailyCustomerCount->andWhere(['>', 'created_at', $this->dayDate])->groupBy('customer_id')->count();
    }

    /**
     * 当日新增下单用户数
     */
    public function todayNewOrderCustomer_count()
    {
        $todayOrderCustomer = SalesFlatOrder::find()->select('customer_id')->where(['>', 'created_at', $this->dayDate]);
        $beforeTodayOrderCustomer = SalesFlatOrder::find()->select('customer_id')->where(['<', 'created_at', $this->dayDate]);
        if ($this->role != self::COMMON_CONTRACTOR) {
            $todayOrderCustomer = $todayOrderCustomer->andWhere(['city' => $this->city_list]);
            $beforeTodayOrderCustomer = $beforeTodayOrderCustomer->andWhere(['city' => $this->city_list]);
        } else {
            $todayOrderCustomer = $todayOrderCustomer->andWhere(['contractor_id' => $this->contractor_id]);
            $beforeTodayOrderCustomer = $beforeTodayOrderCustomer->andWhere(['contractor_id' => $this->contractor_id]);
        }
        //今天下单用户
        $todayOrderCustomer_ids = $todayOrderCustomer->groupBy('customer_id')->column();
        //今天之前下单用户
        $beforeTodayOrderCustomer_ids = $beforeTodayOrderCustomer->andWhere(['not in', 'state', ['canceled', 'closed']])
            ->groupBy('customer_id')->column();
        $this->todayNewOrderCustomer_count = count(array_diff($todayOrderCustomer_ids, $beforeTodayOrderCustomer_ids));
    }

    /**
     * 当月GMV  当月下单用户数
     */
    public function monthlyData()
    {
        $monthlyOrders = SalesFlatOrder::find()->where(['>', 'created_at', $this->monthDate]);
        if ($this->role != self::COMMON_CONTRACTOR) {
            $monthlyOrders = $monthlyOrders->andWhere(['city' => $this->city_list]);
        } else {
            $monthlyOrders = $monthlyOrders->andWhere(['contractor_id' => $this->contractor_id]);
        }
        $this->monthlyGMV = $monthlyOrders->sum('grand_total');
        $this->monthlyCustomerCount = $monthlyOrders->groupBy('customer_id')->count();
    }


    public static function request()
    {
        return new ContractorHomeDataRequest();
    }

    public static function response()
    {
        return new ContractorStatics();
    }
}
