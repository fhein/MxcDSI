<?php

namespace MxcDropshipIntegrator\Workflow;

use MxcDropshipInnocigs\Services\ApiClient;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order as ShopwareOrder;

class Order
{
    const ORDER_INNOCIGS = 1;
    const ORDER_VAPEE = 2;

    const NO_ERROR            = 0;
    const ERROR_OUTOFSTOCK    = 1;

    private $liveDropshipStockCheck = false;

    /** @var ShopwareOrder */
    private $order;

    /** @var array */
    private $positions = [];

    /** @var int */
    private $orderType = null;

    /** @var int */
    private $orderAvailability = null;

    /** @var ApiClient */
    private $apiClient;

    /** Wir sind eine Wrapper-Klasse für eine Shopware Order */
    public function __construct(ShopwareOrder $order, ApiClient $apiClient)
    {
        $this->order = $order;
        $this->apiClient = $apiClient;
    }

    public function isInnocigsOrder(bool $force = false) {
        return $this->getOrderType($force) == self::ORDER_INNOCIGS;
    }

    public function isVapeeOrder(bool $force = false) {
        return $this->getOrderType($force) == self::ORDER_VAPEE;
    }

    public function isHybridOrder(bool $force = false)
    {
        return $this->getOrderType($force) == self::ORDER_INNOCIGS | self::ORDER_VAPEE;
    }

    public function vapeeDeliveryRequired(bool $force = false) {
        return ($this->getOrderType($force) & self::ORDER_VAPEE) != 0;
    }

    // Ermittelt den Typ der Bestellung (Lager-Produkte, Dropship Produkte, oder beides)
    public function getOrderType(bool $force = false)
    {
        if ($this->orderType === null || $force) {
            $this->validate($force);
        }
        return $this->orderType;
    }

    public function isOrderAvailable(bool $force = false)
    {
        if ($this->orderAvailability === null || $force) {
            $this->validate($force);
        }
        return $this->orderAvailability;
    }

    // Ermittelt ob die Order Dropship, Vapee, oder beides ist, abgebildet in $this->orderType
    // Ermittelt, ob die Order lieferbar ist, abgebildet in $this->orderAvailability
    private function validate(bool $force = false) {
        if (empty($this->positions) || $force) {
            $this->positions = [];
            $details = $this->order->getDetails();
            $this->orderType = null;
            $this->orderAvailability = null;
            /** @var Detail $detail */
            foreach ($details as $detail) {
                $positionOrderType = $this->getPositionOrderType($detail);
                $this->orderType |= $positionOrderType;

                $positionAvailability = $this->getPositionAvailability($detail, $positionOrderType);
                $this->orderAvailability |= $positionAvailability;

                $this->positions[$positionAvailability][$positionOrderType] = $detail;
            }
        }
        return $this->positions;
    }

    /* @todo */

    private function getPositionOrderType(Detail $detail)
    {
        $articleDetail = $detail->getArticleDetail();
        // replace call with our custom attribute mxc_dsi_dropship_active
        return $articleDetail->isDropShipProduct() ? self::ORDER_INNOCIGS : self::ORDER_VAPEE;
    }

    // Ermittelt die Verfügbarkeit einer Bestellposition
    // @todo
    private function getPositionAvailability(Detail $detail, int $positionOrderType)
    {
        $articleDetail = $detail->getArticleDetail();
        switch ($positionOrderType) {
            case self::ORDER_VAPEE:
                if ($articleDetail->getInStock() < $detail->getQuantity()) {
                    return self::ERROR_OUTOFSTOCK;
                }
                return self::NO_ERROR;
            case self::ORDER_INNOCIGS:
                // @todo: Hier muss der InnoCigs Bestand geprüft werden
                // Zwei Modi: Entweder Live Abfrage oder den Wert in der Datenbank
                // Order Attribute mxc_dsi_instock @todo

                if ($this->liveDropshipStockCheck) {
                    $innocigsStock = $this->apiClient->getStockInfo($articleDetail->getNumber());
                } else {
                    $innocigsStock = $articleDetail->getDropshipInStock();
                }

                if ($innocigsStock < $detail->getQuantity()) {
                    return self::ERROR_OUTOFSTOCK;
                }
                return self::NO_ERROR;
            default:
                return self::ERROR_OUTOFSTOCK;
        }
    }
}