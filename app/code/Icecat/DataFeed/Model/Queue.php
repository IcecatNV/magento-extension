<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Model;

use Icecat\DataFeed\Helper\Data;
use Icecat\DataFeed\Service\IcecatApiService;
use Magento\Catalog\Model\Product\Gallery\Processor;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Zend_Db_Expr;
use Zend_Db_Statement_Exception;

class Queue
{
    public const UNLOCK_STACKED_JOBS_AFTER_MINUTES = 15;
    public const CLEAR_ARCHIVE_LOGS_AFTER_DAYS = 30;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var AdapterInterface
     * */
    private $db;

    /**
     * @var string
     * */
    private $table;

    /** @var string */
    private $logTable;

    /** @var string */
    private $archiveTable;

    /** @var array */
    private $logRecord;

    /** @var ConsoleOutput  */
    private $output;

    /** @var Data  */
    private $data;

    /** @var ProductRepository  */
    private $productRepository;

    /** @var IcecatApiService  */
    private $icecatApiService;

    /** @var IceCatUpdateProduct  */
    private $iceCatUpdateProduct;

    /** @var Processor  */
    private $processor;

    /** @var int */
    private $noOfFailedJobs = 0;

    /**
     * @var string
     * */
    private $galleryEntitytable;

    /**
     * @var string
     * */
    private $galleryTable;

    /**
     * @var string
     * */
    private $videoTable;

    private $columnExists;

    /**
     * @param CollectionFactory $collectionFactory
     * @param ResourceConnection $resourceConnection
     * @param ObjectManagerInterface $objectManager
     * @param ConsoleOutput $output
     * @param Data $data
     * @param ProductRepository $productRepository
     * @param IcecatApiService $icecatApiService
     * @param IceCatUpdateProduct $iceCatUpdateProduct
     * @param Processor $processor
     * @param ResourceConnection $resourceConnection
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ResourceConnection $resourceConnection,
        ObjectManagerInterface $objectManager,
        ConsoleOutput $output,
        Data $data,
        ProductRepository $productRepository,
        IcecatApiService $icecatApiService,
        IceCatUpdateProduct $iceCatUpdateProduct,
        Processor $processor
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->table = $resourceConnection->getTableName('icecat_datafeed_queue');
        $this->archiveTable = $resourceConnection->getTableName('icecat_datafeed_queue_archive');
        $this->logTable = $resourceConnection->getTableName('icecat_datafeed_queue_log');
        $this->schedulerTable = $resourceConnection->getTableName('icecat_queue_scheduler');
        $this->db = $objectManager->create(ResourceConnection::class)->getConnection('core_write');
        $this->output = $output;
        $this->data = $data;
        $this->productRepository = $productRepository;
        $this->icecatApiService = $icecatApiService;
        $this->iceCatUpdateProduct = $iceCatUpdateProduct;
        $this->processor = $processor;
        $this->galleryEntitytable = $resourceConnection->getTableName('catalog_product_entity_media_gallery_value');
        $this->galleryTable = $resourceConnection->getTableName('catalog_product_entity_media_gallery');
        $this->videoTable = $resourceConnection->getTableName('catalog_product_entity_media_gallery_value_video');
        $this->columnExists = $resourceConnection->getConnection()->tableColumnExists('catalog_product_entity_media_gallery_value', 'entity_id');
    }

    public function addJobToQueue($uniqueScheduledId)
    {
        $productCollection = $this->getProductCollections();
        //$productCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $productCollection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $productCollection->getSelect()->columns('entity_id');
        $collection1Ids = $productCollection->getAllIds();
        sort($collection1Ids);
        $chunkedArray = array_chunk($collection1Ids, 50);
        foreach ($chunkedArray as $productids) {
            $this->db->insert($this->table, [
                'created' => date('Y-m-d H:i:s'),
                'pid' => null,
                'data' => implode(',', $productids),
                'data_size' => count($productids),
                'schedule_unique_id' => $uniqueScheduledId
            ]);
        }

        return true;
    }

    public function addNewProductToQueue($uniqueScheduledId)
    {
        $schedulerDetails       = $this->getSchedulerId();
        if (empty($schedulerDetails) || empty($schedulerDetails['ended'])) {
            $productCollection  = $this->getProductCollections();
        } else {
            $productCollection  = $this->getNewProductsCollections($schedulerDetails['ended']);
        }
        //$productCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $productCollection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $productCollection->getSelect()->columns('entity_id');
        $collection1Ids = $productCollection->getAllIds();
        sort($collection1Ids);
        $chunkedArray = array_chunk($collection1Ids, 50);
        foreach ($chunkedArray as $productids) {
            $this->db->insert($this->table, [
                'created' => date('Y-m-d H:i:s'),
                'pid' => null,
                'data' => implode(',', $productids),
                'data_size' => count($productids),
                'schedule_unique_id' => $uniqueScheduledId
            ]);
        }
        return true;
    }

    public function runCron($uniqueScheduledId)
    {
        $this->clearOldArchiveRecords();
        $this->unlockStackedJobs();
        $this->logRecord = [
            'started' => date('Y-m-d H:i:s'),
            'processed_jobs' => 0
        ];

        $started = time();

        $this->run(5, $uniqueScheduledId);

        $this->logRecord['duration'] = time() - $started;

        if (php_sapi_name() === 'cli') {
            $this->output->writeln(
                $this->logRecord['processed_jobs'] . ' jobs processed in ' . $this->logRecord['duration'] . ' seconds.'
            );
        }
    }

    private function clearOldArchiveRecords()
    {
        $archiveLogClearLimit = self::CLEAR_ARCHIVE_LOGS_AFTER_DAYS;
        $this->db->delete(
            $this->archiveTable,
            'created_at < (NOW() - INTERVAL ' . $archiveLogClearLimit . ' DAY)'
        );
    }

    private function unlockStackedJobs()
    {
        $this->db->update($this->table, [
            'locked_at' => null,
            'pid' => null,
        ], ['locked_at < (NOW() - INTERVAL ' . self::UNLOCK_STACKED_JOBS_AFTER_MINUTES . ' MINUTE)']);
    }

    /**
     * @return mixed
     */
    private function getProductCollections()
    {
        $collection = $this->collectionFactory
            ->create();
        return $collection;
    }

    private function getNewProductsCollections($cronLastUpdated)
    {
        $collection = $this->collectionFactory
            ->create();
        $collection->addAttributeToFilter('updated_at', ['gteq' => $cronLastUpdated]);
        return $collection;
    }

    public function run($maxJobs, $uniqueScheduledId)
    {
        $this->clearOldFailingJobs();
        $jobs = $this->getJobs($maxJobs);

        if ($jobs === []) {
            return;
        }
        // Run all reserved jobs
        foreach ($jobs as $job) {
            try {
                $iceCatLogArray = [
                    'job_id' => $job['job_id'],
                    'started' => date('Y-m-d H:i:s'),
                ];
                $productIds = explode(',', $job['data']);
                $productWithOutGtinAndProductCodeAndBrandCode = [];
                $errorProductIds = [];
                $successProducts = [];
                $errorLog  = [];
                $started = time();
                foreach ($productIds as $productId) {
                    $icecatStores = $this->data->getIcecatStoreConfig();
                    $storeArray = explode(',', $icecatStores);
                    $storeArrayForImage = explode(',', $icecatStores);
                    //$storeArray[] = 0; // Admin store
                    $storeArrayForImage[] = 0; // Admin store
                    $updatedStore = [];
                    $errorMessage = null;
                    $globalImageArray = [];
                    $globalVideoArray = [];
                    $responseArray = [];
                    foreach ($storeArrayForImage as $store) {
                        if ($this->data->isImportImagesEnabled()) {
                            $product = $this->productRepository->getById($productId, false, $store);
                            $images = $product->getMediaGalleryImages();
                            $mediaTypeArray = ['image', 'small_image', 'thumbnail'];
                            $this->processor->clearMediaAttribute($product, $mediaTypeArray);
                            $existingMediaGalleryEntries = $product->getMediaGalleryEntries();
                            foreach ($existingMediaGalleryEntries as $key => $entry) {
                                unset($existingMediaGalleryEntries[$key]);
                            }
                            $product->setMediaGalleryEntries($existingMediaGalleryEntries);
                            foreach ($images as $child) {
                                $this->processor->removeImage($product, $child->getFile());
                            }
                            $this->productRepository->save($product);
                        }
                    }

                    // Check for icecat root category from all root categories, create it if not there
                    $rootCats = [];
                    if ($this->data->isCategoryImportEnabled()) {
                        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        $collection = $objectManager->get('\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory')->create();
                        $collection->addAttributeToFilter('level', ['eq' => 1]);
                        foreach ($collection as $coll) {
                            $rootCatId = $coll->getId();
                            $rootCat = $objectManager->get('Magento\Catalog\Model\Category');
                            $rootCatData = $rootCat->load($rootCatId);
                            $rootCats[] = strtolower($rootCatData->getName());
                        }
                        $myRoot=strtolower('Icecat Categories');
                        if (!in_array($myRoot, $rootCats)) {
                            $store = $this->storeManager->getStore();
                            $storeId = $store->getStoreId();
                            $rootNodeId = 1;
                            $rootCat = $objectManager->get('Magento\Catalog\Model\Category');
                            $cat_info = $rootCat->load($rootNodeId);
                            $myRoot='Icecat Categories';
                            $name=ucfirst($myRoot);
                            $url=strtolower($myRoot);
                            $cleanurl = trim(preg_replace('/ +/', '', preg_replace('/[^A-Za-z0-9 ]/', '', urldecode(html_entity_decode(strip_tags($url))))));
                            $categoryFactory=$objectManager->get('\Magento\Catalog\Model\CategoryFactory');
                            $categoryTmp = $categoryFactory->create();
                            $categoryTmp->setName($name);
                            $categoryTmp->setIsActive(true);
                            $categoryTmp->setIncludeInMenu(false);
                            $categoryTmp->setUrlKey($cleanurl);
                            $categoryTmp->setData('description', 'description');
                            $categoryTmp->setParentId($rootCat->getId());
                            $categoryTmp->setStoreId($storeId);
                            $categoryTmp->setPath($rootCat->getPath());
                            $savedCategory = $categoryTmp->save();
                            $icecatCid = $savedCategory->getId();
                            $this->config->saveConfig('datafeed/icecat/root_category_id', $icecatCid, 'default', 0);
                        } else {
                            $categoryFactory = $objectManager->get('\Magento\Catalog\Model\CategoryFactory');
                            $collection = $categoryFactory->create()->getCollection()->addAttributeToFilter('name', "Icecat Categories")->setPageSize(1);
                            $icecatCid = $collection->getFirstItem()->getId();
                        }

                        $allstores = $this->storeRepository->getList();
                        foreach ($allstores as $eachstore) {
                            if ($eachstore->getCode() == 'admin') {
                                continue;
                            }
                            $allstoreArr[] = $eachstore->getId();
                        }
                        foreach ($allstoreArr as $eachstore) {
                            $storeData = $this->storeRepository->getById($eachstore);
                            $storeManager = $objectManager->get(StoreManagerInterface::class);
                            $storeGroup = $objectManager->get(GroupInterfaceFactory::class)->create()->load($storeData->getData('group_id'));
                            if (in_array($eachstore, $storeArray)) {
                                $storeGroup->setRootCategoryId($icecatCid);
                            } else {
                                $storeGroup->setRootCategoryId(2);
                            }
                            $objectManager->get(GroupResource::class)->save($storeGroup);
                        }
                    }
                    foreach ($storeArray as $store) {
                        $product = $this->productRepository->getById($productId, false, $store);
                        $language = $this->data->getStoreLanguage($store);
                        $icecatUri = $this->data->getIcecatUri($product, $language);
                        if ($icecatUri) {
                            $response = $this->icecatApiService->execute($icecatUri);
                            $responseArray[$store] = $response;
                            if (!empty($response) && !empty($response['Code'])) {
                                $errorMessage       = $this->errorMessageResponse($response, $product);
                                $errorProductIds[]  = $productId;
                                $errorLog['Product ID-' . $productId] = $errorMessage;
                            } else {
                                $globalImageArray = $this->iceCatUpdateProduct->updateProductWithIceCatResponse($product, $response, $store, $globalImageArray);
                                $successProducts[] = $productId;
                            }
                        } else {
                            $productWithOutGtinAndProductCodeAndBrandCode[] = $productId;
                        }
                    }

                    // Logic for Video
                    if (!empty($responseArray)) {
                        if ($this->data->isImportMultimediaEnabled()) {
                            foreach ($storeArray as $store) {
                                $product = $this->productRepository->getById($productId);
                                if (!empty($response) && !empty($response['Code'])) {
                                } else {
                                    $globalVideoArray = $this->iceCatUpdateProduct->importVideo($product, $responseArray[$store], $store, $globalVideoArray);
                                }
                            }
                        }
                    }

                    if ($this->columnExists === false) {
                        $query = "select * from " . $this->galleryEntitytable . " A left join " . $this->galleryTable . " B on B.value_id = A.value_id where A.row_id=" . $productId . " and B.media_type='image'";
                    } else {
                        $query = "select * from " . $this->galleryEntitytable . " A left join " . $this->galleryTable . " B on B.value_id = A.value_id where A.entity_id=" . $productId . " and B.media_type='image'";
                    }
                    $data = $this->db->query($query)->fetchAll();
                    foreach ($globalImageArray as $key => $imageArray) {
                        foreach ($imageArray as $image) {
                            $imageData = explode('.', $image);
                            $imageName = $imageData[0];
                            foreach ($data as $k => $value) {
                                if ($key != $value['store_id']) {
                                    if (strpos($value['value'], $imageName) !== false) {
                                        $updateQuery = "UPDATE " . $this->galleryEntitytable . " SET disabled=1 WHERE value_id=" . $value['value_id'] . " AND store_id=" . $value['store_id'];
                                        $this->db->query($updateQuery);
                                    }
                                }
                            }
                        }
                    }

                    if (!empty($globalVideoArray)) {
                        if ($this->columnExists === false) {
                            $query = "select * from " . $this->galleryEntitytable . " A left join " . $this->galleryTable . " B on B.value_id = A.value_id
                    left join " . $this->videoTable . "  C on C.value_id = A.value_id
                     where A.row_id=" . $productId . " and B.media_type='external-video'";
                        } else {
                            $query = "select * from " . $this->galleryEntitytable . " A left join " . $this->galleryTable . " B on B.value_id = A.value_id
                    left join " . $this->videoTable . "  C on C.value_id = A.value_id
                     where A.entity_id=" . $productId . " and B.media_type='external-video'";
                        }
                        $videoData = $this->db->query($query)->fetchAll();
                        foreach ($globalVideoArray as $key => $videoArray) {
                            foreach ($videoArray as $video) {
                                $videoUrl = $video;
                                foreach ($videoData as $k => $value) {
                                    if ($key == 0) {
                                        if ($value['url'] == $videoUrl) {
                                            $updateQuery = "UPDATE " . $this->galleryEntitytable . " SET disabled=1 WHERE value_id=" . $value['value_id'] . " AND store_id=" . $value['store_id'];
                                            $this->db->query($updateQuery);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $iceCatLogArray['duration'] = time() - $started;
                $iceCatLogArray['ended'] = date('Y-m-d H:i:s');
                $iceCatLogArray['imported_record'] = count(array_unique($successProducts));
                $iceCatLogArray['unsuccessful_record'] = count(array_unique($errorProductIds)) + count(array_unique($productWithOutGtinAndProductCodeAndBrandCode));
                $iceCatLogArray['product_ids'] = implode(',', array_unique($errorProductIds));
                $iceCatLogArray['product_ids_with_missing_gtin_product_code'] = implode(',', array_unique($productWithOutGtinAndProductCodeAndBrandCode));
                $iceCatLogArray['error_log'] = json_encode($errorLog);
                $iceCatLogArray['schedule_unique_id'] = $uniqueScheduledId;

                // Delete one by one
                $this->db->delete($this->table, ['job_id IN (?)' => $job['job_id']]);

                $this->db->insert($this->logTable, $iceCatLogArray);
                $this->logRecord['processed_jobs'] += 1;
            } catch (\Exception $e) {
                $logMessage = date('c') . ' ERROR: ' . get_class($e) . ':
                    ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() .
                    "\nStack trace:\n" . $e->getTraceAsString();

                $this->db->update($this->table, [
                    'pid' => null,
                    'retries' => new Zend_Db_Expr('retries + 1'),
                    'error_log' => $logMessage,
                ], ['job_id IN (?)' => $job['job_id']]);

                if (php_sapi_name() === 'cli') {
                    $this->output->writeln($logMessage);
                }
            }
        }
    }

    private function errorMessageResponse($response, $product)
    {
        $brandname = '';
        $productcode = '';
        $gtin = '';
        $gtincode=$this->data->getGTINCode();
        $brandcode=$this->data->getBrandCode();
        $product_att_code=$this->data->getProductCode();
        if (!empty($product->getData($gtincode))) {
            $productcode=$product->getData($gtincode);
        }

        if (!empty($product->getData($brandcode))) {
            $brandname=$product->getData($brandcode);
        }

        if (!empty($product->getData($product_att_code))) {
            $gtin=$product->getData($product_att_code);
        }
        switch ($response['Code']) {
            case '400':
                $message                = 'The GTIN can not be found';
                return[
                    'message'           => $message,
                    'gtin'              => $gtin,
                    'brand'             => $brandname,
                    'product_code'      => $productcode
                ];
                break;
            case '404':
                $message                = 'The requested product is not present in the Icecat database';
                return[
                    'message'           => $message,
                    'gtin'              => $gtin,
                    'brand'             => $brandname,
                    'product_code'      => $productcode
                ];
                break;
            case '403':
                $message                = 'Display of content for users with a Full Icecat subscription level will require the use of a server certificate and a dynamic secret phrase. Please, contact your account manager for help with the implementation.';
                return[
                        'message'           => $message,
                        'gtin'              => $gtin,
                        'brand'             => $brandname,
                        'product_code'      => $productcode
                    ];
                break;
            default:
                return[
                    'message'           => $response['Message'],
                    'gtin'              => $gtin,
                    'brand'             => $brandname,
                    'product_code'      => $productcode
                ];
                break;
        }
    }

    private function clearOldFailingJobs()
    {
        // $retryLimit = 3;

        // if ($retryLimit > 0) {
        //     $where = $this->db->quoteInto('retries >= ?', $retryLimit);
        //     $this->archiveFailedJobs($where);
        //     $this->db->delete($this->table, 'retries > max_retries');
        //     return;
        // }

        $this->archiveFailedJobs('retries > max_retries');
        $this->db->delete($this->table, 'retries > max_retries');
    }

    /**
     * @param string $whereClause
     */
    private function archiveFailedJobs($whereClause)
    {
        $select = $this->db->select()
            ->from($this->table, ['pid', 'data', 'error_log', 'data_size', 'schedule_unique_id', 'NOW()'])
            ->where($whereClause);

        $query = $this->db->insertFromSelect(
            $select,
            $this->archiveTable,
            ['pid', 'data', 'error_log', 'data_size', 'schedule_unique_id', 'created_at']
        );

        $this->db->query($query);
    }

    /**
     * @param int $maxJobs
     *
     * @throws Exception
     *
     * @return mixed
     *
     */
    private function getJobs($maxJobs)
    {
        try {
            $this->db->beginTransaction();
            $jobs = $this->fetchJobs($maxJobs);
            $this->lockJobs($jobs);
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

        return $jobs;
    }

    /**
     * @param $jobLimit
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    private function fetchJobs($jobLimit)
    {
        $query = $this->db->select()
            ->from($this->table, '*')
            ->limit($jobLimit)
            ->order('job_id ASC');
        return $this->db->query($query)->fetchAll();
    }

    private function lockJobs(array $jobs)
    {
        $jobsIds = $this->getJobsIds($jobs);

        if ($jobsIds !== []) {
            $pid = getmypid();
            $this->db->update($this->table, [
                'locked_at' => date('Y-m-d H:i:s'),
                'pid' => $pid,
            ], ['job_id IN (?)' => $jobsIds]);
        }
    }

    private function getJobsIds(array $jobs)
    {
        $jobsIds = [];
        foreach ($jobs as $job) {
            $jobsIds[] = $job['job_id'];
        }
        return $jobsIds;
    }

    /**
     * @return int|void
     * @throws Zend_Db_Statement_Exception
     */
    public function getPendingQueueJobs()
    {
        $query = $this->db->select()
            ->from($this->table, '*');
        $data = $this->db->query($query)->fetchAll();
        return count($data);
    }

    /**
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getStatistics($uniqueScheduledId)
    {
        $query = $this->db->select()
            ->from($this->logTable, '*')
            ->order('job_id ASC')
            ->where('schedule_unique_id=?', $uniqueScheduledId);
        return $this->db->query($query)->fetchAll();
    }

    public function getErrorRecords($uniqueScheduledId)
    {
        $query = $this->db->select()
            ->from($this->archiveTable, '*')
            ->order('id ASC')
            ->where('schedule_unique_id=?', $uniqueScheduledId);
        return $this->db->query($query)->fetchAll();
    }

    public function getSchedulerId()
    {
        $query = $this->db->select()
            ->from($this->schedulerTable, '*')
            ->limit(1)
            ->order('ended DESC');
        return $this->db->query($query)->fetch();
    }

    public function getTimezone()
    {
        $timezone = $this->data->getTimezone();
        return $timezone;
    }
}
