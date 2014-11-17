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

    public function getLastProductId()
    {
        $lastFileName = $this->csvFileName.".last";
        if (!file_exists($lastFileName)) {
            return 0;
        } else {
            return file_get_contents($lastFileName);
        }
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

        $idFieldName = Mage::helper('factfinder/search')->getIdFieldName();
        $exportImageAndDeeplink = Mage::getStoreConfigFlag('factfinder/export/urls', $storeId);
        if ($exportImageAndDeeplink) {
            $imageType = Mage::getStoreConfig('factfinder/export/suggest_image_type', $storeId);
            $imageSize = (int) Mage::getStoreConfig('factfinder/export/suggest_image_size', $storeId);
        }

        $header = $this->_getExportAttributes($storeId);
        $this->_addCsvRow($header);

        // preparesearchable attributes
        $staticFields   = array();
        foreach ($this->_getSearchableAttributes('static', 'system', $storeId) as $attribute) {
            $staticFields[] = $attribute->getAttributeCode();
        }
        $dynamicFields  = array(
            'int'       => array_keys($this->_getSearchableAttributes('int')),
            'varchar'   => array_keys($this->_getSearchableAttributes('varchar')),
            'text'      => array_keys($this->_getSearchableAttributes('text')),
            'decimal'   => array_keys($this->_getSearchableAttributes('decimal')),
            'datetime'  => array_keys($this->_getSearchableAttributes('datetime')),
        );

        // status and visibility filter
        $visibility     = $this->_getSearchableAttribute('visibility');
        $status         = $this->_getSearchableAttribute('status');
        $visibilityVals = Mage::getSingleton('catalog/product_visibility')->getVisibleInSearchIds();
        $statusVals     = Mage::getSingleton('catalog/product_status')->getVisibleStatusIds();

        $lastProductId = $this->getLastProductId();

        $products = $this->_getSearchableProducts($storeId, $staticFields, null, $lastProductId);
        if (!$products) {
            return false;
        }

        $productRelations   = array();
        foreach ($products as $productData) {
            $lastProductId = $productData['entity_id'];
            file_put_contents($this->csvFileName.".last", $lastProductId);
            $productAttributes[$productData['entity_id']] = $productData['entity_id'];
            $productChilds = $this->_getProductChildIds($productData['entity_id'], $productData['type_id']);
            $productRelations[$productData['entity_id']] = $productChilds;
            if ($productChilds) {
                foreach ($productChilds as $productChild) {
                    $productAttributes[$productChild['entity_id']] = $productChild;
                }
            }
        }

        $productAttributes		= $this->_getProductAttributes($storeId, array_keys($productAttributes), $dynamicFields);
        foreach ($products as $productData) {
            if (!isset($productAttributes[$productData['entity_id']])) {
                continue;
            }
            $productAttr = $productAttributes[$productData['entity_id']];

            if (!isset($productAttr[$visibility->getId()]) || !in_array($productAttr[$visibility->getId()], $visibilityVals)) {
                continue;
            }
            if (!isset($productAttr[$status->getId()]) || !in_array($productAttr[$status->getId()], $statusVals)) {
                continue;
            }

            $productIndex = array(
                $productData['entity_id'],
                $productData[$idFieldName],
                $productData['sku'],
                $this->_getCategoryPath($productData['entity_id'], $storeId),
                $this->_formatFilterableAttributes($this->_getSearchableAttributes(null, 'filterable'), $productAttr, $storeId),
                $this->_formatSearchableAttributes($this->_getSearchableAttributes(null, 'searchable'), $productAttr, $storeId)
            );

            if ($exportImageAndDeeplink) {
                $product = Mage::getModel("catalog/product");
                $product->setStoreId($storeId);
                $product->load($productData['entity_id']);

                $productIndex[] = (string) $this->_imageHelper->init($product, $imageType)->resize($imageSize);
                $productIndex[] = $product->getProductUrl();
            }

            $this->_getAttributesRowArray($productIndex, $productAttr, $storeId);

            $this->_addCsvRow($productIndex);

            if ($productChilds = $productRelations[$productData['entity_id']]) {
                foreach ($productChilds as $productChild) {
                    if (isset($productAttributes[$productChild['entity_id']])) {
                        /* should be used if sub products should not be exported because of their status
                        $subProductAttr = $productAttributes[$productChild[ 'entity_id' ]];
                        if (!isset($subProductAttr[$status->getId()]) || !in_array($subProductAttr[$status->getId()], $statusVals)) {
                            continue;
                        } */

                        $subProductIndex = array(
                            $productChild['entity_id'],
                            $productData[$idFieldName],
                            $productChild['sku'],
                            $this->_getCategoryPath($productData['entity_id'], $storeId),
                            $this->_formatFilterableAttributes($this->_getSearchableAttributes(null, 'filterable'), $productAttributes[$productChild['entity_id']], $storeId),
                            $this->_formatSearchableAttributes($this->_getSearchableAttributes(null, 'searchable'), $productAttributes[$productChild['entity_id']], $storeId)
                        );
                        if ($exportImageAndDeeplink) {
                            //dont need to add image and deeplink to child product, just add empty values
                            $subProductIndex[] = '';
                            $subProductIndex[] = '';
                        }
                        $this->_getAttributesRowArray($subProductIndex, $productAttributes[$productChild['entity_id']], $storeId);

                        $this->_addCsvRow($subProductIndex);
                    }
                }
            }
        }

        unset($products);
        unset($productAttributes);
        unset($productRelations);
        flush();

        $this->setState('finished');
    }
}