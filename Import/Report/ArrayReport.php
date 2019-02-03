<?php

namespace MxcDropshipInnocigs\Import\Report;

use Zend\Config\Factory;
use Zend\Filter\StringToLower;
use Zend\Filter\Word\CamelCaseToUnderscore;

class ArrayReport
{
    protected $reportDir;
    protected $writer;

    public function __construct()
    {
        $this->reportDir = Shopware()->DocPath() . 'var/log/mxc_dropship_innocigs';
        if (file_exists($this->reportDir) && ! is_dir($this->reportDir)) {
            unlink($this->reportDir);
        }
        if (! is_dir($this->reportDir)) {
            mkdir($this->reportDir);
        }
    }

    public function __invoke(array $topics) {
        if (! $topics || empty($topics)) return;

        foreach ($topics as $what => $topic) {
            if (! is_string($what) || empty($topic)) continue;
            $fn = $this->getFileName($what);
            $this->writeFiles($topic, $fn);
        }
    }

    /**
     * @param array $topic
     * @param string $fn
     */
    protected function writeFiles(array $topic, string $fn)
    {
        $dir = $this->reportDir . '/';
        $actFile = $dir . $fn . '.php';
        $diffFile = $dir . $fn . '.diff.php';

        if (file_exists($actFile)) {
            /** @noinspection PhpIncludeInspection */
            $old = include $actFile;
            $diff = array_diff($topic, $old);
            if (empty($diff)) {
                if (file_exists($diffFile)) {
                    unlink($diffFile);
                }
            } else {
                Factory::toFile($diffFile, array_diff($topic, $old));
            }
        }
        Factory::toFile($actFile, $topic);
    }

    protected function getFileName(string $pluginClass) {
        $toUnderScore = new CamelCaseToUnderscore();
        $toLowerCase = new StringToLower();
        return $toLowerCase($toUnderScore($pluginClass));
    }
}