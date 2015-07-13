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
class Magegiant_Wysiwyg_Block_Adminhtml_Js extends Mage_Adminhtml_Block_Template
{
    public function getLoadUrl()
    {
        $formKey = Mage::getSingleton('core/session')->getFormKey();

        return $this->getUrl('*/wysiwyg/load', array('form_key' => $formKey));
    }
}