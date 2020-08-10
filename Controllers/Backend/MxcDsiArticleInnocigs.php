<?php

use MxcDropshipInnocigs\Services\ArticleRegistry;
use MxcDropshipInnocigs\Models\ArticleAttributes;
use MxcDropshipIntegrator\MxcDropshipIntegrator;
use Shopware\Components\CSRFWhitelistAware;
use Shopware\Models\Article\Detail;
use MxcDropshipIntegrator\Dropship\SupplierRegistry;
use MxcDropshipIntegrator\Dropship\ArticleRegistryInterface;

class Shopware_Controllers_Backend_MxcDsiArticleInnocigs extends Shopware_Controllers_Backend_ExtJs implements CSRFWhitelistAware
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

    private $services;

    public function registerAction()
    {
        try {
            $services = $this->getServices();
            $log = $services->get('logger');

            /** @var ArticleRegistry $registry */
            $registry = $services->get(ArticleRegistry::class);

            $request = $this->Request();
            $active = $request->getParam('active', false) === "1" ;
            $preferOwnStock = $request->getParam('preferOwnStock', false) === "1";
            $productNumber = trim($request->getParam('productNumber', null));
            $detailId = $request->getParam('detailId', null);

            [$result, $settings] = $registry->register($detailId, $productNumber, $active, $preferOwnStock);

            switch ($result) {
                case ArticleRegistry::NO_ERROR:
                    $this->View()->assign(['success' => true, 'data' => $settings]);
                    break;

                case ArticleRegistry::ERROR_PRODUCT_UNKNOWN:
                    $message = 'Unbekanntes Produkt: ' . $productNumber . '.';
                    $this->view()->assign(['success' => false, 'info' => [ 'title' => 'Fehler', 'message' => $message]]);
                    break;

                case ArticleRegistry::ERROR_DUPLICATE_REGISTRATION:
                    $message = 'Doppelte Registrierung für ' . $productNumber . '.';
                    $this->view()->assign(['success' => false, 'info' => [ 'title' => 'Fehler', 'message' => $message]]);
                    break;

                case ArticleRegistry::ERROR_INVALID_ARGUMENT:
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
            $detailId = $this->Request()->getParam('detailId', null);

            $services = MxcDropshipIntegrator::getServices();
            $log = $services->get('logger');
            /** @var ArticleRegistry $registry */
            $registry = $services->get(ArticleRegistry::class);

            $settings = $registry->getSettings($detailId);

            $this->View()->assign(['success' => true, 'data' => $settings]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }
    
    public function unregisterAction()
    {
            $request = $this->Request();
            $detailId = $request->getParam('detailId', null);
            /** @var Detail $detail */

            $services = $this->getServices();
            /** @var ArticleRegistry $registration */
            $registry = $services->get(ArticleRegistry::class);
            $registry->unregister($detailId);
            $message = 'Dropship registration deleted.';
            $this->View()->assign(['success' => true, 'info' => ['title' => 'Erfolg', 'message' => $message]]);
    }

    protected function handleException(Throwable $e, bool $rethrow = false) {
        MxcDropshipIntegrator::getServices()->get('logger')->except($e, true, $rethrow);
        $this->view->assign([ 'success' => false, 'info' => ['title' => 'Exception', 'message' => $e->getMessage()]]);
    }

    protected function getServices() {
        return $this->services ?? $this->services = MxcDropshipIntegrator::getServices();
    }

}