<?php

namespace Icecat\DataFeed\Console\Command;

use Icecat\DataFeed\Helper\Data;
use Icecat\DataFeed\Model\Queue;
use Magento\Framework\App\State;
use Magento\Framework\Message\ManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddQueueJobs extends Command
{
    /** @var Data  */
    private $data;

    /** @var ManagerInterface  */
    private $messageManager;

    /** @var Queue  */
    private $queue;

    /** @var State  */
    private $state;

    /**
     * @param Data $data
     * @param ManagerInterface $messageManager
     * @param Queue $queue
     * @param State $state
     * @param string|null $name
     */
    public function __construct(
        Data $data,
        ManagerInterface $messageManager,
        Queue $queue,
        State $state,
        string $name = null
    ) {
        parent::__construct($name);
        $this->data = $data;
        $this->messageManager = $messageManager;
        $this->queue = $queue;
        $this->state = $state;
    }

    protected function configure(): void
    {
        $this->setName('icecat:add-queue-jobs');
        $this->setDescription('Icecat Add Queue Job Command');
        parent::configure();
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

        $status = $this->queue->addJobToQueue();
        if ($status) {
            if (php_sapi_name() === 'cli') {
                $output->writeln("Jobs added to the queue");
                return 1;
            }
        } else {
            if (php_sapi_name() === 'cli') {
                $output->writeln("something went wrong! please try again later");
                return 1;
            }
        }
        return $exitCode;
    }
}
