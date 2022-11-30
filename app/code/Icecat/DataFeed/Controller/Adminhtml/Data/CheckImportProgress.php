<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Controller\Adminhtml\Data;

use Icecat\DataFeed\Model\Scheduler;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class CheckImportProgress extends Action
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
        $result = $this->scheduler->fetchInprogressScheduleRecord();
        $automaticResult = $this->scheduler->fetchAutomaticScheduleRecord();
        if ($automaticResult) {
            $response = [
                'status' => true,
                'message' => 'Automatic import cron is running...',
            ];
        } elseif ($result) {
            $message = 'Full import cron is in progress...';
            if ($result['type'] == 'new_import') {
                $message = 'Delta import corn is in progress...';
            }
            $response = [
                'status' => true,
                'message' => $message,
            ];
        } else {
            $response = [
                'status' => false,
                'message' => 'No cron is running...',
            ];
        }
        $this->getResponse()->setBody(json_encode($response));
    }
}
