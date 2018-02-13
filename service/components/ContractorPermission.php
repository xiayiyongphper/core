<?php
/**
 * Created by PhpStorm.
 * User: Jason Y. wang
 * Date: 16-12-28
 * Time: 下午5:43
 */

namespace service\components;


class ContractorPermission
{
    //订单跟踪列表
    const ORDER_TRACKING_COLLECTION = 'order/order-tracking-collection';
    //订单跟踪列表
    const ORDER_STATUS_COLLECTION = 'order/order-status-collection';


    public static function orderTrackingCollectionPermission($role_permission){
        if(is_array($role_permission)){
            if(in_array('*',$role_permission) || in_array(self::ORDER_TRACKING_COLLECTION,$role_permission)){
                return true;
            }
        }
        return false;
    }

    public static function orderStatusCollectionPermission($role_permission){
        if(is_array($role_permission)){
            if(in_array('*',$role_permission) || in_array(self::ORDER_STATUS_COLLECTION,$role_permission)){
                return true;
            }
        }
        return false;
    }



}