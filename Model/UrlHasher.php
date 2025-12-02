<?php

declare(strict_types=1);

namespace SchrammelCodes\EpcQrCode\Model;

use Magento\Framework\FlagManager;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

class UrlHasher
{
    public const EPC_QR_CODE_V2_1_UPGRADE_TIMESTAMP_FLAG = 'epc_qr_code_v2_1_upgrade_timestamp';

    public function __construct(
        private readonly FlagManager $flagManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Creates a unique hash for the order
     *
     * @param OrderInterface $order
     * @return string
     */
    public function createHashForOrder(OrderInterface $order): string
    {
        $hashData = implode('', [
            $order->getIncrementId(),
            $order->getCustomerEmail(),
            $order->getCreatedAt(),
        ]);

        return hash('sha256', $hashData);
    }

    /**
     * Check if the given order expects hash verification or not
     *
     * @param OrderInterface $order
     * @return bool
     */
    public function shouldVerifyHashForOrder(OrderInterface $order): bool
    {
        $moduleUpgradeAt = $this->flagManager->getFlagData(self::EPC_QR_CODE_V2_1_UPGRADE_TIMESTAMP_FLAG);
        $orderCreationTimestamp = strtotime($order->getCreatedAt());

        // In case the migration timestamp is missing or timestamp parsing for order creation date fails, bypass the
        // hash validation with according WARNING log.
        if ($moduleUpgradeAt === null || $orderCreationTimestamp === false) {
            $this->logger->warning(
                '[EPC QR Code] Hash validation omitted.',
                [
                    'orderId' => $order->getId(),
                    'reason' => $moduleUpgradeAt === null ?
                        'Module upgrade timestamp missing' :
                        'Order creation datetime to timestamp conversion error'
                ],
            );

            return false;
        }

        return $orderCreationTimestamp > $moduleUpgradeAt;
    }
}
