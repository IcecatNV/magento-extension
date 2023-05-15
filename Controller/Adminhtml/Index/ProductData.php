<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Controller\Adminhtml\Index;

use Icecat\DataFeed\Helper\Data;
use Icecat\DataFeed\Model\IceCatUpdateProduct;
use Icecat\DataFeed\Service\IcecatApiService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\Product\Gallery\Processor;
use Magento\Catalog\Model\ProductRepository;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\Data\GroupInterfaceFactory;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\ResourceModel\Group as GroupResource;
use Magento\Store\Model\StoreManagerInterface;

class ProductData extends Action
{
    private Data $data;
    private IcecatApiService $icecatApiService;
    private ProductRepository $productRepository;
    private IceCatUpdateProduct $iceCatUpdateProduct;
    private StoreRepositoryInterface $storeRepository;
    private StoreManagerInterface $storeManager;
    private Processor $processor;

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

    /**
     * @var AdapterInterface
     * */
    private $db;

    private $columnExists;

    /**
     * @var Config|ConfigInterface
     */
    private $config;

    /**
     * @param Context $context
     * @param Data $data
     * @param IcecatApiService $icecatApiService
     * @param ProductRepository $productRepository
     * @param IceCatUpdateProduct $iceCatUpdateProduct
     * @param StoreRepositoryInterface $storeRepository
     * @param StoreManagerInterface $storeManager
     * @param Processor $processor
     * @param ResourceConnection $resourceConnection
     * @param ObjectManagerInterface $objectManager
     * @param ConfigInterface $config
     */
    public function __construct(
        Context                  $context,
        Data                     $data,
        IcecatApiService         $icecatApiService,
        ProductRepository        $productRepository,
        IceCatUpdateProduct      $iceCatUpdateProduct,
        StoreRepositoryInterface $storeRepository,
        StoreManagerInterface    $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        ConfigInterface $config,
        Processor $processor,
        ResourceConnection $resourceConnection,
        ObjectManagerInterface $objectManager

    ) {
        parent::__construct($context);
        $this->data = $data;
        $this->icecatApiService = $icecatApiService;
        $this->productRepository = $productRepository;
        $this->iceCatUpdateProduct = $iceCatUpdateProduct;
        $this->storeRepository = $storeRepository;
        $this->storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->config = $config;
        $this->processor = $processor;
        $this->galleryEntitytable = $resourceConnection->getTableName('catalog_product_entity_media_gallery_value');
        $this->galleryTable = $resourceConnection->getTableName('catalog_product_entity_media_gallery');
        $this->videoTable = $resourceConnection->getTableName('catalog_product_entity_media_gallery_value_video');
        $this->db = $objectManager->create(ResourceConnection::class)->getConnection('core_write');

        $this->columnExists = $resourceConnection->getConnection()->tableColumnExists('catalog_product_entity_media_gallery_value', 'entity_id');
    }

    public function execute()
    {
        $response = $this->data->getUserSessionId();
        $configurationSelectedStores = explode(",", $this->_scopeConfig->getValue('datafeed/icecat_store_config/stores', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
        $configWebsiteId = [];
        foreach ($configurationSelectedStores as $configurationSelectedStore) {
            $configWebsiteId[] = (int)$this->storeManager->getStore($configurationSelectedStore)->getWebsiteId();
        }
        $confidWebsiteIds = array_unique($configWebsiteId);
        $productId = $this->_request->getParam('id');
        try {
            if(!empty($response) && array_key_exists("Code",$response) ) {
                $result = ['success'=>0,'message'=>$response['Message']];
                $this->getResponse()->setBody(json_encode($result));
            } else {
                $icecatStores = $this->data->getIcecatStoreConfig();
                $storeArray = explode(',', $icecatStores);
                $storeArrayForImage = explode(',', $icecatStores);
                $storeArrayForImage[] = 0; // Admin store
                $globalMediaArray =[];
                $updatedStore = [];
                $errorMessage = null;
                $globalImageArray = [];
                $globalVideoArray = [];
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $product = $objectManager->create('Magento\Catalog\Model\Product')->load($productId);
                $productWebsiteIds = $product->getWebsiteIds();
                $storeDifferencess = array_diff($confidWebsiteIds, $productWebsiteIds);
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
                        $icecatRootCategoryExist = $this->_scopeConfig->getValue('datafeed/icecat/root_category_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                        if(empty($icecatRootCategoryExist))
                        {
                            $this->config->saveConfig('datafeed/icecat/root_category_id', $icecatCid, 'default', 0);
                        }
                    }

                    $allstores = $this->storeRepository->getList();
                    foreach ($allstores as $eachstore) {
                        if ($eachstore->getCode() == 'admin') {
                            continue;
                        }
                        $allstoreArr[] = $eachstore->getId();
                    }
                    if (empty($storeDifferencess)) {
                        foreach ($configurationSelectedStores as $eachstore) {
                            $storeData = $this->storeRepository->getById($eachstore);
                            $storeManager = $objectManager->get(StoreManagerInterface::class);
                            $storeGroup = $objectManager->get(GroupInterfaceFactory::class)->create()->load($storeData->getData('group_id'));
                            if (in_array($eachstore, $storeArray)) {
                                $storeGroup->setRootCategoryId($icecatCid);
                            } else {
                                $storeGroup->setRootCategoryId(2);
                            }
                            $objectManager->create(GroupResource::class)->save($storeGroup);
                        }
                    } else {
                        $logger = \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class);
                        $logger->info("ProductID :".$productId." does not exist in the website's: ". json_encode($storeDifferencess));
                    }

                }

                foreach ($storeArray as $store) {
                    $product = $this->productRepository->getById($productId, false, $store);
                    $language = $this->data->getStoreLanguage($store);
                    $icecatUri = $this->data->getIcecatUri($product, $language);
                    if ($icecatUri) {
                        $response = $this->icecatApiService->execute($icecatUri);
                        if (!empty($response) && !empty($response['Code'])) {
                            $errorMessage = $response['Message'];
                        } else {
                            $globalMediaArray = $this->iceCatUpdateProduct->updateProductWithIceCatResponse($product, $response, $store, $globalMediaArray);
                            $globalImageArray = array_key_exists('image', $globalMediaArray)?$globalMediaArray['image']:[];
                            $globalVideoArray = array_key_exists('video', $globalMediaArray)?$globalMediaArray['video']:[];
                            $storeData = $this->storeRepository->getById($store);
                            $updatedStore[] = $storeData->getName();
                        }
                    } else {
                        $this->messageManager->addErrorMessage('There is no matching criteria - GTIN or Brand Name & Product Code values are empty.');
                        $result = ['success'=>0,'message'=>'There is no matching criteria - GTIN or Brand Name & Product Code values are empty.'];
                        break;
                    }
                }

                // Hide images from non-required stores
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

                // Hide video from non-required stores
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
                                if ((int)$value['metadata'] != (int)$value['store_id']) {
                                    if ($value['url'] == $videoUrl) {
                                        $updateQuery = "UPDATE " . $this->galleryEntitytable . " SET disabled=1 WHERE value_id=" . $value['value_id'] . " AND store_id =" . $value['store_id'];
                                        $this->db->query($updateQuery);
                                    }
                                }
                            }
                        }
                    }
                }
                

                if (count($updatedStore) > 0) {
                    $result = ['success'=>1,'message'=>'Product updated successfully on ' . str_replace(", Admin", "", implode(' , ', $updatedStore))];
                } elseif (!empty($errorMessage)) {
                    $result = ['success'=>0,'message'=>$errorMessage];
                }
                $this->getResponse()->setBody(json_encode($result));
            } 
        } catch (NoSuchEntityException $noSuchEntityException) {
        }
    }
}
