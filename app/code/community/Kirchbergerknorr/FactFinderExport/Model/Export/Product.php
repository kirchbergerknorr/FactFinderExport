<?php
/**
 * Export Model
 *
 * @category    Kirchbergerknorr
 * @package     Kirchbergerknorr_FactFinderExport
 * @author      Aleksey Razbakov <ar@kirchbergerknorr.de>
 * @copyright   Copyright (c) 2014 kirchbergerknorr GmbH (http://www.kirchbergerknorr.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Kirchbergerknorr_FactFinderExport_Model_Export_Product extends Flagbit_FactFinder_Model_Export_Product
{
    private $csvFileName;
    private $lastProductId;

    public function log($message)
    {
        Mage::getModel('factfinderexport/observer')->log($message);
    }

    /**
     * Init resource model
     *
     */
    protected function _construct()
    {
        parent::_construct();

        $csvFileName = Mage::getStoreConfig('kirchbergerknorr/factfinderexport/export_path');

        $this->csvFileName = Mage::getBaseDir() . $csvFileName;
        $this->log("Filename: {$this->csvFileName}");
    }

    public function setState($state)
    {
        switch ($state)
        {
            case('started'):
                $this->log("Export started");

                if (file_exists($this->csvFileName)) {
                    unlink($this->csvFileName);
                }

                file_put_contents($this->csvFileName.".processing", '');
                break;

            case('finished'):
                rename($this->csvFileName.".processing", $this->csvFileName);
                $this->log("Export finished at {$this->lastProductId}");
                break;
        }
    }


    /**
     * Add row to CSV file
     *
     * @param array $data
     */
    protected function _addCsvRow($data)
    {
        foreach ($data as &$item) {
            $item = str_replace(array("\r", "\n", "\""), array(' ', ' ', "''"), trim( strip_tags($item), ';') );
        }

        $row = '"'.implode('";"', $data).'"'."\n";

        file_put_contents($this->csvFileName, $row, FILE_APPEND);
    }

    /**
     * Retrieve searchable products per store and set limit from configuration
     *
     * @param int $storeId
     * @param array $staticFields
     * @param array|int $productIds
     * @param int $lastProductId
     * @param int $limit
     * @return array
     */
    protected function _getSearchableProducts($storeId, array $staticFields, $productIds = null, $lastProductId = 0,
                                              $limit = 100)
    {
        $limit = Mage::getStoreConfig('kirchbergerknorr/factfinderexport/queue');

        $this->lastProductId = $lastProductId;
        $this->log("Current ProductId: {$lastProductId}");

        return parent::_getSearchableProducts($storeId, $staticFields, $productIds, $lastProductId, $limit);
    }

    /**
     * Export Product Data with Attributes
     * Output to CSV file
     *
     * @param int $storeId Store View Id
     */
    public function doExport($storeId = null)
    {
        $this->setState('started');

        parent::doExport($storeId);

        $this->setState('finished');
    }
}