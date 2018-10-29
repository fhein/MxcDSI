<?php

namespace MxcDropshipInnocigs\Helper;

class Log {

    protected $logPath;

    public function __construct() {
        $this->logPath = Shopware()->DocPath().'var/log/mxc_dropship_innocigs-'.date('Y-m-d').'.log';
    }

    public function log(string $msg) {
        file_put_contents($this->logPath, $msg . PHP_EOL, FILE_APPEND);
    }
}