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
 * GiantPoints Helper
 *
 * @category    MageGiant
 * @package     MageGiant_GiantPoints
 * @author      MageGiant Developer
 */
class Magegiant_GiantPoints_Helper_Conversion_Earning extends Magegiant_GiantPoints_Helper_Conversion_Abstract
{

    /**
     * get Total Point that customer can earn by purchase current order/ quote
     *
     * @param null|Mage_Sales_Model_Quote $quote
     * @return int
     */
    public function getTotalEarningPoints($quote = null)
    {
        if (is_null($quote)) {
            $quote = $this->getQuote();
        }

        $customer      = Mage::helper('giantpoints/customer')->getCustomer();
        $amountToPoint = $this->getAmountToPoints($quote);
        /*Earning rate*/
        $totalEarnPoints = $this->getEarningByRate($amountToPoint, $quote->getStoreId());
        $container       = new Varien_Object(array(
            'total_earn_points' => $totalEarnPoints,
        ));
        Mage::dispatchEvent('giantpoints_calculation_earning_total_points', array(
            'quote'     => $quote,
            'container' => $container,
        ));
        /*Check max earning points*/
        $totalEarnPoints          = $container->getTotalEarnPoints();
        $maximumPointsPerCustomer = Mage::helper('giantpoints/config')->getMaxPointPerCustomer();
        if ($maximumPointsPerCustomer) {
            $customersPoints = 0;
            if ($customer) {
                $customersPoints = Mage::getModel('giantpoints/customer')->getAccountByCustomer($customer)->getBalance();
            }
            if ($totalEarnPoints + $customersPoints > $maximumPointsPerCustomer) {
                $totalEarnPoints = $maximumPointsPerCustomer - $customersPoints;
            }
        }
        $totalEarnPoints = max($totalEarnPoints, 0);
        return $totalEarnPoints;
    }

    public function getAmountToPoints($quote = null)
    {
        if (is_null($quote)) {
            $quote = $this->getQuote();
        }
        if ($quote->isVirtual()) {
            $address = $quote->getBillingAddress();
        } else {
            $address = $quote->getShippingAddress();
        }
        $helperConfig             = Mage::helper('giantpoints/config');
        $pointsEarningCalculation = $helperConfig->getPointsEarningCalculation();
        $baseSubtotalWithDiscount = $address->getData('base_subtotal') + $address->getData('base_discount_amount');
        $baseSubtotalWithDiscount -= $address->getGiantpointsBaseDiscount();
        if (Mage::helper('giantpoints/config')->getEarningByShipping($quote->getStoreId())) {
            $baseSubtotalWithDiscount += $address->getBaseShippingAmount();
        }

        if ($pointsEarningCalculation != Magegiant_GiantPoints_Model_System_Config_Source_Calculation::POINTS_BEFORE_TAX) {
            $baseSubtotalWithDiscount += $address->getBaseTaxAmount();
        }
        $baseGrandTotal = max(0, $baseSubtotalWithDiscount);

        return $baseGrandTotal;
    }

    /**
     * calculate quote earning points by system rate
     *
     * @param float $baseGrandTotal
     * @param mixed $store
     * @return int
     */
    public function getEarningByRate($baseGrandTotal, $store = null)
    {
        $customerGroupId = $this->getCustomerGroupId();
        $websiteId       = $this->getWebsiteId();

        $cacheKey = "earning_rate_points:$customerGroupId:$websiteId:$baseGrandTotal";
        if ($this->hasCache($cacheKey)) {
            return $this->getCache($cacheKey);
        }
        $rate = Mage::getSingleton('giantpoints/rate')->getConversionRate(
            Magegiant_GiantPoints_Model_Rate::MONEY_TO_POINT, $customerGroupId, $websiteId
        );
        if ($rate && $rate->getId()) {
            /**
             * end update
             */
            if ($baseGrandTotal < 0) {
                $baseGrandTotal = 0;
            }
            $points = Mage::helper('giantpoints/config')->getRoundingMethod(
                $baseGrandTotal * $rate->getPoints() / $rate->getMoney(), $store
            );
            $this->saveCache($cacheKey, $points);
        } else {
            $this->saveCache($cacheKey, 0);
        }

        return $this->getCache($cacheKey);
    }

    public function getEarnedPointsSummary($quote = null)
    {
        if (is_null($quote)) {
            $quote = $this->getQuote();
        }
        if ($quote->isVirtual()) {
            $address = $quote->getBillingAddress();
        } else {
            $address = $quote->getShippingAddress();
        }

        return $address->getGiantpointsEarn();
    }

}