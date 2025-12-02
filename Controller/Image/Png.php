<?php

declare(strict_types=1);

namespace SchrammelCodes\EpcQrCode\Controller\Image;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use SchrammelCodes\EpcQrCode\Model\Config\Reader as ConfigReader;
use SchrammelCodes\EpcQrCode\Model\QrCodeRenderer;
use SchrammelCodes\EpcQrCode\Model\UrlHasher;

class Png implements HttpGetActionInterface
{
    public function __construct(
        private readonly ConfigReader $configReader,
        private readonly RequestInterface $request,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly QrCodeRenderer $qrCodeRenderer,
        private readonly RawFactory $resultFactory,
        private readonly UrlHasher $urlHasher
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(): ?ResultInterface
    {
        $result = $this->resultFactory->create();
        if ($this->configReader->isEpcQrCodeImageSrcBase64Encoded()) {
            $result->setHttpResponseCode(403);
            $result->setHeader('Content-Type', 'text/plain');
            $result->setContents('403 Forbidden');

            return $result;
        }

        $orderId = $this->request->getParam('order_id');
        try {
            $order = is_numeric($orderId) ? $this->orderRepository->get($orderId) : null;
        } catch (NoSuchEntityException) {
        }

        if (!isset($order) || !$order instanceof OrderInterface) {
            throw new NotFoundException(__('Order with ID %s not found', $orderId));
        }

        if ($this->urlHasher->shouldVerifyHashForOrder($order) &&
            $this->request->getParam('hash') !== $this->urlHasher->createHashForOrder($order)
        ) {
            throw new NotFoundException(__('Invalid hash'));
        }

        $qrCode = $this->qrCodeRenderer->getRawPngQrCode($order);

        if (!$qrCode) {
            return null;
        }

        $result->setHeader('Content-Type', 'image/png');
        $result->setContents($qrCode);

        return $result;
    }
}
