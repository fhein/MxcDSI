<?php

namespace MxcDropshipIntegrator\Workflow;

use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use Shopware\Models\Order\Order;
use Shopware_Components_Document;
use DateTime;

class DocumentRenderer implements AugmentedObject
{
    use ModelManagerAwareTrait;

    private $documentTypes = [
        'invoice' => 1,
        'delivery_note' => 2,
        'credit' => 3,
        'cancellation' => 4
    ];

    public function createDocument(Order $order, string $type, DateTime $date = null)
    {
        if ($date === null) {
            $date = date('d.m.Y');
        }

        $document = Shopware_Components_Document::initDocument($order->getId(), $this->documentTypes[$type],
            [
                'netto' => $order->getTaxFree(),
                'date' => $date,
                'shippingCostsAsPosition' => true,
                '_renderer' => 'pdf'
            ]);
        $document->render();
    }

    public function getDocumentPath(Order $order, string $type)
    {
        $sql = "SELECT hash FROM s_order_documents WHERE orderID=? AND type=? ORDER BY date DESC LIMIT 1";
        $hash = Shopware()->Db()->fetchOne($sql, array($order->getId(), $this->documentTypes[$type]));
        return Shopware()->DocPath() . "files/documents" . "/" . $hash . ".pdf";
    }
}