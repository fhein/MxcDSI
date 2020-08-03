<?php

namespace MxcDropshipInnocigs\Dropship\Innocigs;

use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Import\ApiClient;
use MxcDropshipInnocigs\Models\Dropship\Innocigs\Settings;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use MxcDropshipInnocigs\Toolbox\Shopware\ArticleTool;
use Shopware\Models\Article\Detail;

class Registration implements ModelManagerAwareInterface, LoggerAwareInterface
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    const NO_ERROR = 0;
    const ERROR_DUPLICATE_REGISTRATION = 1;
    const ERROR_PRODUCT_UNKNOWN        = 2;
    const ERROR_INVALID_ARGUMENT       = 3;

    private $dropShipAttribute = 'mxc_dsi_innocigs';

    public function register(Detail $detail, string $productNumber, bool $active, bool $preferOwnStock)
    {
        $detailId = $detail->getId();

        if (empty($productNumber)) {
            return [self::ERROR_INVALID_ARGUMENT, null];
        }

        // reject registration if another detail is already registered for the given product number
        if ($this->isDuplicateRegistration($productNumber, $detailId)) {
            return [ self::ERROR_DUPLICATE_REGISTRATION, null];

        }

        // reject registration if InnoCigs does not provide information about the given product number
        $services = MxcDropshipInnocigs::getServices();
        /** @var ApiClient $client */
        $client = $services->get(ApiClient::class);
        $info = $client->getItemInfo($productNumber);
        if (empty($info)) {
            return [self::ERROR_PRODUCT_UNKNOWN, null];
        }
        $info = $info[$productNumber];

        $instock = $client->getStockInfo($productNumber);
        $settingsId = ArticleTool::getDetailAttribute($detail, $this->dropShipAttribute);
        if ($settingsId === null) {
            $settings = new Settings();

        } else {
            $settings = $this->modelManager->getRepository(Settings::class)->find($settingsId);
            if ($settings === null) {
                $settings = new Settings();
            }
        }

        $purchasePrice = floatval(str_replace(',','.', $info['purchasePrice']));
        $uvp = floatval(str_replace(',','.', $info['recommendedRetailPrice']));

        $settings->setDetailId($detailId);
        $settings->setProductNumber($info['model']);
        $settings->setProductName($info['name']);
        $settings->setPurchasePrice($purchasePrice);
        $settings->setRecommendedRetailPrice($uvp);
        $settings->setInStock(intval($instock));
        $settings->setActive($active);
        $settings->setPreferOwnStock($preferOwnStock);

        $this->modelManager->persist($settings);
        $this->modelManager->flush();
        ArticleTool::setDetailAttribute($detail, 'mxc_dsi_innocigs', $settings->getId());
        return [self::NO_ERROR, $settings];
    }

    public function getSettings(Detail $detail)
    {
        $settingsId = ArticleTool::getDetailAttribute($detail, $this->dropShipAttribute);
        $settings = null;
        if ($settingsId !== null) {
            $settings = $this->modelManager->getRepository(Settings::class)->find($settingsId);
        }
        return $settings;
    }

    public function unregister(Detail $detail)
    {
        $settingsId = ArticleTool::getDetailAttribute($detail, $this->dropShipAttribute);
        $settings = null;
        if ($settingsId !== null) {
            $settings = $this->modelManager->getRepository(Settings::class)->find($settingsId);
            $this->modelManager->remove($settings);
            $this->modelManager->flush();
            ArticleTool::setDetailAttribute($detail, $this->dropShipAttribute, null);
        }
        return $settings;
    }

    protected function isDuplicateRegistration(string $productNumber, int $detailId)
    {
        $settings = $this->modelManager->getRepository(Settings::class)->findBy(['productNumber' => $productNumber]);
        if (empty($settings)) return false;
        /** @var Settings $setting */
        foreach ($settings as $setting) {
            if ($setting->getDetailId() !== $detailId) return true;
        }
        return false;
    }
}