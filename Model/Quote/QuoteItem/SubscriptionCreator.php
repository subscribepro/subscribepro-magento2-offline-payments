<?php

namespace SubscribePro\OfflinePayments\Model\Quote\QuoteItem;

use Magento\Catalog\Api\Data\ProductInterface;

class SubscriptionCreator extends \Swarming\SubscribePro\Model\Quote\QuoteItem\SubscriptionCreator
{
    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param int $platformCustomerId
     * @param int $paymentProfileId
     * @param \Magento\Quote\Model\Quote\Address|null $address
     * @return int
     */
    public function create($quoteItem, $platformCustomerId, $paymentProfileId, $address = null)
    {
        $quote = $quoteItem->getQuote();
        $store = $quote->getStore();
        $productSku = $quoteItem->getProduct()->getData(ProductInterface::SKU);
        try {
            $subscription = $this->platformSubscriptionService->createSubscription();
            $subscription->setCustomerId($platformCustomerId);
            if (is_numeric($paymentProfileId)) {
                $subscription->setPaymentProfileId($paymentProfileId);
            } else  {
                $subscription->setPaymentMethodCode($paymentProfileId);
            }
            $subscription->setProductSku($productSku);
            $subscription->setProductOption($this->productOptionHelper->getProductOption($quoteItem));
            $subscription->setQty($quoteItem->getQty());
            $subscription->setUseFixedPrice(false);
            $subscription->setInterval($this->quoteItemHelper->getSubscriptionInterval($quoteItem));
            $subscription->setNextOrderDate($this->dateTimeFactory->create()->format('Y-m-d'));
            $subscription->setFirstOrderAlreadyCreated(true);
            $subscription->setMagentoStoreCode($store->getCode());
            $subscription->setSendCustomerNotificationEmail(true);

            $subscription->setRequiresShipping((bool)$address);
            if ($address) {
                $this->importShippingAddress($subscription, $address);
                $subscription->setMagentoShippingMethodCode($address->getShippingMethod());
            }

            if ($this->subscriptionOptionsConfig->isAllowedCoupon($store->getCode())) {
                $subscription->setCouponCode($quote->getCouponCode());
            }

            $this->eventManager->dispatch(
                'subscribe_pro_before_create_subscription_from_quote_item',
                ['subscription' => $subscription, 'quote_item' => $quoteItem]
            );

            $this->platformSubscriptionService->saveSubscription($subscription);

            $this->eventManager->dispatch(
                'subscribe_pro_after_create_subscription_from_quote_item',
                ['subscription' => $subscription, 'quote_item' => $quoteItem]);
        } catch(\Exception $e) {
            $this->logger->critical($e);
            return false;
        }
        return $subscription->getId();
    }
}
