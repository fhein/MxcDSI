<?php

use Shopware\Models\Order\Status;

return [
    'workflow' => [
        'order' => [
            'DROPSHIP_STATUS' => [
                Status::ORDER_STATE_CANCELLED => [],
                Status::ORDER_STATE_OPEN => [],
                Status::ORDER_STATE_IN_PROCESS => [],
                Status::ORDER_STATE_COMPLETED => [],
                Status::ORDER_STATE_PARTIALLY_COMPLETED => [],
                Status::ORDER_STATE_CANCELLED_REJECTED => [],
                Status::ORDER_STATE_READY_FOR_DELIVERY => [],
                Status::ORDER_STATE_PARTIALLY_DELIVERED => [],
                Status::ORDER_STATE_COMPLETELY_DELIVERED => [],
                Status::ORDER_STATE_CLARIFICATION_REQUIRED => [],
            ],
            'payment_status' => [
                Status::PAYMENT_STATE_PARTIALLY_INVOICED => [],
                Status::PAYMENT_STATE_COMPLETELY_INVOICED => [],
                Status::PAYMENT_STATE_PARTIALLY_PAID => [],
                Status::PAYMENT_STATE_COMPLETELY_PAID => [],
                Status::PAYMENT_STATE_1ST_REMINDER => [],
                Status::PAYMENT_STATE_2ND_REMINDER => [],
                Status::PAYMENT_STATE_3RD_REMINDER => [],
                Status::PAYMENT_STATE_ENCASHMENT => [],
                Status::PAYMENT_STATE_OPEN => [],
                Status::PAYMENT_STATE_RESERVED => [],
                Status::PAYMENT_STATE_DELAYED => [],
                Status::PAYMENT_STATE_RE_CREDITING => [],
                Status::PAYMENT_STATE_REVIEW_NECESSARY => [],
                Status::PAYMENT_STATE_NO_CREDIT_APPROVED => [],
                Status::PAYMENT_STATE_THE_CREDIT_HAS_BEEN_PRELIMINARILY_ACCEPTED => [],
                Status::PAYMENT_STATE_THE_CREDIT_HAS_BEEN_ACCEPTED => [],
                Status::PAYMENT_STATE_THE_PAYMENT_HAS_BEEN_ORDERED => [],
                Status::PAYMENT_STATE_A_TIME_EXTENSION_HAS_BEEN_REGISTERED => [],
                Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED => [],
            ],
        ],
    ]
];
