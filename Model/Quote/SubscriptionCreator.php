<?php

namespace SubscribePro\OfflinePayments\Model\Quote;

class SubscriptionCreator extends \Swarming\SubscribePro\Model\Quote\SubscriptionCreator
{
    const CREATED_SUBSCRIPTION_IDS = 'created_subscription_ids';
    const FAILED_SUBSCRIPTION_COUNT = 'failed_subscription_count';

    /**
     * @var \Swarming\SubscribePro\Platform\Service\Subscription
     */
    protected $platformSubscriptionService;

    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Customer
     */
    protected $platformCustomerManager;

    /**
     * @var \Magento\Vault\Api\PaymentTokenManagementInterface
     */
    protected $tokenManagement;

    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * @var \Swarming\SubscribePro\Helper\Quote
     */
    protected $quoteHelper;

    /**
     * @var \Swarming\SubscribePro\Helper\OrderItem
     */
    protected $orderItemHelper;

    /**
     * @var \Swarming\SubscribePro\Model\Quote\QuoteItem\SubscriptionCreator
     */
    protected $quoteItemSubscriptionCreator;

    private $logger;

    /**
     * @param \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $tokenManagement
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     * @param \Swarming\SubscribePro\Helper\Quote $quoteHelper
     * @param \Swarming\SubscribePro\Helper\OrderItem $orderItemHelper
     * @param \Swarming\SubscribePro\Model\Quote\QuoteItem\SubscriptionCreator $quoteItemSubscriptionCreator
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager,
        \Magento\Vault\Api\PaymentTokenManagementInterface $tokenManagement,
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper,
        \Swarming\SubscribePro\Helper\Quote $quoteHelper,
        \Swarming\SubscribePro\Helper\OrderItem $orderItemHelper,
        \Swarming\SubscribePro\Model\Quote\QuoteItem\SubscriptionCreator $quoteItemSubscriptionCreator,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->platformCustomerManager = $platformCustomerManager;
        $this->tokenManagement = $tokenManagement;
        $this->quoteItemHelper = $quoteItemHelper;
        $this->quoteHelper = $quoteHelper;
        $this->orderItemHelper = $orderItemHelper;
        $this->quoteItemSubscriptionCreator = $quoteItemSubscriptionCreator;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return string[]
     */
    public function createSubscriptions($quote, $order)
    {
        $paymentProfileId = $this->getPaymentProfileId($order->getPayment());
        $paymentProfileId = $paymentProfileId ?: $order->getPayment()->getMethod();
        $platformCustomer = $this->platformCustomerManager->getCustomerById($quote->getCustomerId(), $this->quoteHelper->hasSubscription($quote));
        $subscriptionsSuccess = [];
        $subscriptionsFail = 0;
        /** @var \Magento\Quote\Model\Quote\Address $address */
        foreach ($quote->getAllShippingAddresses() as $address) {
            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
            foreach ($address->getAllVisibleItems() as $quoteItem) {
                if ($quoteItem->getIsVirtual() || !$this->canCreateSubscription($quoteItem)) {
                    continue;
                }

                $subscriptionId = $this->quoteItemSubscriptionCreator->create(
                    $quoteItem,
                    $platformCustomer->getId(),
                    $paymentProfileId,
                    $address,
                    $quote->getBillingAddress()
                );

                if ($subscriptionId) {
                    $this->orderItemHelper->updateOrderItem($order, $quoteItem->getItemId(), $subscriptionId);
                    $subscriptionsSuccess[] = $subscriptionId;
                } else {
                    $subscriptionsFail++;
                }
            }
        }

        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            if (!$quoteItem->getIsVirtual() || !$this->canCreateSubscription($quoteItem)) {
                continue;
            }

            $subscriptionId = $this->quoteItemSubscriptionCreator->create(
                $quoteItem,
                $platformCustomer->getId(),
                $paymentProfileId,
                null,
                $quote->getBillingAddress()
            );

            if ($subscriptionId) {
                $this->orderItemHelper->updateOrderItem($order, $quoteItem->getItemId(), $subscriptionId);
                $subscriptionsSuccess[] = $subscriptionId;
            } else {
                $subscriptionsFail++;
            }
        }

        return [
            self::CREATED_SUBSCRIPTION_IDS => $subscriptionsSuccess,
            self::FAILED_SUBSCRIPTION_COUNT => $subscriptionsFail
        ];
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderPaymentInterface $payment
     * @return string
     * @throws \Exception
     */
    protected function getPaymentProfileId($payment)
    {
        $vault = $this->tokenManagement->getByPaymentId($payment->getEntityId());
        return $vault && $vault->getIsActive() ? $vault->getGatewayToken() : null;
    }
}
