<?php

namespace SubscribePro\OfflinePayments\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;


class Config extends AbstractHelper
{
    public function getAllowedPaymentMethods($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        $paymentMethodsString = $this->getAllowedPaymentMethodsString($scope);
        return !empty($paymentMethodsString) ? explode(',', $paymentMethodsString) : [];
    }

    public function getAllowedPaymentMethodsString($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            'swarming_subscribepro/advanced/offline_payment_methods',
            $scope
        );
    }
}