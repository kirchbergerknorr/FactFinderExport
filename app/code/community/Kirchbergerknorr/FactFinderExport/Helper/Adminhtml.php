<?php
/**
 * File description
 *
 * @category    Kirchbergerknorr
 * @package     Kirchbergerknorr
 * @author      Aleksey Razbakov <ar@kirchbergerknorr.de>
 * @copyright   Copyright (c) 2014 kirchbergerknorr GmbH (http://www.kirchbergerknorr.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Kirchbergerknorr_FactFinderExport_Helper_Adminhtml extends Mage_Core_Helper_Abstract
{
    public function getJsBasedOnConfig()
    {
        if (Mage::getStoreConfigFlag('kirchbergerknorr/factfinderexport/enabled')) {
            return 'js/factfinderexport.js';
        } else {
            return '';
        }
    }
}