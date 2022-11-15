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
        $this->config = $config;
        $this->processor = $processor;
        $this->galleryEntitytable = $resourceConnection->getTableName('catalog_product_entity_media_gallery_value');
        $this->galleryTable = $resourceConnection->getTableName('catalog_product_entity_media_gallery');
        $this->db = $objectManager->create(ResourceConnection::class)->getConnection('core_write');
        $this->columnExists = $resourceConnection->getConnection()->tableColumnExists('catalog_product_entity_media_gallery_value', 'entity_id');
    }

    public function execute()
    {
        $productId = $this->_request->getParam('id');
        try {
            $icecatStores = $this->data->getIcecatStoreConfig();
            $storeArray = explode(',', $icecatStores);
            $storeArrayForImage = explode(',', $icecatStores);
            //$storeArray[] = 0; // Admin store
            $storeArrayForImage[] = 0; // Admin store
            $updatedStore = [];
            $errorMessage = null;
            $globalImageArray = [];
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
                    if (!empty($response) && !empty($response['Code'])) {
                        $errorMessage = $response['Message'];
                    } else {
                        $globalImageArray = $this->iceCatUpdateProduct->updateProductWithIceCatResponse($product, $response, $store, $globalImageArray);
                        $storeData = $this->storeRepository->getById($store);
                        $updatedStore[] = $storeData->getName();
                    }
                } else {
                    $this->messageManager->addErrorMessage('There is no matching criteria - GTIN or Brand Name & Product Code values are empty.');
                    $result = ['success'=>0,'message'=>'There is no matching criteria - GTIN or Brand Name & Product Code values are empty.'];
                    break;
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
            if (count($updatedStore) > 0) {
                //$this->messageManager->addSuccessMessage('Product updated successfully on ' . str_replace(", Admin", "", implode(' , ', $updatedStore)));
                $result = ['success'=>1,'message'=>'Product updated successfully on ' . str_replace(", Admin", "", implode(' , ', $updatedStore))];
            } elseif (!empty($errorMessage)) {
                //$this->messageManager->addErrorMessage($errorMessage);
                $result = ['success'=>0,'message'=>$errorMessage];
            }
            $this->getResponse()->setBody(json_encode($result));
            //return $this->_redirect('catalog/product/edit', ['id' => $productId, '_current' => true]);
        } catch (NoSuchEntityException $noSuchEntityException) {
        }
    }
}
