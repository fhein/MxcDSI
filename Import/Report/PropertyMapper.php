<?php

namespace MxcDropshipInnocigs\Import\Report;

use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use MxcDropshipInnocigs\Report\ArrayMap;
use MxcDropshipInnocigs\Report\ArrayReport;
use MxcDropshipInnocigs\Report\Mapper\SuccessiveReplacer;

class PropertyMapper implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    /** @var ArrayReport $reporter */
    protected $reporter;

    /** @var array $topics */
    protected $topics;

    protected $nameTrace;

    protected $config;

    public function __construct()
    {
        $this->reporter = new ArrayReport();
    }

    public function __invoke(array $topics, array $config) {
        $this->topics = $topics;
        $this->nameTrace = $topics['name'] ?? [];
        ksort($this->nameTrace);
        $this->config = $config;
        if (! isset($this->config['log'])) return;
        foreach ($this->config['log'] as $topic) {
            $method = 'log' . ucfirst($topic);
            if (method_exists($this, $method) && @$topics[$topic] !== null) {
                $this->$method();
            }
        }
    }

    protected function logBrand()
    {
        $brands = $this->topics['brand'];
        ksort($brands);
        ($this->reporter)([ 'pmBrands' => $brands]);
    }

    protected function logSupplier()
    {
        $suppliers = $this->topics['supplier'];
        ksort($suppliers);
        ($this->reporter)([ 'pmSuppliers' => $suppliers]);
    }

    protected function logOption()
    {
        $optionMapping = $this->topics['option'];
        ksort($optionMapping);
        $applied = array_filter($optionMapping, function($value) { return $value['fixApplied'] === true; });
        $applied = array_flip(array_flip(array_column($applied, 'option')));
        $unused = array_diff(array_keys($this->config['product_name_option_fixes']), $applied);
        $optionReport['unused_mappings'] = $unused;

        foreach ($optionMapping as $key => $record) {
            $entry = [ 'option' => $record['option'], 'before' => $record['before'], 'after ' => $record['after']];
            if ($record['mapped']) {
                $optionReport['mapped'][$key] = $entry;
                continue;
            }

            if ($record['fixAvailable']) {
                if ($record['fixApplied']) {
                    $optionReport['mapped via fix'][$key] = $entry;;
                } else {
                    $optionReport['fix not applicable'][$key] = $entry;;
                }
                continue;
            }
            $optionReport['no fix avalable'][$key] = $entry;;
        }
        ($this->reporter)([ 'pmOptionMapping' => $optionReport]);
    }

    protected function logCategory()
    {
        $categoryUsage = $this->topics['category'];
        ksort($categoryUsage);
        foreach ($categoryUsage as &$array) {
            ksort($array);
        }
        ($this->reporter)([
            'pmCategoryUsage' => $categoryUsage,
            'pmCategory'      => array_keys($categoryUsage),
        ]);
    }

    protected function logName() {

        $nameMap = array_values(array_map(function ($value) {
            return [
                'imported' => $value['imported'],
                'mapped  ' => $value['mapped'],
            ];
        }, $this->nameTrace));

        $unchangedProductNames = array_map(function ($value) {
            return ($value['imported'] === $value['mapped']);
        }, $this->nameTrace);
        $unchangedProductNames = array_keys(array_filter(
            $unchangedProductNames,
            function ($value) {
                return $value === true;
            }
        ));

        $namesWithoutRemovedOptions = array_map(function ($value) {
            return ($value['imported'] === $value['options_removed']);
        }, $this->nameTrace);
        $namesWithoutRemovedOptions = array_keys(array_filter($namesWithoutRemovedOptions, function ($value) {
            return $value === true;
        }));

        $productNames = array_flip(array_flip(array_column($nameMap, 'mapped  ')));
        sort($productNames);


        ($this->reporter)([
            'pmName'                  => $productNames,
            'pmNameTrace'             => $this->nameTrace,
            'pmNameMap'               => $nameMap,
            'pmNameUnchanged'         => $unchangedProductNames,
            'pmNameNoOptionsRemoved'  => $namesWithoutRemovedOptions,
        ]);
    }

    protected function logReplacement() {
        $pregReplace = $this->getReplacementLog('brand_prepended', 'preg_replace');
        $strReplace = $this->getReplacementLog('preg_replace_applied', 'str_replace');
        ($this->reporter)([
            'pmNamePregReplace' => $pregReplace,
            'pmNameStringReplace' => $strReplace
        ]);
    }

    /**
     * @param string $keyIndex
     * @param string $replacer
     * @return array
     */
    protected function getReplacementLog(string $keyIndex, string $replacer): array
    {
        $replacementLog = [];
        foreach ($this->nameTrace as $key => $entry) {
            $replacementLog[$key] = $entry[$keyIndex];
        }
        $mapper = new ArrayMap();
        $replacementLog = $mapper($replacementLog,[
            SuccessiveReplacer::class => [
                'replacer'     => $replacer,
                'replacements' => $this->config['product_name_replacements'][$replacer],
            ]]
        );
        return $replacementLog;
    }
}