<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Block\Adminhtml\System\Config\Form;

use Icecat\DataFeed\Model\Queue;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use stdClass;

class Statistics extends Field
{
    private Queue $queue;
    private Filesystem $filesystem;


    /**
     * @param Context $context
     * @param Queue $queue
     * @param Filesystem $filesystem
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(
        Context                  $context,
        Queue                    $queue,
        Filesystem               $filesystem,
        array                    $data = [],
        ?SecureHtmlRenderer      $secureRenderer = null
    ) {
        parent::__construct($context, $data, $secureRenderer);
        $this->queue = $queue;
        $this->filesystem = $filesystem;
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('icecat/datafeed/statistics.phtml');
    }

    /**
     * Return element html
     *
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return $import_info array for table of statistics
     *
     * @return array
     */

    public function collectData()
    {
        $data                   = [];
        $data['missing_gtin']   = '';
        $data['not_found']      = '';
        $schedulerDetails       = $this->queue->getSchedulerId();
        if (empty($schedulerDetails)) {
            return $data;
        }
        $schedulerUniqueId      = $schedulerDetails['schedule_unique_id'];
        $import_info            = $this->queue->getStatistics($schedulerUniqueId);
        $lastElement            = count($import_info);
        $i                      = 0;
        $data['execution_type'] = $schedulerDetails['queue_mode'];
        $totalRecord            = 0;
        $importedRecord         = 0;
        $unsuccessfulRecord     = 0;
        $contents               = null;
        foreach ($import_info as $key => $value) {
            if ($key == 0) {
                $data['started']    = $value['started'];
            }
            if (++$i === $lastElement) {
                $data['ended']      = $value['ended'];
            }
            $totalRecord        += $value['imported_record'] + $value['unsuccessful_record'];
            $importedRecord     += $value['imported_record'];
            $unsuccessfulRecord += $value['unsuccessful_record'];
            if ($value['error_log'] != "[]") {
                $contents           .= $value['error_log'];
            }
            if (!empty($value['product_ids_with_missing_gtin_product_code'])) {
                $data['missing_gtin'] .= $value['product_ids_with_missing_gtin_product_code'].",";
            }

            if (!empty($value['product_ids'])) {
                $data['not_found']  .= $value['product_ids'];
            }
        }
        $data['total_record']   = $totalRecord;
        $data['success_record'] = $importedRecord;
        $data['error_record']   = $unsuccessfulRecord;
        $data['log']            = $contents;

        $contents           = $this->createLogFile($data);
        $logFileName        = 'icecat_last_import.csv';
        $fileUrl            = $this->getCSV($contents, $logFileName);
        $data['log_url']    = $fileUrl;
        return $data;
    }

    private function createLogFile($data)
    {
        $csvContent = [];
        if (!empty($data['log'])) {
            $contents = json_decode($data['log']);
            foreach ($contents as $key => $logMessage) {
                $productId = str_replace("Product ID-", "", $key);
                $logMessage->product_id = $productId;
                $csvContent[] = $logMessage;
            }
        }

        if (!empty($data['missing_gtin'])) {
            foreach (explode(',', rtrim($data['missing_gtin'], ",")) as $p_id) {
                $details                = new stdClass();
                $details->message       = "Product(s) ids ".$p_id." with missing GTIN, Brand and Product Code";
                $details->gtin          = null;
                $details->brand         = null;
                $details->product_code  = null;
                $details->product_id    = $p_id;
                $csvContent[]           = $details;
            }
        }
        return $csvContent;
    }

    public function getTimezone()
    {
        $timezone = $this->queue->getTimezone();
        return $timezone;
    }

    public function getCSV($contents, $logFileName)
    {
        $filepath   = 'icecatLogs/'.$logFileName;
        $varFolder  = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $stream     = $varFolder->openFile($filepath, 'w+');
        $stream->lock();
        $header     = ['Product Id', 'Message', 'GTIN', 'Brand', 'Product Code'];
        $stream->writeCsv($header);
        foreach ($contents as $item) {
            $itemData   = [];
            $itemData[] = $item->product_id;
            $itemData[] = $item->message;
            $itemData[] = $item->gtin;
            $itemData[] = $item->brand;
            $itemData[] = $item->product_code;
            $stream->writeCsv($itemData);
        }
        $fileUrl    = DIRECTORY_SEPARATOR. DirectoryList::MEDIA. DIRECTORY_SEPARATOR. 'icecatLogs' . DIRECTORY_SEPARATOR . $logFileName;
        return $fileUrl;
    }
}
