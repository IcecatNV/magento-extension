<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\ObjectManagerInterface;
use Zend_Db_Statement_Exception;
use Icecat\DataFeed\Helper\Data;

class Scheduler
{
    private Data $data;
    /**
     * @var AdapterInterface
     * */
    private $db;

    /**
     * @var string
     * */
    private $table;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ObjectManagerInterface $objectManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Data $data
    ) {
        $this->table = $resourceConnection->getTableName('icecat_queue_scheduler');
        $this->db = $objectManager->create(ResourceConnection::class)->getConnection('core_write');
        $this->_scopeConfig = $scopeConfig;
        $this->data = $data;
    }

    /**
     * @param $type
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function scheduleProductImport($type)
    {
        $scheduledRecord = $this->fetchScheduleRecord();
        $authResponse = $this->data->validateToken();
        $logger = \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class);
        $logger->info("auth response".json_encode($authResponse));
        if($authResponse['httpcode'] != "400")
        {
            if (empty($scheduledRecord)) {
                $this->db->insert($this->table, [
                    'type' => $type,
                    'status' => 'scheduled',
                    'created_at' => date('Y-m-d H:i:s'),
                    'queue_mode' => 'manual'
                ]);
                if ($type == 'full_import') {
                    $result = [
                        'success' => true,
                        'message' => 'Full import is Scheduled'
                    ];
                } else {
                    $result = [
                        'success' => true,
                        'message' => 'New product import is Scheduled'
                    ];
                }
            } else {
                if ($scheduledRecord['status'] == 'in_progress') {
                    $result = [
                        'success' => true,
                        'message' => 'There is already Queue in progress'
                    ];
                } else {
                    $this->db->update($this->table, [
                        'type' => $type,
                    ], ['id IN (?)' => $scheduledRecord['id']]);

                    if ($type == 'full_import') {
                        $result = [
                            'success' => true,
                            'message' => 'Full import is Scheduled'
                        ];
                    } else {
                        $result = [
                            'success' => true,
                            'message' => 'New product import is Scheduled'
                        ];
                    }
                }
            }
        }
        else
        {
            $result = [
                            'success' => true,
                            'message' => 'Invalid API Credentials'
                        ];
        }
        return $result;
    }

    /**
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function fetchScheduleRecord()
    {
        $query = $this->db->select()
            ->from($this->table, '*')
            ->where('status!=?', 'completed')
            ->where('queue_mode=?', 'manual')
            ->limit(1)
            ->order('id ASC');
        return $this->db->query($query)->fetch();
    }

    /**
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function fetchNotCompletedScheduleRecord()
    {
        $query = $this->db->select()
            ->from($this->table, '*')
            ->where('status!=?', 'completed')
            ->order('id ASC');
        return $this->db->query($query)->fetchAll();
    }

    /**
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function fetchInprogressScheduleRecord()
    {
        $query = $this->db->select()
            ->from($this->table, '*')
            ->where('status=?', 'in_progress')
            ->order('id ASC');
        return $this->db->query($query)->fetch();
    }
    
    public function fetchAutomaticScheduleRecord()
    {
        $query = $this->db->select()
            ->from($this->table, '*')
            ->where('status=?', 'in_progress')
            ->where('queue_mode=?', 'automatic')
            ->order('id ASC');
        return $this->db->query($query)->fetch();
    }

    /**
     * @param array $scheduler
     * @return void
     */
    public function updateSchedulerStatus(array $scheduler, $status, $started = null, $ended = null, $uniqueScheduleId = null, $nextCronRunDateTime = null)
    {
        $this->db->update($this->table, [
            'status' => $status,
            'started' => $started,
            'ended' => $ended,
            'schedule_unique_id' => $uniqueScheduleId,
            'cron_run_time' => $nextCronRunDateTime
        ], ['id IN (?)' => $scheduler['id']]);
    }

    public function scheduleAutomaticCron($cronExp, $nextCronDateTime)
    {
        $query = $this->db->select()
            ->from($this->table, '*')
            ->where('queue_mode=?', 'automatic')
            ->limit(1)
            ->order('id ASC');

        $data = $this->db->query($query)->fetch();
        if (empty($data)) {
            $this->db->insert($this->table, [
                'type' => 'full_import',
                'status' => 'scheduled',
                'created_at' => date('Y-m-d H:i:s'),
                'queue_mode' => 'automatic',
                'cron_expression' => $cronExp,
                'cron_run_time' => $nextCronDateTime
            ]);
        } else {
            $this->db->update($this->table, [
                'cron_expression' => $cronExp,
                'cron_run_time' => $nextCronDateTime
            ], ['id IN (?)' => $data['id']]);
        }
        return true;
    }

    public function calculateNextCronjob($expression)
    {
        $cron = \Cron\CronExpression::factory($expression);
        $nextDate = $cron->getNextRunDate()->format('Y-m-d H:i:s');
        return $nextDate;
    }
}
