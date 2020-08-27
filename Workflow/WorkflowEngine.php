<?php

namespace MxcDropshipIntegrator\Workflow;

use MxcCommons\Plugin\Service\ClassConfigAwareTrait;
use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropshipIntegrator\MxcDropshipIntegrator;
use Shopware\Models\Order\Status;

class WorkflowEngine implements AugmentedObject
{
    use DatabaseAwareTrait;
    use ClassConfigAwareTrait;

    protected $services;

    public function init()
    {
        $this->services = MxcDropshipIntegrator::getServices();
    }

    public function run() {
        $orders = $this->getOpenOrders();
        $results = [];
        foreach ($orders as $order)
        {
            $order['id'] = $order['orderID'];
            $type = 'order_status';
            $results[$type] = $this->processOrder($order, $type, $order['status']);
            $type = 'payment_status';
            $results[$type] = $this->processOrder($order, 'payment_status', $order['cleared']);
        }
        return $results;
    }

    public function processOrder(array $order, string $type, int $status)
    {
        $statusActions = @$this->classConfig['workflow']['order'][$type][$status] ?? [];
        $results = [];
        foreach ($statusActions as $actionClass)
        {
            $action = $this->services->get($actionClass);
            $result = $action->run($order);
            $results = [
                'name' => $actionClass,
                'result' => $result,
            ];
            if (! $result) break;
        }
        return $results;
    }


    //    'offen' =>
    //          if bezahlt {
    //              $orderType = 0;
    //              $error = 0;
    //
    //            if Lager-Artikel in der Bestellung {
    //                  $orderType |= ORDER_VAPEE;
    //                  $result = Verfügbarkeit der Produkte prüfen
    //                  if ($result == ok) {
    //                      Packzettel erzeugen
    //                      Option: DHL Versandlabel erzeugen
    //                  } else {
    //                      $error |= ERROR_OUTOFSTOCK_VAPEE
    //                      $problemListeLager = Produkte, die Probleme machen
    //                  }
    //              }
    //              if InnoCigs Artikel in der Bestellung {
    //                  $orderType |= ORDER_INNOCIGS;
    //                  Verfügbarkeit der Produkte prüfen
    //                  wenn verfügbar {
    //                      $result = Innocigs Dropship Auftrag schicken
    //                      wenn $result == not ok {
    //                          $error |= ERROR_OUTOFSTOCK_INNOCIGS
    //                          $problemListeInnocigs = Produkte, die Probleme machen
    //                      }
    //                  },
    //              }
    //              // kein Fehler aufgetreten
    //              if ($error == 0) {
    //                  Folgestatus = 'in Bearbeitung'
    //                  $mail an Kunden, 'In Bearbeitung'
    //                  // InnoCigs Bestellung
    //                  if ($orderType == 1) {
    //                      $adminMail = neue Bestellung, bereits bezahlt, erfolgreich weitergeleitet an InnoCigs
    //                  elseif ($
    //
    //              }
    //
    //
    //


    // retrieve all order with order status !=
    protected function getOpenOrders() {
        return $this->db->fetchAll('
            SELECT * FROM s_order o 
            LEFT JOIN s_order_attributes oa ON oa.orderID = o.id
            WHERE o.status != ?',
            [ Status::ORDER_STATE_COMPLETED ]
        );
    }
}