<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Model;
use const MxcDropshipInnocigs\MXC_DELIMITER_L1;
use const MxcDropshipInnocigs\MXC_DELIMITER_L2;

class ArticleNameMapper extends BaseImportMapper implements ArticleMapperInterface
{
    protected $report;

    public function map(Model $model, Article $article)
    {
        $modelName = $model->getName();
        $this->report['name'][$modelName]['model'] = $model->getModel();
        $trace['imported'] = $model->getName();
        $name = $this->replace($modelName, 'name_prepare');
        $trace['name_prepared'] = $name;
        $name = $this->removeOptionsFromModelName($name, $model);
        $trace['options_removed'] = $name;

        // general name mapping applied first
        $result = $this->config['article_names'][$model->getName()];
        if ($result !== null) {
            $trace['directly_mapped'] = $result;
            $article->setName($result);
            return;
        }

        // rule based name mapping applied next
        $name = $this->applySupplierAndBrandDeocoration($name, $article);
        $trace['brand_prepended'] = $name;

        $name = $this->replace($name, 'article_name_replacements');
        $trace['after_name_replacements'] = $name;

        $supplier = $article->getSupplier();
        $supplier = $supplier === 'Smoktech' ? 'SMOK' : $supplier;

        $search[] = '~(' . $article->getBrand() . ') ([^\-])~';
        $search[] = '~(' . $supplier . ') ([^\-])~';
        $name = preg_replace($search, '$1 - $2', $name);
        $trace['supplier_separator'] = $name;
        $search = $this->config['product_names'][$article->getBrand()];
        if (null !== $search) {
            $name = preg_replace($search, '$1 -', $name);
            $trace['product_separator'] = $name;
        }

        $name = $this->replace($name, 'name_cleanup');
        $name = preg_replace('~\s+~', ' ', $name);

        $trace['mapped'] = $name;
        $this->report['name'][$trace['imported']] = $trace;
        $article->setName($name);
    }

    /**
     * InnoCigs model records (which represent variants) contain a product name.
     * The product name of a model contains parts reflecting the options of the variant.
     *
     * Here we remove the option parts from the model's product name.
     *
     * Creating a new article we use the product name of the first model belonging
     * to that article and ignore the product names of the other models belonging
     * to that article.
     *
     * Anyway, for update reasons all models with the same master id should map to
     * the same product name after name mapping. This can be checked via the GUI
     * 'Check name mapping consistency'.
     *
     * @param string $name
     * @param Model $model
     * @return string
     */
    protected function removeOptionsFromModelName(string $name, Model $model)
    {
        $options = explode(MXC_DELIMITER_L2, $model->getOptions());

        foreach ($options as $option) {
            $option = explode(MXC_DELIMITER_L1, $option)[1];
            $number = $model->getModel();

            if (strpos($name, $option) !== false) {
                // article name contains option name
                $before = $name;
                $replacement = $this->config['option_replacements'][$option] ?? '';
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
     * addressed with the article_name_option_fixes configuration setting,
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
        // to the string added to the article name, so we have to check
        // that also.
        $o = $this->config['article_name_option_fixes'][$option] ?? null;
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

    public function applySupplierAndBrandDeocoration(string $name, Article $article)
    {
        $brand = $article->getBrand();
        if (!$brand) {
            return $name;
        }
        $supplier = $article->getSupplier();
        if ($supplier === 'Innocigs') {
            $supplier = 'InnoCigs';
        }
        $isInnocigsBrand = in_array($brand, $this->config['innocigs_brands']);
        $isInnocigsSupplier = ($supplier === 'InnoCigs');

        if ($isInnocigsBrand && $isInnocigsSupplier) {
            // There are some articles from supplier InnoCigs which are not branded
            if (strpos($name, $brand) !== 0 && !in_array($name, $this->config['articles_without_brand'])) {
                $name = $brand . ' - ' . $name;
            }
            return $name;
        }

        $append = $isInnocigsBrand ? ' - by ' . $brand : '';
        if ($supplier === 'Smoktech') {
            $supplier = 'SMOK';
        }

        if (!$isInnocigsSupplier) {
            $name = str_replace($brand, $supplier, $name) . $append;
        }
        return $name;
    }

    public function replace(string $topic, string $what)
    {
        $config = $this->config[$what];
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
}