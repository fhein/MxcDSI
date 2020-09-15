<?php


namespace MxcDropshipIntegrator\Mapping\Import;

use MxcDropshipIntegrator\Models\Model;
use MxcDropshipIntegrator\Models\Product;
use MxcCommons\Toolbox\Report\ArrayReport;

class ProductSeoMapper extends BaseImportMapper implements ProductMapperInterface
{
    /** @var array */
    protected $report = [];
    protected $config;

    // characters set by Shopware (' | vapee.de')
    protected $titleCharBias = 11;
    protected $titleCharMax = 55;

    protected $slug;
    protected $productSeriesPatterns;

    protected $descriptionCharBias = 0;
    protected $descriptionCharMax = 155;

    protected $descriptionSnippets = [
        '► Besuchen Sie vapee.de!',
        '✓ Rascher Versand ',
        '✓ Faire Preise ',
        '✓ Große Auswahl ',
    ];

    protected $titleSnippets = [
        ' kaufen',
        ' online kaufen',
        ' günstig kaufen',
        ' günstig online kaufen',
        ' bequem online kaufen',
        'bequem günstig online',
        'bequem und günstig online kaufen',
    ];

    protected $descriptionMaxLength;

    protected $searchPatterns = [
        '##name##',
        '##title##',
        '##supplier##',
        '##brand##',
        '##common_name##'
    ];

    public function __construct()
    {
        $this->descriptionMaxLength = $this->descriptionCharMax - $this->descriptionCharBias;
        $commonNameIndex = include __DIR__ . '/../../Config/CommonNameMapper.config.php';
        $commonNameIndex = $commonNameIndex['common_name_index'];
        $lastLength = 0;
        $snippets = [];
        foreach ($this->descriptionSnippets as $snippet) {
            $newLength = strlen($snippet) + $lastLength;
            $snippets[$snippet] = $newLength;
            $lastLength = $newLength;
        }
        $this->descriptionSnippets = $snippets;

        $snippets = [];
        foreach ($this->titleSnippets as $snippet) {
            $snippets[$snippet] = strlen($snippet);
        }
        asort($snippets);
        $this->titleSnippets = $snippets;

        $series = [];
        foreach ($commonNameIndex as $supplier => $entries) {
            $entries = array_keys($entries);
            foreach ($entries as $entry) {
                $search = '~(' . $supplier . ' -) ' . $entry . ' -~';
                $series[$search] = '$1';
            }
        }
        $this->productSeriesPatterns = $series;
        $this->slug = Shopware()->Container()->get('shopware.slug');
    }

    public function map(Model $model, Product $product, bool $remap = false)
    {
        $name = $product->getName();
        $brand = $product->getBrand();
        $supplier = $product->getSupplier();
        $commonName = $product->getCommonName();
        if ($supplier === 'InnoCigs') $supplier = $brand;

        $map = $this->getTypeMap();

        // compute and set seo title

        // apply replacements applicable to all products
        $defaultPatterns = $this->classConfig['patterns'][$map['default']]['title'];
        $search = array_keys($defaultPatterns);
        $replace = array_values($defaultPatterns);
        $title = trim(preg_replace($search, $replace, $name));

        // remove product series
        $search = array_keys($this->productSeriesPatterns);
        $replace = array_values($this->productSeriesPatterns);
        $title = trim(preg_replace($search, $replace, $title));


        // apply replacements applicable to products of $type
        $type = $product->getType();
        $patterns = $this->classConfig['patterns'][$map[$type]]['title'] ?? null;
        if ($patterns) {
            $search = array_keys($patterns);
            $replace = array_values($patterns);
            $title = trim(preg_replace($search, $replace, $title));
        }

        $titleLength = strlen($title);
        $titleMin = $this->classConfig['sizes']['titleMin'];

        // make short titles longer
        $titleSnippet = '';
        if ($titleLength < $titleMin) {
            $titleMax = $this->classConfig['sizes']['titleMax'];
            $snippets = $this->titleSnippets;
            foreach ($snippets as $snippet => $length) {
                if ($titleLength + $length >= $titleMax) break;
                $titleSnippet = $snippet;
            }
        }

        $seoTitle = $title . $titleSnippet;
        $this->report[$name]['title'] = $seoTitle;
        $product->setSeoTitle($seoTitle);

        // compute and set seo description

        $description = $this->classConfig['patterns'][$map['default']]['description'];
        $replace = [$name, $title, $supplier, $brand, $commonName];
        $description = str_replace($this->searchPatterns, $replace, $description);

        $descriptionLength = strlen($description);
        $descriptionSnippet = '';
        foreach ($this->descriptionSnippets as $snippet => $snippetLength) {
            if ($descriptionLength + $snippetLength > $this->descriptionMaxLength) break;
            $descriptionSnippet = $snippet . $descriptionSnippet;
        }

        $seoDescription = $description . $descriptionSnippet;
        $this->report[$name]['description'] = $seoDescription;
        $product->setSeoDescription($seoDescription);

        // compute and set seo url

        $url = str_replace('/', '-', $title);
        $url = str_replace('Dr.', 'Dr', $url);
        $url = preg_replace('~([^ ])\'([^ ])~', '$1$2', $url);
        $url = preg_replace('~(\d)\.(\d{3} ((mAh)|(ml)))~', '$1$2', $url);

        $url = mb_strtolower($this->slug->slugify($url));

        $url = str_replace('-akku-', '-', $url);
        $url = preg_replace('~-e-zigarette$~', '', $url);
        $url = preg_replace('~-e-pfeife$~', '', $url);
        $url = preg_replace('~-e-liquid$~', '', $url);
        $url = preg_replace('~-liquid$~', '', $url);
        $url = preg_replace('~-aroma$~', '', $url);
        $url = preg_replace('~-verdampfer$~', '', $url);
        $url = preg_replace('~-akkutraeger-~', '-', $url);
        $url = preg_replace('~-squonker-box-~', '-', $url);
        $url = preg_replace('~-leerflasche-~', '-', $url);
        $url = preg_replace('~-shake-vape$~', '', $url);
        $url = preg_replace('~(suenden-)\d\.-~', '$1', $url);
        $url = preg_replace('~(cartridge-)mit-head-~', '$1', $url);
        $url = preg_replace('~-e-hookah-set$~', '', $url);
        $url = preg_replace('~(flavour)-(trade)~', '$1$2', $url);
        $url = preg_replace('~-fuer-chubby-gorilla$~', '', $url);
        $url = preg_replace('~-inkl.-head~', '', $url);

        $type = $product->getType();
        if (in_array($type, ['AROMA', 'LIQUID', 'NICSALT_LIQUID','SHAKE_VAPE'])) {
            $content = $product->getContent();
            if ($content > 0) {
                $url .= '-' . $product->getContent() . '-ml';
            }
        }
        $baseUrl = $this->classConfig['base_urls'][$type];
        $url = $baseUrl . $url;

        $this->report[$name]['url'] = $url;

        $product->setSeoUrl($url);

        // compute and set seo keywords
        $keywords = implode(',',array_unique([$commonName, $supplier, $brand]));
        $product->setSeoKeywords($keywords);
    }

    protected function getTypeMap() {
        $typeMap = [];
        foreach ($this->classConfig['patterns'] as $idx => $record) {
            foreach ($record['types'] as $type) {
                $typeMap[$type] = $idx;
            }
        }
        return $typeMap;
    }

    public function report()
    {
        $shortTitles = $longTitles = $allTitles = [];
        $shortUrls = $longUrls = $allUrls = [];
        $shortDescriptions = $longDescriptions = $allDescriptions = [];

        foreach ($this->report as $name => $report) {
            $this->rankItem(
                $name,
                $report['url'],
                $this->classConfig['sizes']['urlMin'],
                $this->classConfig['sizes']['urlMax'],
                $shortUrls,
                $longUrls,
                $allUrls
            );

            $this->rankItem(
                $name,
                $report['title'],
                $this->classConfig['sizes']['titleMin'],
                $this->classConfig['sizes']['titleMax'],
                $shortTitles,
                $longTitles,
                $allTitles
            );

            $this->rankItem(
                $name,
                $report['description'],
                $this->classConfig['sizes']['descriptionMin'],
                $this->classConfig['sizes']['descriptionMax'],
                $shortDescriptions,
                $longDescriptions,
                $allDescriptions
            );
        }

        $report = new ArrayReport();

        ksort($shortUrls);
        ksort($longUrls);
        ksort($allUrls);
        ksort($shortTitles);
        ksort($longTitles);
        ksort($allTitles);
        ksort($shortDescriptions);
        ksort($longDescriptions);
        ksort($allDescriptions);

        $doubleUrls = $this->getDoubles($allUrls);
        $doubleTitles = $this->getDoubles($allTitles);
        $doubleDescriptions = $this->getDoubles($allDescriptions);

        $report([
            'pmSeoProductShortUrls' => $shortUrls,
            'pmSeoProductLongUrls' => $longUrls,
            'pmSeoProductAllUrls' => $allUrls,
            'pmSeoProductDoubledUrls' => $doubleUrls,
            'pmSeoProductShortTitles' => $shortTitles,
            'pmSeoProductLongTitles' => $longTitles,
            'pmSeoProductAllTitles' => $allTitles,
            'pmSeoProductDoubledTitles' => $doubleTitles,
            'pmSeoProductShortDescriptions' => $shortDescriptions,
            'pmSeoProductLongDescriptions' => $longDescriptions,
            'pmSeoProductAllDescriptions' => $allDescriptions,
            'pmSeoProductDoubledDescriptions' => $doubleDescriptions,

        ]);
        
        if (! empty($shortUrls)) {
            $this->log->debug('Product SEO: # short urls: ' . count($shortUrls));            
        }
        if (! empty($longUrls)) {
            $this->log->debug('Product SEO: # long urls: ' . count($longUrls));
        }
        if (! empty($doubleUrls)) {
            $this->log->debug('Product SEO: # duplicate urls: ' . count($doubleUrls));
        }
        if (! empty($shortTitles)) {
            $this->log->debug('Product SEO: # short titles: ' . count($shortTitles));
        }
        if (! empty($longTitles)) {
            $this->log->debug('Product SEO: # long titles: ' . count($longTitles));
        }
        if (! empty($doubleTitles)) {
            $this->log->debug('Product SEO: # duplicate titles: ' . count($doubleTitles));
        }
        if (! empty($shortDescriptions)) {
            $this->log->debug('Product SEO: # short descriptions: ' . count($shortDescriptions));
        }
        if (! empty($longDescriptions)) {
            $this->log->debug('Product SEO: # long descriptions: ' . count($longDescriptions));
        }
        if (! empty($doubleDescriptions)) {
            $this->log->debug('Product SEO: # duplicate descriptions: ' . count($doubleDescriptions));
        }

    }

    protected function rankItem(string $name, string $item, int $min, int $max, array &$short, array &$long, array &$all)
    {
        $len = strlen($item);
        if ($len < $min) {
            $short[$item] = $name;
            $all[$item] = 'Too short.';
        } elseif ($len > $max) {
            $long[$item] = $name;
            $all[$item] = 'Too long.';
        } else {
            $all[$item] = 'Ok.';
        }
    }

    protected function getDoubles(array $array) {
        $array = array_keys($array);
        $unique = array_unique($array);
        $doubles = [];
        if (count($array) === count($unique)) return $doubles;
        $found = [];
        foreach ($array as $value) {
            if ($found[$value] !== null) {
                $doubles[$value] = true;
            }
            $found[$value] = true;
        }
        return $doubles;
    }
}