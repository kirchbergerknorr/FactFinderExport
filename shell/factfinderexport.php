<?php

ini_set('session.use_cookies', 0);
ini_set('session.cache_limiter', '');

require_once "../app/Mage.php";
require_once 'abstract.php';

Mage::app('admin');
Mage::setIsDeveloperMode(true);

class Kirchbergerknorr_Shell_FactFinderExport extends Mage_Shell_Abstract
{
    public function log($message, $p1 = null, $p2 = null, $p3 = null)
    {
        echo sprintf($message, $p1, $p2, $p3)."\n";
    }

    public function export()
    {
        define('FACTFINDEREXPORT_ECHO_LOGS', true);
        Mage::getModel('factfinderexport/observer')->export();
    }

    public function help()
    {
        $this->log('FactFinderExport Help:');

        $help = <<< HELP

    Start export:

      php factfinderexport.php

HELP;

        $this->log($help);
    }

    public function run($params = false)
    {
        if (!$params || count($params) < 2) {
            $this->export();
            return false;
        }
    }
}


$shell = new Kirchbergerknorr_Shell_FactFinderExport();

try {
    $shell->run($argv);
} catch (Exception $e) {
    $shell->log($e->getMessage());
}
