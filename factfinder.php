<?php

setcookie("MGT_NO_CACHE", true);

ini_set('session.use_cookies', 0);
ini_set('session.cache_limiter', '');

require_once "app/Mage.php";

Mage::app('admin');
Mage::setIsDeveloperMode(true);

$fileName = Mage::getBaseDir() . Mage::getStoreConfig('kirchbergerknorr/factfinderexport/export_path');

$restart = false;

if(isset($_GET['stop'])) {
    if (file_exists($fileName.".run")) {
        unlink($fileName.".run");
    }
    if (file_exists($fileName.".processing")) {
        unlink($fileName.".processing");
    }
    if (file_exists($fileName.".last")) {
        unlink($fileName.".last");
    }
    return false;
    exit;
};

if(isset($_GET['last'])) {
    if (file_exists($fileName)) {
        echo date("Y-m-d H:i:s", filemtime($fileName));
    } else {
        echo 'Never generated';
    }
    exit;
};

if(isset($_GET['restart'])) {
    $restart = true;
};

Mage::getModel('factfinderexport/observer')->export(null, $restart);