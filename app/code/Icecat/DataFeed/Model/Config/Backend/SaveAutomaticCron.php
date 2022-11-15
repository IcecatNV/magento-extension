<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Model\Config\Backend;

use Icecat\DataFeed\Model\Scheduler;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class SaveAutomaticCron extends \Magento\Framework\App\Config\Value
{
    private Scheduler $scheduler;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param Scheduler $scheduler
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        Scheduler $scheduler,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->scheduler = $scheduler;
    }

    /**
     * @return $this
     */
    public function afterSave()
    {
        if ($this->isValueChanged()) {
            $value = $this->getValue();
            $nextCronDateTime = $this->scheduler->calculateNextCronjob($value);
            $this->scheduler->scheduleAutomaticCron($value, $nextCronDateTime);
        }
        return parent::afterSave();
    }
}
