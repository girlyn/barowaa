<?php
/**
 * MageGiant
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MageGiant.com license that is
 * available through the world-wide-web at this URL:
 * http://magegiant.com/license-agreement/
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    MageGiant
 * @package     MageGiant_GiantPoints
 * @copyright   Copyright (c) 2014 MageGiant (http://magegiant.com/)
 * @license     http://magegiant.com/license-agreement/
 */

/**
 * Giantpoints Model
 *
 * @category    MageGiant
 * @package     MageGiant_GiantPoints
 * @author      MageGiant Developer
 */
class Magegiant_GiantPoints_Model_Rate extends Mage_Core_Model_Abstract
{
    /**
     * Rate direction - Spending Point
     */
    const POINT_TO_MONEY = 1;

    /**
     * Rate direction - Earning Point
     */
    const MONEY_TO_POINT = 2;


    protected $_currentCustomer;
    protected $_currentWebsite;

    public function _construct()
    {
        parent::_construct();
        $this->_init('giantpoints/rate');
    }

    /**
     * prepare customer group and website for save to database
     *
     * @return Magegiant_GiantPoints_Model_Rate
     */
    protected function _beforeSave()
    {
        if (is_array($this->getWebsiteIds())) {
            $this->setWebsiteIds(implode(',', $this->getWebsiteIds()));
        }
        if (is_array($this->getCustomerGroupIds())) {
            $this->setCustomerGroupIds(implode(',', $this->getCustomerGroupIds()));
        }

        return parent::_beforeSave();
    }

    /**
     * get Rate by direction
     *
     * @param type $direction
     * @param type $customerGroupId
     * @param type $websiteId
     * @return false | Magegiant_GiantPoints_Model_Rate
     */
    public function getConversionRate($direction = 1, $customerGroupId = null, $websiteId = null)
    {
        if (is_null($customerGroupId)) {
            $customerGroupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
        }
        if (is_null($websiteId)) {
            $websiteId = Mage::app()->getStore()->getWebsiteId();
        }
        $rateCollection = $this->getCollection()
            ->addFieldToFilter('direction', $direction)
            ->addFieldToFilter('website_ids', array('finset' => $websiteId))
            ->addFieldToFilter('customer_group_ids', array('finset' => $customerGroupId))
            ->addFieldToFilter('points', array('gt' => 0))
            ->addFieldToFilter('money', array('gt' => 0));
        $rateCollection->getSelect()->order('priority DESC');
        $rate = $rateCollection->getFirstItem();
        if ($rate && $rate->getId()) {
            return $rate;
        }

        return false;
    }

    /**
     * exchange point to money
     *
     * @param $amount
     * @return float|int
     * @throws Exception
     */
    public function exchange($amount)
    {
        if (!$this->getPoints() || !$this->getMoney()) {
            throw new Exception(Mage::helper('giantpoints')->__('Exchange rates are incorrect'));
        }
        if ($this->getDirection() == self::POINT_TO_MONEY) {
            $newAmount = Mage::helper('giantpoints/config')->getRoundingMethod($amount * $this->getMoney() / $this->getPoints(), 2);
        } else {
            $newAmount = (int)Mage::helper('giantpoints/config')->getRoundingMethod(
                $amount * $this->getPoints() / $this->getMoney()
            );
        }

        return $newAmount;
    }

    /**
     * load rate by direction
     *
     * @param $direction
     * @return $this
     */
    public function loadByDirection($direction)
    {
        $this->getResource()->loadRateByCustomerWebsiteDirection(
            $this, $this->getCurrentCustomer(), $this->getCurrentWebsite(), $direction
        );

        return $this;
    }

    /**
     * @return Mage_Core_Model_Website
     */
    public function getWebsite()
    {
        return Mage::app()->getWebsite($this->getWebsiteId());
    }

    public function getCurrentCustomer()
    {
        if (!$this->_currentCustomer) {
            $this->_currentCustomer = Mage::getModel('customer/session')->getCustomer();
        }

        return $this->_currentCustomer;
    }

    public function setCurrentCustomer($customer)
    {
        $this->_currentCustomer = $customer;

        return $this;
    }

    public function getCurrentWebsite()
    {
        if (!$this->_currentWebsite) {
            $this->_currentWebsite = Mage::app()->getWebsite();
        }

        return $this->_currentWebsite;
    }

    /*++++++++++++++++Calculator earning point+++++++++++++++++++*/
    public function processEarnPoint(Mage_Sales_Model_Quote_Item_Abstract $item, $address)
    {
        $item->setGiantpointsEarn(0);
        $baseItemPrice = $this->_getItemBasePrice($item);
        $baseItemPrice -= $item->getBaseDiscountAmount();
        if ($baseItemPrice < 0) {
            return $this;
        }
        $qty = $item->getTotalQty();
        /*Item earn point by rate*/
        $totalEarnByRate = $address->getGiantpointsEarn();
        $baseSubTotal    = $address->getBaseSubtotalWithDiscount();
        if ($totalEarnByRate > 0.001 && $baseSubTotal > 0.001) {
            $ratio          = $totalEarnByRate / $baseSubTotal;
            $itemEarnByRate = round($qty * $baseItemPrice * $ratio);
            if ($itemEarnByRate > 0.0001) {
                $maxItemEarn = $address->getGiantpointsEarn() - $address->getItemEarnedByRate();
                if ($itemEarnByRate > $maxItemEarn)
                    $itemEarnByRate = $maxItemEarn;
                $item->setGiantpointsEarn($itemEarnByRate);
                $address->setItemEarnedByRate($address->getItemEarnedByRate() + $itemEarnByRate);
            }
        }

        return $this;
    }

    protected function _getItemBasePrice($item)
    {
        $price = $item->getDiscountCalculationPrice();

        return ($price !== null) ? $item->getBaseDiscountCalculationPrice() : $item->getBaseCalculationPrice();
    }

    /**
     * Return item price
     *
     * @param Mage_Sales_Model_Quote_Item_Abstract $item
     * @return float
     */
    protected function _getItemPrice($item)
    {
        $price     = $item->getDiscountCalculationPrice();
        $calcPrice = $item->getCalculationPrice();

        return ($price !== null) ? $price : $calcPrice;
    }

    /**
     * Return item original price
     *
     * @param Mage_Sales_Model_Quote_Item_Abstract $item
     * @return float
     */
    protected function _getItemOriginalPrice($item)
    {
        return Mage::helper('tax')->getPrice($item, $item->getOriginalPrice(), true);
    }

    /**
     * Return item base original price
     *
     * @param Mage_Sales_Model_Quote_Item_Abstract $item
     * @return float
     */
    protected function _getItemBaseOriginalPrice($item)
    {
        return Mage::helper('tax')->getPrice($item, $item->getBaseOriginalPrice(), true);
    }

    /*+++++++++++++++++++Calculator spent point discount++++++++++++++++++++++*/
    public function processSpentPoint(Mage_Sales_Model_Quote_Item_Abstract $item, $address)
    {
        $item->setGiantpointsBaseDiscount(0);
        $item->setGiantpointsDiscount(0);
        $item->setGiantpointsSpent(0);
        $quote         = $item->getQuote();
        $itemPrice     = $this->_getItemPrice($item);
        $baseItemPrice = $this->_getItemBasePrice($item);
        if ($itemPrice < 0) {
            return $this;
        }
        $qty = $item->getTotalQty();
        /*Spent point item discount*/
        $store                  = $address->getQuote()->getStore();
        $maxBaseDiscount        = $address->getGiantpointsBaseDiscount();
        $totalItemsBaseDiscount = $address->getTotalGiantPointsBaseDiscount();
        $baseSubTotal           = $address->getBaseSubtotal();
        if ($maxBaseDiscount > 0.0001) {
            $ratio                       = $maxBaseDiscount / $baseSubTotal;
            $itemGiantpointsBaseDiscount = round($qty * $baseItemPrice * $ratio, 2, PHP_ROUND_HALF_DOWN);
            if ($itemGiantpointsBaseDiscount > 0.0001) {
                $maxItemDiscount = $maxBaseDiscount - $totalItemsBaseDiscount;
                if ($itemGiantpointsBaseDiscount > $maxItemDiscount) {
                    $itemGiantpointsBaseDiscount = $maxItemDiscount;
                }
                $itemGiantpointsDiscount = $quote->getStore()->convertPrice($itemGiantpointsBaseDiscount);
                /*==Process tax after discount==*/
                if (Mage::helper('tax')->applyTaxAfterDiscount($store) &&
                    Mage::helper('giantpoints/config')->getPointsSpendingCalculation() == Magegiant_GiantPoints_Model_System_Config_Source_Calculation::POINTS_BEFORE_TAX
                ) {
                    $rate             = $this->getItemRateOnQuote($item->getProduct(), $store);
                    $sentPointTax     = $itemGiantpointsDiscount * $rate;
                    $sentPointBaseTax = $itemGiantpointsBaseDiscount * $rate;
                    $item->setSpentPointTax($sentPointTax);
                    $item->setSpentPointBaseTax($sentPointBaseTax);

                }
                /*==End process tax after discount==*/
                $item->setGiantpointsBaseDiscount($itemGiantpointsBaseDiscount);
                $item->setGiantpointsDiscount($itemGiantpointsDiscount);
                $address->setTotalGiantPointsBaseDiscount($address->getTotalGiantPointsBaseDiscount() + $itemGiantpointsBaseDiscount);
            }
        }
        /*Spending points for each item*/

        $totalPointSent = $address->getGiantpointsSpent();
        if ($totalPointSent) {
            $ratio          = $totalPointSent / $baseSubTotal;
            $itemPointSpent = round($qty * $baseItemPrice * $ratio, 0, PHP_ROUND_HALF_DOWN);
            if ($itemPointSpent > 0.0001) {
                if ($item->getIsLast()) {
                    $itemPointSpent = $totalPointSent - $address->getTotalPointSpent();
                }
                $item->setGiantpointsSpent($itemPointSpent);
                $address->setTotalPointSpent($address->getTotalPointSpent() + $itemPointSpent);

            }

        }

        return $this;
    }

    public function getItemRateOnQuote($product, $store)
    {
        //Calculate rate to subtract taxable amount
        $priceIncludesTax = (bool)Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX, $store);
        $taxClassId       = $product->getTaxClassId();
        if ($taxClassId && $priceIncludesTax) {
            $request = Mage::getSingleton('tax/calculation')->getRateRequest(false, false, false, $store);
            $rate    = Mage::getSingleton('tax/calculation')
                ->getRate($request->setProductClassId($taxClassId));
            $rate    = 1 + $rate / 100;
        } else $rate = 1;

        return $rate;
    }

}