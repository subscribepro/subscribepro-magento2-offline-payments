<?php

namespace SubscribePro\OfflinePayments\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;


class Config extends AbstractHelper
{
    public function getAllowedPaymentMethods($scope = ScopeInterface::SCOPE_WEBSITE)
    {
        $paymentMethodsString = $this->getAllowedPaymentMethodsString($scope);
        return !empty($paymentMethodsString) ? explode(',', $paymentMethodsString) : [];
    }

    public function getAllowedPaymentMethodsString($scope = ScopeInterface::SCOPE_WEBSITE)
    {
        return $this->scopeConfig->getValue(
            'swarming_subscribepro/advanced/offline_payment_methods',
            $scope
        );
    }
}