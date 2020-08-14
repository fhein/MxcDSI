<?php

namespace MxcDropshipIntegrator\Mapping\Import;

use MxcDropshipInnocigs\Models\Model;
use MxcDropshipIntegrator\Models\Product;
use MxcDropshipIntegrator\MxcDropshipIntegrator;
use MxcCommons\Toolbox\Report\ArrayMap;
use MxcCommons\Toolbox\Report\ArrayReport;
use MxcCommons\Toolbox\Report\Mapper\SuccessiveReplacer;
use MxcCommons\Defines\Constants;

class NameMapper extends BaseImportMapper implements ProductMapperInterface
{
    protected $report = [];

    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function map(Model $model, Product $product, bool $remap = false)
    {
        $name = $this->config[$product->getIcNumber()]['name'] ?? null;
        if ($remap || ! $name) {
            $name = $this->remap($model, $product);
        }
        $product->setName($name);
    }

    public function remap(Model $model, Product $product)
    {
        $modelName = $model->getProductName();
        $this->report['name'][$modelName]['model'] = $model->getModel();
        $trace['imported'] = $modelName;
        $name = $this->replace($modelName, 'name_prepare');
        $trace['name_prepared'] = $name;
//        $name = $this->removeOptionsFromModelName($name, $model);
//        $trace['options_removed'] = $name;

        // general name mapping applied first
        $result = @$this->classConfig['product_names_direct'][$model->getName()];
        if ($result !== null) {
            $trace['directly_mapped'] = $result;
            return $name;
        }

        // rule based name mapping applied next
        $name = $this->applySupplierAndBrandDeocoration($name, $product);
        $trace['brand_prepended'] = $name;

        $name = $this->replace($name, 'product_name_replacements');
        $trace['after_name_replacements'] = $name;

        $supplier = $product->getSupplier();
        $supplier = $supplier === 'Smoktech' ? 'SMOK' : $supplier;

        $search[] = '~(' . $product->getBrand() . ') ([^\-])~';
        $search[] = '~(' . $supplier . ') ([^\-])~';
        $name = preg_replace($search, '$1 - $2', $name);
        $trace['supplier_separator'] = $name;
        $search = $this->classConfig['product_names'][$product->getBrand()] ?? null;
        if (null !== $search) {
            $name = preg_replace($search, '$1 -', $name);
        }
        $trace['product_separator'] = $name;

        $name = $this->replace($name, 'name_cleanup');
        $name = preg_replace('~\s+~', ' ', $name);

        $trace['mapped'] = $name;
        $this->report['name'][$trace['imported']] = $trace;
        return $name;
    }

    public function replace(string $topic, string $what)
    {
        $config = @$this->classConfig[$what];
        if (null === $config) {
            return $topic;
        }

        foreach ($config as $replacer => $replacements) {
            $search = array_keys($replacements);
            $replace = array_values($replacements);
            $topic = $replacer($search, $replace, $topic);
        }
        return $topic;
    }

    /**
     * InnoCigs model records (which represent variants) contain a product name.
     * The product name of a model contains parts reflecting the options of the variant.
     *
     * Here we remove the option parts from the model's product name.
     *
     * Creating a new product we use the product name of the first model belonging
     * to that product and ignore the product names of the other models belonging
     * to that product.
     *
     * Anyway, for update reasons all models with the same master id should map to
     * the same product name after name mapping. This can be checked via the GUI
     * 'Check name mapping consistency'.
     *
     * NOTE: We are now using the new PARENT_NAME property supplied by the API, so
     * this function is not in use currently.
     *
     * @param string $name
     * @param Model $model
     * @return string
     */
    protected function removeOptionsFromModelName(string $name, Model $model)
    {
        $options = explode(Constants::DELIMITER_L2, $model->getOptions());

        foreach ($options as $option) {
            $option = explode(Constants::DELIMITER_L1, $option)[1];
            $number = $model->getModel();

            if (strpos($name, $option) !== false) {
                // prodcut name contains option name
                $before = $name;
                $replacement = $this->classConfig['option_replacements'][$option] ?? '';
                $name = str_replace($option, $replacement, $name);
                $this->report['option'][$number] = [
                    'before' => $before,
                    'after'  => $name,
                    'mapped' => true,
                    'option' => $option,
                ];
                continue;
            }

            $name = $this->applyOptionNameMapping($number, $name, $option);

        }
        $name = preg_replace('~\s+~', ' ', $name);
        return trim($name);
    }

    /**
     * The option names do not in every case match the string which InncCigs
     * adds to a model name to reflect that option. These discrepancies get
     * addressed with the product_name_option_fixes configuration setting,
     * which map the option name to the string found in the product name.
     *
     * @param string $model
     * @param string $name
     * @param string $option
     * @return mixed|string
     */
    public function applyOptionNameMapping(string $model, string $name, string $option)
    {
        // They introduced some cases where the option name is not equal
        // to the string added to the product name, so we have to check
        // that also.
        $o = $this->classConfig['product_name_option_fixes'][$option] ?? null;
        $fixApplied = false;
        $fixAvailable = $o !== null;
        $before = $name;
        if ($fixAvailable && $o !== '') {
            if (is_string($o)) {
                $o = [$o];
            }
            foreach ($o as $mappedOption) {
                if (strpos($name, $mappedOption) !== false) {
                    $name = str_replace($mappedOption, '', $name);
                    $fixApplied = true;
                    break;
                }
            }
        }
        $this->report['option'][$model] = [
            'before'       => $before,
            'after'        => $name,
            'option'       => $option,
            'fixAvailable' => $fixAvailable,
            'fixApplied'   => $fixApplied,
        ];
        return $name;
    }

    public function applySupplierAndBrandDeocoration(string $name, Product $product)
    {
        $brand = $product->getBrand();
        if (!$brand) {
            return $name;
        }
        $supplier = $product->getSupplier();
        if ($supplier === 'Innocigs') {
            $supplier = 'InnoCigs';
        }
        $innocigsBrands = $this->classConfig['innocigs_brands'] ?? [];
        $isInnocigsBrand = in_array($brand, $innocigsBrands);
        $isInnocigsSupplier = ($supplier === 'InnoCigs');

        if ($isInnocigsBrand && $isInnocigsSupplier) {
            // There are some products from supplier InnoCigs which are not branded
            if (strpos($name, $brand) !== 0 && !in_array($name, $this->classConfig['products_without_brand'])) {
                $name = $brand . ' - ' . $name;
            }
            return $name;
        }

        $append = $isInnocigsBrand ? ' - by ' . $brand : '';
        if ($supplier === 'Smoktech') {
            $supplier = 'SMOK';
        }

        if (! $isInnocigsSupplier) {
            $name = str_replace($brand, $supplier, $name) . $append;
        }

        return $name;
    }

    public function report()
    {
        $reporter = new ArrayReport();
        $report = $this->getNameReport();
        $report['pmOptionMapping'] = $this->getOptionReport();
        $reporter($report);
        $reporter($this->getReplacementReport());
    }

    protected function getNameReport()
    {
        if (isset($this->report['name'])) {
            $nameMap = array_values(array_map(function($value) {
                return [
                    'imported' => $value['imported'],
                    'mapped  ' => $value['mapped'],
                ];
            }, $this->report['name']));

            $unchangedProductNames = array_map(function($value) {
                return ($value['imported'] === $value['mapped']);
            }, $this->report['name']);
            $unchangedProductNames = array_keys(array_filter(
                $unchangedProductNames,
                function($value) {
                    return $value === true;
                }
            ));

            $namesWithoutRemovedOptions = array_map(function($value) {
                return ($value['imported'] === $value['options_removed']);
            }, $this->report['name']);
            $namesWithoutRemovedOptions = array_keys(array_filter($namesWithoutRemovedOptions, function($value) {
                return $value === true;
            }));

            $productNames = array_flip(array_flip(array_column($nameMap, 'mapped  ')));
            sort($productNames);
        }

        return [
            'pmName'                 => $productNames ?? [],
            'pmNameTrace'            => $this->report['name'] ?? [],
            'pmNameMap'              => $nameMap ?? [],
            'pmNameUnchanged'        => $unchangedProductNames ?? [],
            'pmNameNoOptionsRemoved' => $namesWithoutRemovedOptions ?? [],
        ];
    }

    protected function getOptionReport()
    {
        $optionMapping = $this->report['option'] ?? [];
        if (! empty($optionMapping)) return $optionMapping;

        ksort($optionMapping);
        $applied = array_filter($optionMapping, function($value) {
            return $value['fixApplied'] === true;
        });
        $applied = array_flip(array_flip(array_column($applied, 'option')));
        $unused = array_diff(array_keys($this->classConfig['product_name_option_fixes']), $applied);
        $optionReport['unused_mappings'] = $unused;

        foreach ($optionMapping as $key => $record) {
            $entry = ['option' => $record['option'], 'before' => $record['before'], 'after ' => $record['after']];
            if ($record['mapped']) {
                $optionReport['mapped'][$key] = $entry;
                continue;
            }

            if ($record['fixAvailable']) {
                if ($record['fixApplied']) {
                    $optionReport['mapped via fix'][$key] = $entry;
                } else {
                    $optionReport['fix not applicable'][$key] = $entry;
                }
                continue;
            }
            $optionReport['no fix avalable'][$key] = $entry;
        }
        return $optionReport;
    }

    protected function getReplacementReport()
    {
        $namePrepare = $this->getReplacementTrace('name_prepare', 'preg_replace', 'imported');
        $nameReplace = $this->getReplacementTrace('product_name_replacements', 'preg_replace', 'brand_prepended');
        $nameCleanup = $this->getReplacementTrace('name_cleanup', 'preg_replace', 'product_separator');
        return [
            'pmReplacementNamePrepare' => $namePrepare ?? [],
            'pmReplacementNameReplace' => $nameReplace ?? [],
            'pmReplacementNameCleanup' => $nameCleanup ?? [],
        ];
    }

    /**
     * @param string $idx
     * @param string $replacer
     * @param string $nameIdx
     * @return array
     */
    protected function getReplacementTrace(string $idx, string $replacer, string $nameIdx): array
    {
        $replacementLog = [];
        foreach ($this->report['name'] as $key => $entry) {
            $replacementLog[$key] = $entry[$nameIdx];
        }
        $mapper = new ArrayMap();
        $replacementLog = $mapper($replacementLog, [
                SuccessiveReplacer::class => [
                    'replacer'                => $replacer,
                    'replacements'            => $this->classConfig[$idx][$replacer],
                    'reportUnmatchedPatterns' => false,
                ],
            ]
        );
        return $replacementLog;
    }

}