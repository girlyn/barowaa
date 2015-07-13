<?php

/**
 * Magegiant
 * http://magegiant.com
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@magegiant.com so we can send you a copy immediately.
 *
 * @category    Magegiant
 * @package     Magegiant_Wysiwyg
 * @author      Stefan Wieczorek <stefan.wieczorek@magegiant.com>
 * @copyright   Copyright (c) 2012 (http://magegiant.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Magegiant_Wysiwyg_Model_Wysiwyg extends Mage_Core_Model_Abstract
{
    const CSS_CLASS                               = 'amazing-wysiwyg';
    const XML_PATH_MAGEGIANT_WYSIWYG_ACTIVE       = 'default/magegiant_wysiwyg/magegiant_wysiwyg/active';
    const XML_PATH_MAGEGIANT_WYSIWYG_PRODUCTS     = 'default/magegiant_wysiwyg/magegiant_wysiwyg/enable_wysiwyg_product';
    const XML_PATH_MAGEGIANT_WYSIWYG_CMS_PAGE     = 'default/magegiant_wysiwyg/magegiant_wysiwyg/enable_wysiwyg_cms_page';
    const XML_PATH_MAGEGIANT_WYSIWYG_STATIC_BLOCK = 'default/magegiant_wysiwyg/magegiant_wysiwyg/enable_wysiwyg_static_block';

    static protected $_isEnabled;

    static public function isEnabled()
    {
        if (!self::$_isEnabled) {
            self::$_isEnabled = (int)self::_getConfigurationValue(self::XML_PATH_MAGEGIANT_WYSIWYG_ACTIVE);
        }

        return self::$_isEnabled;
    }

    public function isEnabledForProduct()
    {
        return (int)self::_getConfigurationValue(self::XML_PATH_MAGEGIANT_WYSIWYG_PRODUCTS);
    }

    public function isEnabledForCmsPage()
    {
        return (int)self::_getConfigurationValue(self::XML_PATH_MAGEGIANT_WYSIWYG_CMS_PAGE);
    }

    public function isEnabledForStaticBlock()
    {
        return (int)self::_getConfigurationValue(self::XML_PATH_MAGEGIANT_WYSIWYG_STATIC_BLOCK);
    }

    static protected function _getConfigurationValue($path)
    {
        return Mage::getConfig()->getNode($path);
    }
}