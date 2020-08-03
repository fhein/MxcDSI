<?php

use MxcDropshipInnocigs\Dropship\Innocigs\Registration;
use MxcDropshipInnocigs\Models\Dropship\Innocigs\Settings;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use Shopware\Models\Article\Detail;

class Shopware_Controllers_Backend_MxcDsiArticleInnocigs extends Shopware_Controllers_Backend_ExtJs implements \Shopware\Components\CSRFWhitelistAware
{
    /**
     * @return array
     */
    public function getWhitelistedCSRFActions() {
        return [
            'register',
            'unregister',
            'getSettings'
        ];
    }

    public function registerAction()
    {
        try {
            $log = MxcDropshipInnocigs::getServices()->get('logger');
            $request = $this->Request();
            $active = $request->getParam('active');
            $preferOwnStock = $request->getParam('preferOwnStock');
            $productNumber = trim($request->getParam('productNumber', null));

            /** @var Detail $detail */
            $detailId = $this->Request()->getParam('articleId', null);
            $detail = $this->getDetail($detailId);

            /** @var Registration $registration */
            $registration = MxcDropshipInnocigs::getServices()->get(Registration::class);
            /** @var Settings $settings */
            [$result, $settings] = $registration->register($detail, $productNumber, $active, $preferOwnStock);

            switch ($result) {
                case Registration::NO_ERROR:
                    $data = $this->setupResultData($settings);
                    $this->View()->assign(['success' => true, 'data' => $data]);
                    break;

                case Registration::ERROR_PRODUCT_UNKNOWN:
                    $message = 'Unbekanntes Produkt: ' . $productNumber . '.';
                    $this->view()->assign(['success' => false, 'info' => [ 'title' => 'Fehler', 'message' => $message]]);
                    break;

                case Registration::ERROR_DUPLICATE_REGISTRATION:
                    $message = 'Doppelte Registrierung für ' . $productNumber . '.';
                    $this->view()->assign(['success' => false, 'info' => [ 'title' => 'Fehler', 'message' => $message]]);
                    break;

                case Registration::ERROR_INVALID_ARGUMENT:
                    $this->View()->assign(['success' => false, 'info' => []]);
                    break;

                default:
                    $message = 'Unbekannter Fehler.';
                    $this->View()->assign(['success' => false, 'info' => ['title' => 'Fehler', 'message' => $message]]);
                    break;
            }
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function getSettingsAction()
    {
        try {
            $services = MxcDropshipInnocigs::getServices();
            $detailId = $this->Request()->getParam('articleId', null);
            /** @var Detail $detail */
            $detail = $this->getDetail($detailId);

            $log = $services->get('logger');
            /** @var Registration $registration */
            $registration = $services->get(Registration::class);
            $settings = $registration->getSettings($detail);
            $data = [];
            if ($settings instanceof Settings) {
                $data = $this->setupResultData($settings);
            }

            $this->View()->assign(['success' => true, 'data' => $data]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }
    
    public function unregisterAction()
    {
            $detailId = $this->Request()->getParam('articleId', null);
            $detail = $this->getDetail($detailId);
            /** @var Registration $registration */
            $registration = MxcDropshipInnocigs::getServices()->get(Registration::class);
            $registration->unregister($detail);
            $message = 'Dropship registration deleted.';
            $this->View()->assign(['success' => true, 'info' => ['title' => 'Erfolg', 'message' => $message]]);
    }

    protected function setupResultData(Settings $settings)
    {
        $data = [
            'mxc_dsi_ic_productnumber' => $settings->getProductNumber(),
            'mxc_dsi_ic_productname' => $settings->getProductName(),
            'mxc_dsi_ic_purchaseprice' => $settings->getPurchasePrice(),
            'mxc_dsi_ic_retailprice' => $settings->getRecommendedRetailPrice(),
            'mxc_dsi_ic_instock' => $settings->getInstock(),
            'mxc_dsi_ic_active' => $settings->isActive(),
            'mxc_dsi_ic_preferownstock' => $settings->isPreferOwnStock()
        ];
        return $data;
    }

    protected function handleException(Throwable $e, bool $rethrow = false) {
        MxcDropshipInnocigs::getServices()->get('logger')->except($e, true, $rethrow);
        $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
    }

    protected function getDetail(?int $id)
    {
        if ($id === null) return null;
        return $this->getModelManager()->getRepository(Detail::class)->find($id);
    }

}