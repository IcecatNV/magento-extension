<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Controller\Adminhtml\Data;

use Icecat\DataFeed\Model\Scheduler;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Importproductinfo extends Action
{
    private Scheduler $scheduler;

    /**
     * @param Context $context
     * @param Scheduler $scheduler
     */
    public function __construct(
        Context $context,
        Scheduler $scheduler
    ) {
        parent::__construct($context);
        $this->scheduler = $scheduler;
    }

    public function execute()
    {
        $this->checkAction();
    }

    /**
     * Return some checking result
     *
     * @return void
     */
    public function checkAction()
    {
        $result = $this->scheduler->scheduleProductImport('full_import');
        $this->getResponse()->setBody(json_encode($result));
    }
}
