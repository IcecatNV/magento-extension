<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Controller\Adminhtml\Index;

use Icecat\DataFeed\Helper\Data;
use Icecat\DataFeed\Service\IcecatApiService;
use Icecat\DataFeed\Model\IceCatUpdateProduct;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\Product\Gallery\Processor;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\StoreRepositoryInterface;

class ProductData extends Action
{
    private Data $data;
    private IcecatApiService $icecatApiService;
    private ProductRepository $productRepository;
    private IceCatUpdateProduct $iceCatUpdateProduct;
    private StoreRepositoryInterface $storeRepository;
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
     * @param Context $context
     * @param Data $data
     * @param IcecatApiService $icecatApiService
     * @param ProductRepository $productRepository
     * @param IceCatUpdateProduct $iceCatUpdateProduct
     * @param StoreRepositoryInterface $storeRepository
     * @param Processor $processor
     * @param ResourceConnection $resourceConnection
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        Context                  $context,
        Data                     $data,
        IcecatApiService         $icecatApiService,
        ProductRepository        $productRepository,
        IceCatUpdateProduct      $iceCatUpdateProduct,
        StoreRepositoryInterface $storeRepository,
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
            $updatedStore = array();
            $errorMessage = null;
            $globalImageArray = [];
            foreach ($storeArrayForImage as $store) {
                if ($this->data->isImportImagesEnabled()) {
                    $product = $this->productRepository->getById($productId, false, $store);
                    $images = $product->getMediaGalleryImages();
                    $mediaTypeArray = array('image', 'small_image', 'thumbnail');
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
                    break;
                }
            }
            if ($this->columnExists === false) {
                $query = "select * from " . $this->galleryEntitytable. " A left join ". $this->galleryTable. " B on B.value_id = A.value_id where A.row_id=".$productId. " and B.media_type='image'";
            } else {
                $query = "select * from " . $this->galleryEntitytable. " A left join ". $this->galleryTable. " B on B.value_id = A.value_id where A.entity_id=".$productId. " and B.media_type='image'";
            }
            $data = $this->db->query($query)->fetchAll();
            foreach ($globalImageArray as $key => $imageArray) {
                foreach ($imageArray as $image) {
                    $imageData = explode('.', $image);
                    $imageName = $imageData[0];
                    foreach ($data as $k => $value) {
                        if ($key != $value['store_id']) {
                            if (strpos($value['value'], $imageName) !== false) {
                                $updateQuery = "UPDATE " . $this->galleryEntitytable . " SET disabled=1 WHERE value_id=" . $value['value_id'] . " AND store_id=".$value['store_id'];
                                $this->db->query($updateQuery);
                            }
                        }
                    }
                }
            }
            if (count($updatedStore) > 0) {
                $this->messageManager->addSuccessMessage('Product updated successfully on ' . str_replace(", Admin", "", implode(' , ', $updatedStore)));
            } elseif (!empty($errorMessage)) {
                $this->messageManager->addErrorMessage($errorMessage);
            }
            return $this->_redirect('catalog/product/edit', ['id' => $productId, '_current' => true]);
        } catch (NoSuchEntityException $noSuchEntityException) {
        }
    }
}
