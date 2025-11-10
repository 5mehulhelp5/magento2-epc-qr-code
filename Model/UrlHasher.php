<?php

declare(strict_types=1);

namespace SchrammelCodes\EpcQrCode\Model;

use Magento\Sales\Api\Data\OrderInterface;

class UrlHasher
{
    public function createHashForOrder(OrderInterface $order): string
    {
        $hashData = implode('', [
            $order->getIncrementId(),
            $order->getCustomerEmail(),
            $order->getCreatedAt(),
        ]);

        return hash('sha256', $hashData);
    }
}
