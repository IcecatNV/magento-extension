<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Console\Command;

use Icecat\DataFeed\Helper\Data;
use Icecat\DataFeed\Model\Queue;
use Icecat\DataFeed\Model\Scheduler;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer\Cron;
use Magento\Framework\Message\ManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QueueRunner extends Command
{
    /** @var Data  */
    private $data;

    /** @var ManagerInterface  */
    private $messageManager;

    /** @var Queue  */
    private $queue;

    /** @var State  */
    private $state;

    /** @var Scheduler  */
    private $scheduler;
    private $cron;

    protected function configure(): void
    {
        $this->setName('icecat:queue-runner');
        $this->setDescription('Icecat Queue Processor Command');
        parent::configure();
    }

    /**
     * @param Data $data
     * @param ManagerInterface $messageManager
     * @param Queue $queue
     * @param State $state
     * @param Scheduler $scheduler
     * @param string|null $name
     */
    public function __construct(
        Data $data,
        ManagerInterface $messageManager,
        Queue $queue,
        State $state,
        Scheduler $scheduler,
        Cron $cron,
        string $name = null
    ) {
        parent::__construct($name);
        $this->data = $data;
        $this->messageManager = $messageManager;
        $this->queue = $queue;
        $this->state = $state;
        $this->scheduler = $scheduler;
        $this->cron = $cron;
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

        $exitCode = 0;

        if (!$this->data->getIsModuleEnabled()) {
            $errorMsg = '<info>Module is not enabled</info>';
            if (php_sapi_name() === 'cli') {
                $output->writeln($errorMsg);
                return 1;
            }
            $this->messageManager->addErrorMessage($errorMsg);
            return 1;
        }

        if (empty($this->data->getUsername()) || empty($this->data->getPassword())) {
            $errorMsg = '<info>Username or Password is missing, please check the configuration</info>';
            if (php_sapi_name() === 'cli') {
                $output->writeln($errorMsg);
                return 1;
            }
            $this->messageManager->addErrorMessage($errorMsg);
            return 1;
        }

        $scheduledJobs = $this->scheduler->fetchNotCompletedScheduleRecord();
        if (empty($scheduledJobs)) {
            return $exitCode;
        }

        $cronArray = [];
        foreach ($scheduledJobs as $key => $value) {
            if ($value['status'] == 'in_progress') {
                $cronArray['key'] = $key;
            }
        }

        if (!empty($cronArray)) {
            // Already Running the cron
            $scheduledJob = $scheduledJobs[$cronArray['key']];
            $this->processScheduler($scheduledJob);
        } else {
            // No Cron Is Running
            // Priority will be manual cron
            $manualCron = [];
            foreach ($scheduledJobs as $key => $value) {
                if ($value['queue_mode'] == 'manual') {
                    $manualCron['key'] = $key;
                }
            }

            if (!empty($manualCron)) {
                $scheduledJob = $scheduledJobs[$manualCron['key']];
                $this->processScheduler($scheduledJob);
            } else {
                // Automatic cron logic
                $scheduledJob = $scheduledJobs[0];
                $this->processScheduler($scheduledJob);
            }
        }

        return $exitCode;
    }

    /**
     * @param $scheduledJob
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    private function processScheduler($scheduledJob)
    {
        $uniqueScheduleId = (int)date('dmyHi');
        $startedDateTime = date('Y-m-d H:i:s');
        if ($scheduledJob['queue_mode'] == 'manual') {
            // if Already running cron is manual
            if ($scheduledJob['status'] == 'scheduled') {
                // Adding Jobs to the Queue
                if ($scheduledJob['type'] == 'full_import') {
                    $this->queue->addJobToQueue($uniqueScheduleId);
                } else {
                    $this->queue->addNewProductToQueue($uniqueScheduleId);
                }
                // Updating the Scheduler To in_progress
                $this->scheduler->updateSchedulerStatus($scheduledJob, 'in_progress', $startedDateTime, null, $uniqueScheduleId, null);

                // Doing Import here
                $this->queue->runCron($uniqueScheduleId);

                // Checking Queue Table is Empty or not
                $pendingJobs = $this->queue->getPendingQueueJobs();
                if ($pendingJobs < 1) {
                    $this->scheduler->updateSchedulerStatus($scheduledJob, 'completed', $startedDateTime, date('Y-m-d H:i:s'), $uniqueScheduleId, null);
                }
            } else {
                // Do Import Only
                $this->queue->runCron($scheduledJob['schedule_unique_id']);
                $pendingJobs = $this->queue->getPendingQueueJobs();
                if ($pendingJobs < 1) {
                    $this->scheduler->updateSchedulerStatus($scheduledJob, 'completed', $scheduledJob['started'], date('Y-m-d H:i:s'), $scheduledJob['schedule_unique_id'], null);
                }
            }
        } elseif ($scheduledJob['queue_mode'] == 'automatic') {
            $currentDateTime = strtotime(date('Y-m-d H:i:00'));
            $cronRunDateTime = strtotime($scheduledJob['cron_run_time']);
            $nextCronRunDateTime = $this->scheduler->calculateNextCronjob($scheduledJob['cron_expression']);
            if ($scheduledJob['status'] == 'scheduled' && $currentDateTime == $cronRunDateTime) {
                // Adding Jobs to the Queue
                $this->queue->addJobToQueue($uniqueScheduleId);

                // Updating the Scheduler To in_progress
                $this->scheduler->updateSchedulerStatus($scheduledJob, 'in_progress', $startedDateTime, null, $uniqueScheduleId, $scheduledJob['cron_run_time']);

                // Doing Import here
                $this->queue->runCron($uniqueScheduleId);

                // Checking Queue Table is Empty or not
                $pendingJobs = $this->queue->getPendingQueueJobs();
                if ($pendingJobs < 1) {
                    $this->scheduler->updateSchedulerStatus($scheduledJob, 'scheduled', $startedDateTime, date('Y-m-d H:i:s'), $uniqueScheduleId, $nextCronRunDateTime);
                }
            } else {
                // Do Import Only
                $this->queue->runCron($scheduledJob['schedule_unique_id']);
                $pendingJobs = $this->queue->getPendingQueueJobs();
                if ($pendingJobs < 1) {
                    $this->scheduler->updateSchedulerStatus($scheduledJob, 'scheduled', $scheduledJob['started'], date('Y-m-d H:i:s'), $scheduledJob['schedule_unique_id'], $nextCronRunDateTime);
                }
            }
        }
    }
}
