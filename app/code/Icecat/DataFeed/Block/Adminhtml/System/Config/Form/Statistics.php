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
        $forbidden_count='';
        $data                   = [];
        $data['missing_gtin']   = '';
        $data['not_found']      = '';
        $data['countof_four']      = '';
        $data['product_code'] = '';
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
        $count=0;
        $contents           .='[';
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
                $contents .= ",";
            }
            if (!empty($value['product_ids_with_missing_gtin_product_code'])) {
                $data['missing_gtin'] .= $value['product_ids_with_missing_gtin_product_code'] . ",";
            }
            if (!empty($value['product_ids'])) {
                $data['not_found']  .= $value['product_ids'];
            }
        }
        $contents           .='{"Product ID-":{"message":"","gtin":"","brand":"","product_code":""}}]';
        $data['total_record']   = $totalRecord;
        $data['success_record'] = $importedRecord;
        $data['error_record']   = $unsuccessfulRecord;
        $data['log']            = $contents;
        $contents           = $this->createLogFile($data);
        $forbidden_count = $this->cleanMessage($data);
        $data['countof_four']  = $forbidden_count;
        $notfound_icecat = $this->notfoundinIcecat($data);
        $data['not_found'] = $notfound_icecat;
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
            if (!empty($contents)) {
                $arkey=count($contents);
                for ($j=0; $j<$arkey; $j++) {
                    foreach ($contents[$j] as $key => $logMessage) {
                        $productId = str_replace("Product ID-", "", $key);
                        $logMessage->product_id = $productId;
                        $csvContent[] = $logMessage;
                    }
                }
            }
        }

        if (!empty($data['missing_gtin'])) {
            foreach (explode(',', rtrim($data['missing_gtin'], ",")) as $p_id) {
                $details                = new stdClass();
                $details->message       = "Product(s) ids " . $p_id . " with missing GTIN, Brand and Product Code";
                $details->gtin          = null;
                $details->brand         = null;
                $details->product_code  = null;
                $details->product_id    = $p_id;
                $csvContent[]           = $details;
            }
        }
        return $csvContent;
    }
    private function cleanMessage($data)
    {
        $msgContent = [];
        $i=0;
        if (!empty($data['log'])) {
            $contents1 = json_decode($data['log']);
            if (!empty($contents1)) {
                $arkey=count($contents1);
                for ($j=0; $j<$arkey; $j++) {
                    foreach ($contents1[$j] as $logMessage) {
                        if ($logMessage->message == "Display of content for users with a Full Icecat subscription level will require the use of a server certificate and a dynamic secret phrase. Please, contact your account manager for help with the implementation.") {
                            $i++;
                        }
                    }
                }
            }
        }
        return $i;
    }

    private function notfoundinIcecat($data)
    {
        $i=0;
        $msgContent = [];
        if (!empty($data['log'])) {
            $contents2 = json_decode($data['log']);
            if (!empty($contents2)) {
                $arkey=count($contents2);
                for ($j=0; $j<$arkey; $j++) {
                    foreach ($contents2[$j] as $key => $logMessage) {
                        $msgContent[] =$logMessage;
                        if ($logMessage->message == "The requested product is not present in the Icecat database" || $logMessage->message =="The GTIN can not be found" || $logMessage->message == "Product has brand restrictions or access is limited") {
                            $i++;
                        }
                    }
                }
            }
        }
        return $i;
    }

    public function getTimezone()
    {
        $timezone = $this->queue->getTimezone();
        return $timezone;
    }

    public function getCSV($contents, $logFileName)
    {
        $filepath   = 'icecatLogs/' . $logFileName;
        $varFolder  = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $stream     = $varFolder->openFile($filepath, 'w+');
        $stream->lock();
        $header     = ['Product Id', 'Message', 'GTIN', 'Brand', 'Product Code'];
        $stream->writeCsv($header);
        foreach ($contents as $item) {
            $itemData   = [];
            $itemData[] = $item->product_id;
            $itemData[] = $item->message;
            $itemData[] = $item->product_code;
            $itemData[] = $item->brand;
            $itemData[] = $item->gtin;
            $stream->writeCsv($itemData);
        }
        $fileUrl    = DIRECTORY_SEPARATOR . DirectoryList::MEDIA . DIRECTORY_SEPARATOR . 'icecatLogs' . DIRECTORY_SEPARATOR . $logFileName;
        return $fileUrl;
    }
}
