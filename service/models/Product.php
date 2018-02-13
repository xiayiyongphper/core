<?php
namespace service\models;

use common\redis\Keys;
use framework\components\Date;
use service\components\Tools;
use service\message\common\Tag;
use service\message\customer\CustomerResponse;
use service\resources\Exception;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/26
 * Time: 12:48
 */
class Product
{
    //状态:1：待审核，2：审核通过，3：审核不通过，4：系统下架
    const STATE_PENDING = 1;
    const STATE_APPROVED = 2;
    const STATE_DISAPPROVED = 3;
    const STATE_DISABLED = 4;
    //状态:1：上架，2：下架
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 2;

    const PLACEHOLDER_PRODUCT_IMAGE_180X180 = 'http://assets.lelai.com/images/catalog/product/default/v2/img_placeholder_180x180@2x.png';
    const PLACEHOLDER_PRODUCT_IMAGE_232X232 = 'http://assets.lelai.com/images/catalog/product/default/v2/img_placeholder_232x232@2x.jpg';
    const PLACEHOLDER_PRODUCT_IMAGE_388X388 = 'http://assets.lelai.com/images/catalog/product/default/v2/img_placeholder_388x388@2x.jpg';
    const PLACEHOLDER_PRODUCT_IMAGE_600X600 = 'http://assets.lelai.com/images/catalog/product/default/v2/img_placeholder_600x600@2x.jpg';
    const PLACEHOLDER_PRODUCT_IMAGE_640X300 = 'http://assets.lelai.com/images/catalog/product/default/v2/img_placeholder_640x300@2x.jpg';
    const GALLERY_IMAGE_SIZE_180X180 = '180x180';
    const GALLERY_IMAGE_SIZE_232X232 = '232x232';
    const GALLERY_IMAGE_SIZE_388X388 = '388x388';
    const GALLERY_IMAGE_SIZE_640X300 = '640x300';
    const GALLERY_IMAGE_SIZE_600X600 = '600x600';
    const GALLERY_IMAGE_URL_SOURCE_NAME = 'source';
    /**
     * @var \service\message\common\Product
     */
    protected $_product;

    public function __construct($product)
    {
        $this->_product = $product;
        return $this;
    }

    /**
     * @return bool
     */
    public function isOnSale()
    {
        if ($this->_product->getState() == self::STATE_APPROVED && $this->_product->getStatus() == self::STATUS_ENABLED) {
            return true;
        }
        return false;
    }

    /**
     * @param CustomerResponse $customer
     * @return int
     */
    public function getDailyPurchaseNum($customer)
    {
        $redis = Tools::getRedis();
        $key = Keys::getDailyPurchaseHistory($customer->getCustomerId(), $customer->getCity());
        $purchasedQty = $redis->hGet($key, $this->getProductId());
        return (int)$purchasedQty;
    }

    /**
     * @param $qty
     * @param CustomerResponse $customer
     * @param $throwException
     * @return bool
     */
    public function checkRestrictDaily($qty, $customer, $throwException = false)
    {
        $purchasedQty = $this->getDailyPurchaseNum($customer);
        $restrictDaily = $this->getRestrictDaily();
        if ($restrictDaily == 0) {
            return true;
        }
        if ($restrictDaily > 0 && $restrictDaily > $purchasedQty && ($restrictDaily - $purchasedQty - $qty) >= 0) {
            return true;
        }

        if ($throwException) {
            $restQty = $restrictDaily - $purchasedQty;
            $restQty = $restQty > 0 ? $restQty : 0;
            Exception::catalogProductOutOfRestrictDaily($this->getName(), $restrictDaily, $restQty);
        }
        return false;
    }

    /**
     * @param $qty
     * @return bool
     */
    public function checkQty($qty)
    {
        if ($this->_product->getQty() >= $qty) {
            return true;
        }
        return false;
    }

    /**
     * @param bool|false $originalName
     * @return string
     */
    public function getName()
    {
        return $this->_product->getName();
    }


    public function getIsCalculateLelaiRebates()
    {
        return $this->_product->getIsCalculateLelaiRebates();
    }

    public function getCommission()
    {
        return $this->_product->getCommission();
    }

    public function getBrand()
    {
        return $this->_product->getBrand();
    }

    /**
     * @return int
     */
    public function getQty()
    {
        return $this->_product->getQty();
    }

    public function getSpecificationText()
    {
        if ($this->_product->getSpecification() && $this->_product->getPackageNum() && $this->_product->getPackageSpe() && $this->_product->getPackage()) {
            $specification = ' ' . $this->_product->getSpecification() . '×' . $this->_product->getPackageNum() . $this->_product->getPackageSpe() . '/' . $this->_product->getPackage();
        } else if ($this->_product->getSpecification() && $this->_product->getPackageSpe()) {
            $specification = ' ' . $this->_product->getSpecification() . '/' . $this->_product->getPackageSpe();
        } else {
            $specification = '';
        }
        return $specification;
    }

    /**
     * @return string
     */
    public function getProductId()
    {
        return $this->_product->getProductId();
    }

    /**
     * @return mixed|string
     */
    public function getImage180X180()
    {
        if (!$this->_product->getGallery()) {
            return self::PLACEHOLDER_PRODUCT_IMAGE_180X180;
        }
        $images = $this->getGalleryImages(self::GALLERY_IMAGE_SIZE_180X180);
        return trim(current($images));
    }

    public function getImage232X232()
    {
        if (!$this->_product->getGallery()) {
            return self::PLACEHOLDER_PRODUCT_IMAGE_232X232;
        }
        $images = $this->getGalleryImages(self::GALLERY_IMAGE_SIZE_232X232);
        return trim(current($images));
    }

    public function getImage388X388()
    {
        if (!$this->_product->getGallery()) {
            return self::PLACEHOLDER_PRODUCT_IMAGE_388X388;
        }
        $images = $this->getGalleryImages(self::GALLERY_IMAGE_SIZE_388X388);
        return trim(current($images));
    }

    public function getImage600X600()
    {
        if (!$this->_product->getGallery()) {
            return self::PLACEHOLDER_PRODUCT_IMAGE_600X600;
        }
        $images = $this->getGalleryImages(self::GALLERY_IMAGE_SIZE_600X600);
        return trim(current($images));
    }

    public function getImage640X300()
    {
        if (!$this->_product->getGallery()) {
            return self::PLACEHOLDER_PRODUCT_IMAGE_640X300;
        }
        $images = $this->getGalleryImages(self::GALLERY_IMAGE_SIZE_640X300);
        return trim(current($images));
    }

    /**
     * 获取CDN上图片
     * @param string $size
     * @param $merchantId
     * @return array
     */
    public function getGalleryImages($size = self::GALLERY_IMAGE_SIZE_180X180)
    {
        $gallery = $this->_product->getGallery();
        if (is_string($gallery)) {
            $gallery = explode(';', $this->_product->getGallery());
        }
        $images = array();
        foreach ($gallery as $url) {
            if (strlen(trim($url)) === 0) {
                continue;
            }
            $imageUrl = $url;
            $imageUrl = str_replace(self::GALLERY_IMAGE_SIZE_600X600, $size, $imageUrl);
            $imageUrl = str_replace(self::GALLERY_IMAGE_URL_SOURCE_NAME, $size, $imageUrl);
            $images[] = $imageUrl;
        }
        return $images;
    }

    public function getFinalPrice()
    {
        //morrowind 添加 特价限制
        /** @var Date $date */
        $date = new Date();
        $todayTime = $date->timestamp();
        $getSpecialFromTime = strtotime($this->_product->getSpecialFromDate());
        $getSpecialToTime = strtotime($this->_product->getSpecialToDate());
        $isSpecialPrice = ($getSpecialFromTime && $getSpecialFromTime > $todayTime) ? false : true;
        $isSpecialPrice = ($getSpecialToTime && $isSpecialPrice && $todayTime > $getSpecialToTime) ? false : $isSpecialPrice;
        $finalPrice = $this->_product->getPrice();
        if ($this->_product->getSpecialPrice() && $this->_product->getSpecialPrice() > 0
            && ($this->_product->getSpecialPrice() < $this->_product->getPrice())
            && $isSpecialPrice
        ) {
            $finalPrice = $this->_product->getSpecialPrice();
        }
        return Tools::formatPrice($finalPrice);
    }

    public function setPrice($price)
    {
        $this->_product->setPrice(Tools::formatPrice($price));
    }

    public function getPrice()
    {
        return Tools::formatPrice($this->_product->getPrice());
    }

    public function getOriginalPrice()
    {
        return Tools::formatPrice($this->_product->getOriginalPrice());
    }

    public function getBarcode()
    {
        return $this->_product->getBarcode();
    }

    public function getFirstCategoryId()
    {
        return $this->_product->getFirstCategoryId();
    }

    public function getSecondCategoryId()
    {
        return $this->_product->getSecondCategoryId();
    }

    public function getThirdCategoryId()
    {
        return $this->_product->getThirdCategoryId();
    }

    public function getSpecification()
    {
        return $this->_product->getSpecification();
    }

    public function getPackageSpe()
    {
        return $this->_product->getPackageSpe();
    }

    public function getTags()
    {
        $tags = array();
        /** @var Tag $tag */
        foreach ($this->_product->getTags() as $tag) {
            array_push($tags, $tag->toArray());
        }
        return $tags;
    }

    public function getRebatesAll()
    {
        return $this->_product->getRebatesAll();
    }

    public function getRebatesWholesaler()
    {
        return $this->_product->getRebatesWholesaler();
    }

    public function getRebatesLelai()
    {
        return $this->_product->getRebatesLelai();
    }

    public function getPackageNum()
    {
        return $this->_product->getPackageNum();
    }

    public function getPackage()
    {
        return $this->_product->getPackage();
    }

    public function getWeight()
    {
        return 0;
    }

    public function getRebates()
    {
        return $this->_product->getRebates();
    }

    public function getSku()
    {
        return '';
    }

    public function getWholesalerId()
    {
        return $this->_product->getWholesalerId();
    }

    public function getAdditionalInfo()
    {
        return $this->_product->getAdditionalInfo();
    }

    public function getRelativeProducts()
    {
        return $this->_product->getRelativeProducts();
    }

    public function setNum($num)
    {
        $this->_product->setNum($num);
    }

    public function setWholesalerId($wholesalerId)
    {
        $this->_product->setWholesalerId($wholesalerId);
    }

    public function setBuyPath($buyPath)
    {
        $this->_product->setBuyPath($buyPath);
    }


    public function setType($buyPath)
    {
        $this->_product->setType($buyPath);
    }

    public function getNum()
    {
        return $this->_product->getNum();
    }

    public function getSubsidiesWholesaler()
    {
        return $this->_product->getSubsidiesWholesaler();
    }

    public function getSubsidiesLelai()
    {
        return $this->_product->getSubsidiesLelai();
    }

    public function getLelaiRebates()
    {
        return $this->_product->getLelaiRebates();
    }

    public function getOrigin()
    {
        return $this->_product->getOrigin();
    }

    public function getPromotionText()
    {
        return $this->_product->getPromotionText();
    }

    /**
     * @return string
     */
    public function getRestrictDaily()
    {
        return $this->_product->getRestrictDaily();
    }

    public function getRuleId()
    {
        return $this->_product->getRuleId();
    }

    public function getBuyPath()
    {
        return $this->_product->getBuyPath();
    }

    public function getType()
    {
        return $this->_product->getType();
    }

    public function getSalesTypes()
    {
        return $this->_product->getSalesTypesStr();
    }

    public function getActivityId()
    {
        return $this->_product->getActivityId();
    }

    public function getLsin()
    {
        return $this->_product->getLsin();
    }

    public function toArray()
    {
        return $this->_product->toArray();
    }

    /**
     * @return bool
     */
    public function isSpecialPrice()
    {
        if ($this->_product->getOriginalPrice() > $this->_product->getPrice()) {
            return true;
        }
        return false;
    }

    /**
     *
     */
    public function __clone()
    {
//        $this->_product = clone $this->_product; // 没用，pb的values估计是引用。。
        $tmp = new \service\message\common\Product();
        $tmp->setFrom($this->_product->toArray());
        $this->_product = $tmp;
    }
}