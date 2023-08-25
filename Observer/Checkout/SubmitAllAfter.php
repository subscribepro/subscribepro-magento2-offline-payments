<?php

namespace Swarming\OfflinePayments\Observer\Checkout;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Swarming\SubscribePro\Model\Quote\SubscriptionCreator;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider as GatewayConfigProvider;

class SubmitAllAfter extends \Swarming\SubscribePro\Observer\Checkout\SubmitAllAfter
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\General
     */
    protected $generalConfig;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Swarming\SubscribePro\Model\Quote\SubscriptionCreator
     */
    protected $subscriptionCreator;

    /**
     * @var \Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor
     */
    protected $cartItemOptionProcessor;

    /**
     * @var \Swarming\OfflinePayments\Helper\Config
     */
    protected $offlinePaymentsConfig;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Swarming\SubscribePro\Model\Config\General $generalConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Swarming\SubscribePro\Model\Quote\SubscriptionCreator $subscriptionCreator
     * @param \Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor $cartItemOptionProcessor
     * @param \Swarming\OfflinePayments\Helper\Config $offlinePaymentsConfig
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $generalConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Swarming\SubscribePro\Model\Quote\SubscriptionCreator $subscriptionCreator,
        \Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor $cartItemOptionProcessor,
        \Swarming\OfflinePayments\Helper\Config $offlinePaymentsConfig,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->generalConfig = $generalConfig;
        $this->checkoutSession = $checkoutSession;
        $this->subscriptionCreator = $subscriptionCreator;
        $this->cartItemOptionProcessor = $cartItemOptionProcessor;
        $this->offlinePaymentsConfig = $offlinePaymentsConfig;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getData('quote');
        $this->addProductOptionsToQuoteItems($quote);

        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
        $order = $observer->getData('order');
        $paymentMethodCode = $order->getPayment()->getMethod();

        $this->logger->info('Order payment method: ' . $paymentMethodCode);
        $this->logger->info('Payment method allowed for subscription creation? ' . ($this->isAllowedPaymentMethod($paymentMethodCode)));

        $websiteCode = $quote->getStore()->getWebsite()->getCode();
        if (!$this->generalConfig->isEnabled($websiteCode)
            || !$this->isAllowedPaymentMethod($paymentMethodCode)
            || !$quote->getCustomerId()
        ) {
            return;
        }

        try {
            $result = $this->subscriptionCreator->createSubscriptions($quote, $order);
            $this->checkoutSession->setData(SubscriptionCreator::CREATED_SUBSCRIPTION_IDS, $result[SubscriptionCreator::CREATED_SUBSCRIPTION_IDS]);
            $this->checkoutSession->setData(SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT, $result[SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT]);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * @param string $method
     */
    private function isAllowedPaymentMethod($method)
    {
        $allowedPaymentMethods = $this->offlinePaymentsConfig->getAllowedPaymentMethods();
        return  $method == GatewayConfigProvider::CODE || in_array($method, $allowedPaymentMethods);
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return void
     */
    private function addProductOptionsToQuoteItems($quote)
    {
        foreach ($quote->getAllItems() as $quoteItem) {
            if ($quoteItem->getProductOption()) { // Skip if options are already added
                continue;
            }
            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
            $item = $this->cartItemOptionProcessor->addProductOptions($quoteItem->getProductType(), $quoteItem);
            $this->cartItemOptionProcessor->applyCustomOptions($item);
        }
    }
}
