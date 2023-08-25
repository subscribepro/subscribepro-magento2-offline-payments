<?php

namespace Swarming\OfflinePayments\Observer\Payment;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Swarming\SubscribePro\Gateway\Config\Config as PaymentConfig;
use Swarming\OfflinePayments\Helper\Config;

class Availability implements ObserverInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Swarming\SubscribePro\Helper\Quote
     */
    protected $quoteHelper;

    /**
     * @var \Swarming\OfflinePayments\Helper\Config
     */
    protected $config;


    private $logger;

    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Swarming\SubscribePro\Helper\Quote $quoteHelper
     * @param Config $config
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Swarming\SubscribePro\Helper\Quote $quoteHelper,
        \Swarming\OfflinePayments\Helper\Config $config,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteHelper = $quoteHelper;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Payment\Model\Method\Adapter $methodInstance */
        $methodInstance = $observer->getData('method_instance');

        /** @var \Magento\Framework\DataObject $result */
        $result = $observer->getData('result');

        /** @var \Magento\Quote\Api\Data\CartInterface $quote */
        $quote = $observer->getData('quote');
        $quote = $quote ?: $this->checkoutSession->getQuote();
        if (!$quote) {
            return;
        }

        $methodCode = $methodInstance->getCode();
        $isAvailable = $result->getData('is_available');
        $isActiveNonSubscription = $methodInstance->getConfigData(PaymentConfig::KEY_ACTIVE_NON_SUBSCRIPTION);

        // First we check whether the method is available in the first place. If not, we don't need to do anything else.
        if (!$isAvailable) {
            return;
        // Then we check if the cart has a subscription. If not, and if the method code is the SubscribePro method, and it is not enabled for non-subscription orders, we hide it.
        } else if (!$this->quoteHelper->hasSubscription($quote)) {
            if ($methodCode === ConfigProvider::CODE && !$isActiveNonSubscription) {
                $isAvailable = false;
            }
        // If the order does have a subscription, we check if the method is either one of the allowed offline payment methods or the Subscribe Pro payment method. If not, we hide it.
        } else {
            $availableOfflinePaymentMethods = $this->config->getAllowedPaymentMethods();
            if ($this->quoteHelper->hasSubscription($quote)) {
                $isAvailable = in_array($methodCode, $availableOfflinePaymentMethods) || ConfigProvider::CODE == $methodCode;
            }
        }

        $result->setData('is_available', $isAvailable);
    }
}
