<?php

namespace Swarming\OfflinePayments\Gateway\Config;

class Config extends \Swarming\SubscribePro\Gateway\Config\Config
{
    const KEY_OFFLINE_PAYMENT_METHODS = 'offline_payment_methods';

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function getOfflinePaymentMethodsString($storeId = null)
    {
        return $this->getValue(self::KEY_OFFLINE_PAYMENT_METHODS, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function getOfflinePaymentMethodsArray($storeId = null)
    {
        return explode(',', $this->getOfflinePaymentMethodsString($storeId));
    }
}
