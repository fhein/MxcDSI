<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Import;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Client\ApiClient;
use MxcDropshipInnocigs\Exception\InvalidArgumentException;
use MxcDropshipInnocigs\Models\Import\ImportArticle;
use MxcDropshipInnocigs\Models\Import\ImportGroup;
use MxcDropshipInnocigs\Models\Import\ImportImage;
use MxcDropshipInnocigs\Models\Import\ImportOption;
use MxcDropshipInnocigs\Models\Import\ImportVariant;
use Shopware\Components\Model\ModelManager;
use Zend\Config\Config;

class ImportClient extends ImportBase
{
    /**
     * @var ModelManager $modelManager
     */
    protected $modelManager;

    /**
     * ImportBase constructor.
     *
     * @param ModelManager $modelManager
     * @param ApiClient $apiClient
     * @param Config $config
     * @param LoggerInterface $log
     */
    public function __construct(
        ModelManager $modelManager,
        ApiClient $apiClient,
        Config $config,
        LoggerInterface $log
    ) {
        parent::__construct($apiClient, $config, $log);
        $this->modelManager = $modelManager;
    }

    public function import()
    {
        parent::import();
        /** @noinspection PhpUndefinedFieldInspection */
        $limit = $this->config->numberOfArticles ?? -1;
        $this->createGroups();
        $this->createArticles($limit);
        $this->modelManager->flush();
    }

    protected function createArticles(int $limit = -1)
    {
        $i = 0;
        foreach ($this->import as $number => $data) {
            $article = new ImportArticle();
            $this->createVariants($article, $data);
            $article->setNumber($number);
            // this cascades persisting the variants also
            $this->modelManager->persist($article);
            $i++;
            if ($limit !== -1 && $i === $limit) {
                break;
            }
        }
    }

    protected function createVariants(ImportArticle $article, array $variants)
    {
        foreach ($variants as $number => $data) {
            $variant = new ImportVariant();
            $this->setVariant($variant, $number, $data);
            $article->addVariant($variant);
        }
    }

    /**
     * @param ImportVariant $variant
     * @param $data
     * @param $number
     */
    protected function setVariant(ImportVariant $variant, $number, $data): void
    {
        $variant->setCategory($this->getParamString($data['CATEGORY']));
        $variant->setNumber($number);
        $variant->setEan($this->getParamString($data['EAN']));
        $variant->setName($this->getParamString($data['NAME']));
        $variant->setPurchasePrice($this->getParamString($data['PRODUCTS_PRICE']));
        $variant->setRetailPrice($this->getParamString($data['PRODUCTS_PRICE_RECOMMENDED']));
        $variant->setManufacturer($this->getParamString($data['MANUFACTURER']));
        $imageUrl = $this->getParamString($data['PRODUCTS_IMAGE']);
        if ($imageUrl !== '') {
            $variant->setImage($this->getImage($data['PRODUCTS_IMAGE']));
        }
        foreach($data['PRODUCTS_IMAGE_ADDITIONAL']['IMAGE'] as $imageUrl) {
            $image = $this->getImage($imageUrl);
            $variant->addAdditionalImage($image);
        }
        foreach ($data['PRODUCTS_ATTRIBUTES'] as $group => $option) {
            $variant->addOption($this->items['groups'][$group][$option]);
        }
    }

    public function getImage($url) {
        $image = $this->items['images'][$url];
        if ($image instanceof ImportImage) {
            return $image;
        }
        if ($image === true) {
            $image = new ImportImage();
            $image->setUrl($url);
            return $image;
        }
        throw new InvalidArgumentException(sprintf(
            'Requested Image %s not registered.',
            $url
        ));
    }

    protected function createGroups()
    {
        foreach ($this->items['groups'] as $groupName => $options) {
            $group = new ImportGroup();
            $group->setName($groupName);
            $this->createOptions($group, array_keys($options));
            // this cascades persisting the options also
            $this->modelManager->persist($group);
        }
    }

    protected function createOptions(ImportGroup $group, $options)
    {
        foreach ($options as $optionName) {
            $option = new ImportOption();
            $option->setName($optionName);
            $group->addOption($option);
            $this->items['groups'][$group->getName()][$optionName] = $option;
        }
    }
}
