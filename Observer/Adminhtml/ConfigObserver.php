<?php

namespace Icecat\DataFeed\Observer\Adminhtml;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Icecat\DataFeed\Helper\Data;
use Icecat\DataFeed\Model\Config\Source\UserType;

class ConfigObserver implements ObserverInterface
{

    public function __construct(
    	Data $data,
        UserType $userType
    ) {
    	$this->data = $data;
        $this->userType = $userType;
    }

    public function execute(EventObserver $observer)
    {
        $subscriptionLevel = $this->data->getUserType();     
        if (!empty($subscriptionLevel) && $subscriptionLevel == 'full') {
            $this->userType->setUserTypeValue(1);
        } else if (!empty($subscriptionLevel) && $subscriptionLevel == 'open') {
            $this->userType->setUserTypeValue(0);
        } else {
            $this->userType->setUserTypeValue(0);
        }
        $this->userType->flushCache();
    }
}