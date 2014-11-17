<?php
/**
 * Observer
 *
 * @category    Kirchbergerknorr
 * @package     Kirchbergerknorr_FactFinderExport
 * @author      Aleksey Razbakov <ar@kirchbergerknorr.de>
 * @copyright   Copyright © 2014 kirchbergerknorr GmbH (http://www.kirchbergerknorr.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Kirchbergerknorr_FactFinderExport_Model_Observer
{
    const LOGFILE = 'kk_factfinderexport';

    public function log($message)
    {
        Mage::log($message, null, self::LOGFILE.'.log');

        if (defined('FACTFINDEREXPORT_ECHO_LOGS')) {
            echo date("H:i:s").": ".$message."\n";
        }
    }

    public function export($observer = null, $reStart = true)
    {
        if (!Mage::getStoreConfig('kirchbergerknorr/factfinderexport/enabled')) {
            $this->log('FactFinderExport is disabled');
            return false;
        }

        $csvFileName = Mage::getStoreConfig('kirchbergerknorr/factfinderexport/export_path');
        if(!$csvFileName) {
            $this->log("kirchbergerknorr/factfinderexport/export_path is empty");
            return false;
        }

        try {
            Mage::getModel('factfinderexport/export_product')->doExport(1, $reStart);
        } catch (Exception $e) {
            $this->log($e->getMessage());
        }
    }
}