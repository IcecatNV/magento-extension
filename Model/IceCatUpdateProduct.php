<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Model;

use Icecat\DataFeed\Helper\Data;
use Icecat\DataFeed\Model\Product\Gallery\Video\Processor as VideoProcessor;
use Icecat\DataFeed\Model\ResourceModel\ProductAttachment\CollectionFactory;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Gallery\Processor;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Store\Model\StoreManagerInterface;
use Icecat\DataFeed\Model\AttributeCodes;

class IceCatUpdateProduct
{
    private ProductResourceModel $productResource;
    private DirectoryList $directoryList;
    private Processor $processor;
    private Data $data;
    private VideoProcessor $videoGalleryProcessor;
    private StoreManagerInterface $storeManager;
    protected CategoryFactory $categoryFactory;
    protected CategoryLinkManagementInterface $categoryLinkManagement;
    protected $_productAttachment;
    protected $_productReview;
    protected $_productAttachmentCollection;
    protected $_productReviewCollection;
    private File $file;
    private $moduleDataSetup;
    public $globalMediaArray;
    private $resultRedirect;
    private $_scopeConfig;

    /**
     * @param ProductResourceModel $productResource
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param DirectoryList $directoryList
     * @param VideoProcessor $processor
     * @param VideoProcessor $videoGalleryProcessor
     * @param StoreManagerInterface $storeManager
     * @param CategoryFactory $categoryFactory
     * @param CategoryLinkManagementInterface $categoryLinkManagementInterface
     * @param Data $data
     * @param ResultFactory $result
     * @param ProductAttachmentFactory $productAttachment
     * @param ResourceModel\ProductAttachment\CollectionFactory $productAttachmentCollection
     * @param ProductReviewFactory $productReview
     * @param ResourceModel\ProductReview\CollectionFactory $productReviewCollection
     * @param File $file
     */
    public function __construct(
        ProductResourceModel                          $productResource,
        ModuleDataSetupInterface                      $moduleDataSetup,
        DirectoryList                                 $directoryList,
        Processor                                     $processor,
        VideoProcessor                                $videoGalleryProcessor,
        StoreManagerInterface                         $storeManager,
        CategoryFactory                               $categoryFactory,
        CategoryLinkManagementInterface               $categoryLinkManagementInterface,
        Data                                          $data,
        ResultFactory                                 $result,
        ProductAttachmentFactory                      $productAttachment,
        CollectionFactory                             $productAttachmentCollection,
        ProductReviewFactory                          $productReview,
        ResourceModel\ProductReview\CollectionFactory $productReviewCollection,
        File                                          $file,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->data = $data;
        $this->productResource = $productResource;
        $this->directoryList = $directoryList;
        $this->processor = $processor;
        $this->videoGalleryProcessor = $videoGalleryProcessor;
        $this->storeManager = $storeManager;
        $this->categoryFactory = $categoryFactory;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->categoryLinkManagement = $categoryLinkManagementInterface;
        $this->resultRedirect = $result;
        $this->_productAttachment = $productAttachment;
        $this->_productAttachmentCollection = $productAttachmentCollection;
        $this->_productReview = $productReview;
        $this->_productReviewCollection = $productReviewCollection;
        $this->file = $file;
        $this->_scopeConfig = $scopeConfig;
    }

    public function updateProductWithIceCatResponse(Product $product, $response, $storeId, $globalMediaArray)
    {
        $tmpMediaDir = $this->getMediaDir();
        if (!file_exists($tmpMediaDir . "/tmp")) {
            mkdir($tmpMediaDir . "/tmp", 0755, true);
        }

        $contentToken = $this->data->getContentToken();
        $userType = $this->data->getUserType();

        $product->setStoreId($storeId);
        $product->setData('icecat_icecat_id', $response['data']['GeneralInfo']['IcecatId']);

        $attributeForMapping = $this->data->getProductAttributes();

        $productData = $response['data'];
        if (!empty($attributeForMapping)) {
            $attributeArray = [];
            foreach ($attributeForMapping as $attributeMapping) {
                $attributeArray[$attributeMapping['attribute']] = explode('-', $attributeMapping['icecat_fields']);
            }

            foreach ($attributeArray as $key => $value) {
                $productData1 = $response['data'];
                foreach ($value as $a) {
                    $productData1 = $productData1[$a];
                    if (empty($productData1)) {
                        break;
                    }
                }
                $product->setData($key, $productData1);
            }
        }

        if ($this->data->isReasonToBuyEnabled()) {
            $productReasonsToBuy = $productData['ReasonsToBuy'];
            $flag = 'LEFT';
            if (count($productReasonsToBuy) > 0) {
                $reasonsHtml  = '<style>
                                    .row-normal{
                                        display: flex;
                                    }
                                </style>';
                foreach ($productReasonsToBuy as $reasons) {
                    $reasonsHtml .= '<div class = "row-normal" >';
                    if (isset($reasons['HighPic']) && (!empty($reasons['HighPic']))):
                        if ($flag === 'LEFT'):
                            $reasonsHtml .= ' <div class = "content-block"> <h5><b>' . $reasons['Title'] . '</b></h5>';
                            $reasonsHtml .= '<span>' . $reasons['Value'] . '</span></div>';
                            if ($userType == 'full' && !empty($contentToken)) {
                                $reasonsHtml .= ' <div class="image-block"><img class = "image-left" alt="IMAGE-NOT-AVAILABLE" src="' . $reasons['HighPic'] . '?content_token='.$contentToken.'" /></div>';
                            } else {
                                $reasonsHtml .= ' <div class="image-block"><img class = "image-left" alt="IMAGE-NOT-AVAILABLE" src="' . $reasons['HighPic'] . '" /></div>';
                            }
                            $flag = 'RIGHT';
                        else:
                            if ($userType == 'full' && !empty($contentToken)) {
                                $reasonsHtml .= '<div class = "image-block">  <img class = "image-right" alt="IMAGE-NOT-AVAILABLE" src="' . $reasons['HighPic'] . '?content_token='. $contentToken.'" /> </div>';
                            } else {
                                $reasonsHtml .= '<div class = "image-block">  <img class = "image-right" alt="IMAGE-NOT-AVAILABLE" src="' . $reasons['HighPic'] . '" /> </div>';
                            }
                            $reasonsHtml .= '<div class = "content-block"><h5><b>' . $reasons['Title'] . '</b></h5>';
                            $reasonsHtml .= '<span>' . $reasons['Value'] . '</span></div>';

                            $flag = 'LEFT';
                        endif;
                    else:
                        $reasonsHtml .= '<div class = "content-block"> <h5><b>' . $reasons['Title'] . '</b></h5>';
                        $reasonsHtml .= '<span>' . $reasons['Value'] . '</span></div>';
                    endif;
                    $reasonsHtml .= '</div>';
                }
                $reasonsHtml .= '</div>';
                $product->setData(AttributeCodes::ICECAT_PRODUCT_ATTRIBUTE_REASON_TO_BUY, $reasonsHtml);
            } else {
                $product->setData(AttributeCodes::ICECAT_PRODUCT_ATTRIBUTE_REASON_TO_BUY, '');
            }
        }

        if ($this->data->isProductReviewEnabled()) {
            $productReviews = $productData['Reviews'];
            if (count($productReviews) > 0) {
                $this->deleteProductreviewList($storeId, $product->getId());
                foreach ($productReviews as $review) {
                    $reviewDetails     = [
                        'store_id'          => $storeId,
                        'product_id'        => $product->getId(),
                        'source'            => $review['URL'],
                        'description'       => $review['Value'],
                        'score'             => $review['Score']
                    ];
                    $this->createProductReview($reviewDetails);
                }
            }
        }

        if ($this->data->isBulletPointsEnabled() && isset($productData['GeneralInfo']['BulletPoints']['Values'])) {
            $bulletPointsArray = $productData['GeneralInfo']['BulletPoints']['Values'];
            if (count($bulletPointsArray) > 0) {
                $bulletHtml = '<ul>';
                foreach ($bulletPointsArray as $bullet) {
                    $bulletHtml .= '<li>' . $bullet . '</li>';
                }
                $bulletHtml .= '</ul>';
                $product->setData(AttributeCodes::ICECAT_PRODUCT_ATTRIBUTE_BULLET_POINTS, $bulletHtml);
            } else {
                $product->setData(AttributeCodes::ICECAT_PRODUCT_ATTRIBUTE_BULLET_POINTS, '');
            }
        }

        if ($this->data->isProductStoriesEnabled()) {
            $productStory = $productData['ProductStory'];
            if (count($productStory) >0) {
                foreach ($productStory as $data) {
                    if (empty($data['Language'])) {
                        $data['Language'] = 'en';
                    }
                    $data['Language'] = strtolower($data['Language']);
                    $html = "";
                    if ($data['URL'] != "") {
                        $pathinfo = pathinfo($data['URL']);
                        if ($userType == 'full' && !empty($contentToken)) {
                            $html = file_get_contents($data['URL'].'?content_token='. $contentToken);
                        }else{
                            $html = file_get_contents($data['URL']);
                        }
                        $html = preg_replace("/src=\"/", 'src="' . $pathinfo['dirname'] . '/', $html);
                        if ($userType == 'full' && !empty($contentToken)) {
                            $html = preg_replace("/\" alt=\"/", '?content_token='. $contentToken .'" alt="', $html);
                        }
                        $html = preg_replace("/href=\"/", 'href="' . $pathinfo['dirname'] . '/', $html);
                    }
                }
                $product->setData(AttributeCodes::ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_STORIES, $html);
            } else {
                $product->setData(AttributeCodes::ICECAT_PRODUCT_ATTRIBUTE_PRODUCT_STORIES, '');
            }
        }


        if ($this->data->isImportImagesEnabled()) {
            $productImageData = $productData['Gallery'];
            if (count($productImageData) > 0) {
                $i = 0;
                $oldImageName = [];
                $productId = $product->getId();
                $images = $product->getMediaGalleryImages();
                foreach ($productImageData as $imageData) {
                    $image = $imageData['Pic'];
                    $tmpDir = $this->getMediaDirTmpDir();
                    $imageName = $product->getId() . '_' . $storeId . '_' . baseName($image);
                    $imageNameWithoutExtension = substr($imageName, 0, strrpos($imageName, '.'));
                    if (!in_array($imageName, $oldImageName)) {
                        $oldImageName[] = $imageName;
                    } else {
                        continue;
                    }
                    $imgFlag = 0;
                    foreach ($images as $child) {
                        if (strpos($child->getFile(), $imageNameWithoutExtension) !== false) {
                            $imgFlag = 1;
                            continue;
                        }
                    }
                    if($imgFlag == 1){
                        continue;
                    }

                    /** create folder if it is not exists */
                    $newFileName = $tmpDir . $imageName;
                    /** read file from URL and copy it to the new destination */
                    if ($userType == 'full' && !empty($contentToken)) {
                        $result = $this->file->read($image. '?content_token=' .$contentToken, $newFileName);
                    } else {
                        $result = $this->file->read($image, $newFileName);
                    }

                    // Updating file permission of the uploaded file
                    $this->file->chmod($newFileName, 0777);
                    if ($result) {
                        try{
                            $baseImage = $product->getData('image');
                            if ($i == 0 && (empty($baseImage) || $baseImage == "no_selection")) {
                                $product->addImageToMediaGallery($newFileName, ['image', 'small_image', 'thumbnail'], false, false);
                            } else {
                                $product->addImageToMediaGallery($newFileName, [], false, false);
                            }
                            $i++;
                        } catch (\Exception $e) {
                            $this->logger->error('Image issue: ' . $e->getMessage());
                        }
                    }
                    $globalMediaArray['image'][$storeId][] = $imageName;
                }
            }
        }

        if ($this->data->isImportMultimediaEnabled()) {
            $productMultiMediaData = $productData['Multimedia'];
            if (count($productMultiMediaData) > 0) {
                $count = 1;
                $productMedia = $product->getMediaGalleryImages();
                foreach ($productMultiMediaData as $multiMediaData) {
                    if ($multiMediaData['IsVideo']) {
                        if (strpos($multiMediaData['URL'], 'youtube') !== false) {
                            $videoUrl = $multiMediaData['URL'];
                            $headers = get_headers("https://www.youtube.com/oembed?url=$videoUrl");
                            if(strpos($headers[0], '200')) {
                                $videoData = [
                                    'video_id' => $multiMediaData['ID'], //set your video id
                                    'video_title' => $multiMediaData['Description'], //set your video title
                                    'video_description' => $multiMediaData['Description'], //set your video description
                                    'thumbnail' => "image path", //set your video thumbnail path.
                                    'video_provider' => "youtube",
                                    'video_metadata' => $storeId,
                                    'video_url' => $multiMediaData['URL'], //set your youtube channel's video url
                                    'media_type' => \Magento\ProductVideo\Model\Product\Attribute\Media\ExternalVideoEntryConverter::MEDIA_TYPE_CODE,
                                    'thumbnail_url' => ($multiMediaData['ThumbUrl'] ? $multiMediaData['ThumbUrl']: $multiMediaData['PreviewUrl']),
                                    'store_id' => $storeId
                                ];

                                // Add video to the product

                                // Skip Duplicate Video Start
                                $videoFlag = 0;
                                foreach ($productMedia as $child) {
                                    $mageProdVideoData = $child->getData();
                                    if (isset($mageProdVideoData['media_type']) && $mageProdVideoData['media_type'] == 'external-video') {
                                        if ($mageProdVideoData['video_url'] == $videoUrl) {
                                            $videoFlag = 1;
                                            continue;
                                        }
                                    }
                                }
                                if($videoFlag == 1){
                                    continue;
                                }
                                // Skip Duplicate Video End

                                $mediaTmpDiretory = $this->getMediaDirTmpDir();
                                if ($product->hasGalleryAttribute()) {
                                    $videoName = $this->videoGalleryProcessor->addVideo(
                                            $product->getId(),
                                            $videoData,
                                            $storeId,
                                            ['image', 'small_image', 'thumbnail'],
                                            false,
                                            false,
                                            $mediaTmpDiretory
                                        );
                                    $globalMediaArray['video'][$storeId][] = $multiMediaData['URL'];
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($this->data->isImportPdfEnabled()) {
            $productMultiMediaData = $productData['Multimedia'];
            if (count($productMultiMediaData) > 0) {
                $this->deletePdfList($storeId, $product->getId());
                foreach ($productMultiMediaData as $multiMediaData) {
                    if (!$multiMediaData['IsVideo']) {
                        $currentStore   = $this->storeManager->getStore($storeId);
                        $destinationPath = $tmpMediaDir . '/doc/' . $currentStore->getId() . '/' . $product->getId() . '/';
                        if (!file_exists($destinationPath)) {
                            mkdir($destinationPath, 0755, true);
                        }

                        $pdf            = $multiMediaData['URL'];
                        $pdfNameArray   = explode('/', $pdf);
                        $pdfName        = end($pdfNameArray);
                        /** create folder if it is not exists */
                        /** @var string $newFileName */
                        $newFileName    = $destinationPath . baseName($pdf);

                        if($userType == 'full' && !empty($contentToken)){
                            $result = $this->file->read($pdf.'?content_token='. $contentToken, $newFileName);
                        }else{
                            $result = $this->file->read($pdf, $newFileName);
                        }
                        $relativePath   = 'doc/' . $currentStore->getId() . '/' . $product->getId() . '/' . $pdfName;
                        $pdfDetails     = [
                            'product_id'        => $product->getId(),
                            'attachment_file'   => $relativePath,
                            'store_id'          => $storeId,
                            'title'             => $multiMediaData['Description']
                        ];
                        $this->createPdfAttribute($pdfDetails);
                    }
                }
            }
        }

        if ($this->data->isImportSpecificationEnabled()) {
            $allSpecifications = $productData['FeaturesGroups'];
            if (!empty($allSpecifications)) {
                $specificationHtml = '<style>
                .break_cols {
                    display: flex;
                    /* align-items: center; */
                    justify-content: space-between;
                    column-gap: 30px;
                }

                .tableRowHead h3 {
                    background: ' . $this->data->getSpecificationHeaderColor() . ';
                    margin: 0;
                    padding: 5px;
                    font-size: 16px;
                    font-weight: 600;
                }

                .table {
                    border-radius: 5px;
                    border: 1px solid #c5c9e2;
                }

                .inner-data {
                    display: flex;
                    align-items: center;
                    padding: 5px;
                    justify-content: space-between;
                }

                .spec-column {
                    width: 50%;
                }

                .ds_label {
                    width: 50%;
                }

                .ds_data {
                    width: 50%;
                }
            </style>';
                $specificationHtml .= '<div class="container mb-btm">';
                $specificationHtml .= '<div class="panel-body break_cols">';
                $specificationHtml .= '<div class="spec-column">';
                $specificationHtml .= '<div class="table">';
                $specificationHtml .= '<div class="tableRow">';

                $features               = [];
                $counting               = null;
                $specficFeatureCount    = [];
                $featureCount           = 0;

                foreach ($allSpecifications as $allSpecification) {
                    $featureCount = $featureCount + count($allSpecification['Features']);
                    $specficFeatureCount[$allSpecification['FeatureGroup']['Name']['Value']] = $featureCount;
                    $counting = $featureCount;

                    $specifications = $allSpecification['Features'];
                    foreach ($specifications as $specification) {
                        $data1['featureName']         = $specification['Feature']['Name']['Value'];
                        $data1['featureValue']        = $specification['PresentationValue'];
                        $data1['featureDescription']  = $specification['Description'];
                        $data1['mandatory']           = $specification['Mandatory'];
                        $features[$allSpecification['FeatureGroup']['Name']['Value']][] = $data1;
                    }
                }

                $keys           = array_keys($features);
                $counting      += count($keys);
                $halfArray      = $counting / 2;
                $arrayFirst     = [];
                $arraySecond    = [];
                $i              = 0;
                foreach ($features as $key => $value) {
                    foreach ($value as $val) {
                        if ($i < $halfArray) {
                            $arrayFirst[$key][] = $val;
                        } else {
                            $arraySecond[$key][] = $val;
                        }
                        $i++;
                    }
                    $i++;
                }

                foreach ($arrayFirst as $key => $value) {
                    $specificationHtml .= '<div>';
                    $specificationHtml .= '<div class="tableRowHead row">';
                    $specificationHtml .= '<h3>' . $key . '</h3>';
                    $specificationHtml .= '</div>';
                    foreach ($value as $val) {
                        $specificationHtml .= '<div class="row inner-data">';
                        $specificationHtml .= '<div class="ds_label">';
                        $specificationHtml .= '<span title="' . $val['featureDescription'] . '">' . $val['featureName'] . '</span>';
                        $specificationHtml .= (!empty($val['mandatory'])) ? '<span>*</span>' : '';
                        $specificationHtml .= '</div>';
                        $featureStyle       = ($val['featureValue'] == 'Y') ? 'style="color: green;"' : (($val['featureValue'] == 'N') ? 'style="color: red;"' : '');
                        $featureValue       = ($val['featureValue'] == 'Y') ? '&#x2713;' : (($val['featureValue'] == 'N') ? '&#10005;' : $val['featureValue']);
                        $specificationHtml .= '<div class="ds_data" ' . $featureStyle . '>' . $featureValue . '</div>';
                        $specificationHtml .= '</div>';
                    }
                    $specificationHtml .= '</div>';
                }
                $specificationHtml .= '</div>';
                $specificationHtml .= '</div>';
                $specificationHtml .= '</div>';
                $specificationHtml .= '<div class="spec-column">';
                $specificationHtml .= '<div class="table">';
                $specificationHtml .= '<div class="tableRow">';
                foreach ($arraySecond as $key => $value) {
                    $specificationHtml .= '<div>';
                    $specificationHtml .= '<div class="tableRowHead row">';
                    $specificationHtml .= '<h3>' . $key . '</h3>';
                    $specificationHtml .= '</div>';
                    foreach ($value as $val) {
                        $specificationHtml .= '<div class="row inner-data">';
                        $specificationHtml .= '<div class="ds_label">';
                        $specificationHtml .= '<span title="' . $val['featureDescription'] . '">' . $val['featureName'] . '</span>';
                        $specificationHtml .= (!empty($val['mandatory'])) ? '<span>*</span>' : '';
                        $specificationHtml .= '</div>';
                        $featureStyle       = ($val['featureValue'] == 'Y') ? 'style="color: green;"' : (($val['featureValue'] == 'N') ? 'style="color: red;"' : '');
                        $featureValue       = ($val['featureValue'] == 'Y') ? '&#x2713;' : (($val['featureValue'] == 'N') ? '&#10005;' : $val['featureValue']);
                        $specificationHtml .= '<div class="ds_data" ' . $featureStyle . '>' . $featureValue . '</div>';
                        $specificationHtml .= '</div>';
                    }
                    $specificationHtml .= '</div>';
                }
                $specificationHtml .= '</div>';
                $specificationHtml .= '</div>';
                $specificationHtml .= '</div>';
                $specificationHtml .= '</div>';
                $specificationHtml .= '</div>';
                $product->setData(AttributeCodes::ICECAT_PRODUCT_ATTRIBUTE_SPECIFICATION, $specificationHtml);
            } else {
                $product->setData(AttributeCodes::ICECAT_PRODUCT_ATTRIBUTE_SPECIFICATION, "");
            }
        }

        if ($this->data->isImportRelatedProductEnabled()) {
            $relatedProductData = $productData['ProductRelated'];
            if (!empty($relatedProductData) > 0) {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $linkData = [];
                foreach ($relatedProductData as $relatedProduct) {
                    $ProductIcecatProductCode = $relatedProduct['ProductCode'];
                    $ProductIcecatBrand = $relatedProduct['Brand'];
                    $productSku = $product->getSku();
                    $result = $this->searchProductSku($ProductIcecatBrand,$ProductIcecatProductCode);
                    if ($result) {
                        $productLink = $objectManager->create('Magento\Catalog\Api\Data\ProductLinkInterface')
                            ->setSku($productSku)
                            ->setLinkedProductSku($result)
                            ->setLinkType('related');

                        $linkData[] = $productLink;
                    }
                }
                if (count($linkData) > 0) {
                    $product->setProductLinks($linkData);
                }
            }
        }

        $product->setData('icecat_updated_time', date('Y-m-d H:i:s'));
        $this->productResource->save($product);

        if ($this->data->isCategoryImportEnabled() && isset($productData['GeneralInfo']['Category'])) {
            $categoryData = $productData['GeneralInfo']['Category'];
            $isIncludeInMenu = $this->data->isCategoryIncludeInMenuEnabled();
            $category = $this->fetchOrCreateProductCategory($categoryData['Name']['Value'], $categoryData['CategoryID'], $isIncludeInMenu, $storeId);
            if ($category->getId()) {
                $categoryIds[] = $category->getId();
                $categoryIds = array_unique(
                    array_merge(
                        $product->getCategoryIds(),
                        $categoryIds
                    )
                );
                $this->categoryLinkManagement->assignProductToCategories(
                    $product->getSku(),
                    $categoryIds
                );
            }
        }
        return $globalMediaArray;
    }

    public function createProductReview($reviewDetails)
    {
        $model = $this->_productReview->create();
        $model->addData([
            "store_id"          => $reviewDetails['store_id'],
            "product_id"        => $reviewDetails['product_id'],
            "source"            => $reviewDetails['source'],
            "description"       => $reviewDetails['description'],
            "score"             => $reviewDetails['score'],
        ]);
        $saveData = $model->save();
        return $saveData;
    }

    public function createPdfAttribute($pdfDetails)
    {
        $model = $this->_productAttachment->create();
        $model->addData([
            "product_id"        => $pdfDetails['product_id'],
            "attachment_file"   => $pdfDetails['attachment_file'],
            "store_id"          => $pdfDetails['store_id'],
            "title"             => $pdfDetails['title'],
        ]);
        $saveData = $model->save();
        return $saveData;
    }

    public function deletePdfList($storeId, $productId)
    {
        try {
            $collection = $this->_productAttachmentCollection->create()
                ->addFieldToSelect('*')
                ->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('store_id', $storeId);
            if ($collection->getSize() > 0) {
                $collection->walk('delete');
            }
        } catch (\Exception $e) {
            $this->logger->error('Error While Deleting old PDF: ' . $e->getMessage());
        }
    }

    public function deleteProductreviewList($storeId, $productId)
    {
        try {
            $collection = $this->_productReviewCollection->create()
                ->addFieldToSelect('*')
                ->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('store_id', $storeId);

            if ($collection->getSize() > 0) {
                $collection->walk('delete');
            }
        } catch (\Exception $e) {
            $this->logger->error('Error While Deleting old Review: ' . $e->getMessage());
        }
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws LocalizedException
     */
    public function fetchOrCreateProductCategory($categoryName, $categoryId, $includeInMenu, $storeId)
    {
        // get the current stores root category
        $connection = $this->moduleDataSetup->getConnection();
        $table      = $connection->getTableName('core_config_data');
        $category   = $connection->getConnection()
            ->query('SELECT value FROM ' . $table . ' WHERE path = "datafeed/icecat/root_category_id"')
            ->fetch();
        $parentId   = $category['value'];

        $parentCategory = $this->categoryFactory->create()->load($parentId);

        $category = $this->categoryFactory->create();
        $category = $category->getCollection()
            ->addAttributeToFilter('icecat_category_id', $categoryId)
            ->addAttributeToFilter('parent_id', $parentId)
            ->getFirstItem();

        if (!$category->getId()) {
            $category->setPath($parentCategory->getPath())
                ->setParentId($parentId)
                ->setName($categoryName)
                ->setIcecatCategoryId($categoryId)
                ->setIncludeInMenu($includeInMenu)
                ->setIsActive(true);
            try {
                $category->save();
            } catch (\Exception $e) {
                throw new LocalizedException(__($e->getMessage()));
            }
        } else {
            $category->load($category->getId())
                ->setName($categoryName)
                ->setStoreId($storeId);
            try {
                $category->save();
            } catch (\Exception $e) {
                throw new LocalizedException(__($e->getMessage()));
            }
        }

        return $category;
    }

    /**
     * Media directory name for the temporary file storage
     * pub/media/tmp
     *
     * @return string
     */
    private function getMediaDirTmpDir()
    {
        return $this->directoryList->getPath(DirectoryList::MEDIA) . DIRECTORY_SEPARATOR . 'tmp/';
    }

    private function getMediaDir()
    {
        return $this->directoryList->getPath(DirectoryList::MEDIA);
    }

    protected function searchProduct($productIcecatID)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Collection');
        $collection->addFieldToFilter('icecat_icecat_id', ['eq' => $productIcecatID]);
        $productData = $collection->getData();
        if (count($productData) > 0) {
            return $productData[0]['sku'];
        }

        return null;
    }

    protected function searchProductSku($ProductIcecatBrand,$ProductIcecatProductCode)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $brandAttributeCode = $this->_scopeConfig->getValue('datafeed/product_brand_fetch_type/brand', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $productCodeAttributeCode = $this->_scopeConfig->getValue('datafeed/product_brand_fetch_type/product_code', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $collection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Collection');
        $collection->addFieldToFilter($brandAttributeCode, ['eq' => $ProductIcecatBrand]);
        $collection->addFieldToFilter($productCodeAttributeCode, ['eq' => $ProductIcecatProductCode]);
        $productDataSku = $collection->getData();
        if (count($productDataSku) > 0) {
            return $productDataSku[0]['sku'];
        }
        return null;
    }
}
