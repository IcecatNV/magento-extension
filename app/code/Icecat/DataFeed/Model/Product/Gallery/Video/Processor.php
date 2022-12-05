<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Model\Product\Gallery\Video;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Gallery\CreateHandler;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Mime;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Magento\MediaStorage\Helper\File\Storage\Database;

class Processor extends \Magento\Catalog\Model\Product\Gallery\Processor
{
    const PERMISSION_CODE_FOR_FILE = 0777;

    private CreateHandler $createHandler;
    private DirectoryList $directoryList;
    private File $file;
    private Product $product;

    /**
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param Database $fileStorageDb
     * @param Config $mediaConfig
     * @param Filesystem $filesystem
     * @param Gallery $resourceModel
     * @param CreateHandler $createHandler
     * @param DirectoryList $directoryList
     * @param File $file
     * @param Product $product
     * @param Mime|null $mime
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        ProductAttributeRepositoryInterface $attributeRepository,
        Database $fileStorageDb,
        Config $mediaConfig,
        Filesystem $filesystem,
        Gallery $resourceModel,
        CreateHandler $createHandler,
        DirectoryList $directoryList,
        File $file,
        Product $product,
        Mime $mime = null
    ) {
        parent::__construct($attributeRepository, $fileStorageDb, $mediaConfig, $filesystem, $resourceModel, $mime);
        $this->createHandler = $createHandler;
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->product = $product;
    }

    /**
     * @param Product $product
     * @param array $videoData
     * @param null $mediaAttribute
     * @param bool $move
     * @param bool $exclude
     * @param null $mediaTmpDiretory
     * @return boolean
     * @throws LocalizedException
     */
    public function addVideo(
        $productId,
        array $videoData,
        $storeId,
        $mediaAttribute = null,
        $move = false,
        $exclude = true,
        $mediaTmpDiretory = null
    ) {
        if (!empty($videoData['thumbnail_url'])) {
            $saveDir = $this->getVideoThumbnailSaveDir();
            /** @var string $newFileName */
            $newFileName = $saveDir . $productId . '_' . $storeId . '_' . baseName($videoData['thumbnail_url']);
            $finalName = $productId . '_' . $storeId . '_' . baseName($videoData['thumbnail_url']);
            /** read file from URL and copy it to the new destination */
            $result = $this->file->read($videoData['thumbnail_url'], $newFileName);
            $result = $this->file->chmod($newFileName, self::PERMISSION_CODE_FOR_FILE);
        } else {
            $imageHelper = \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Catalog\Helper\Image::class);
            $placeholder = $imageHelper->getDefaultPlaceholderUrl('image');
            $saveDir = $this->getVideoThumbnailSaveDir();
            /** @var string $newFileName */
            $newFileName = $saveDir . baseName($placeholder);
            $finalName = baseName($placeholder);
            /** read file from URL and copy it to the new destination */
            $result = $this->file->read($placeholder, $newFileName);
            $result = $this->file->chmod($newFileName, self::PERMISSION_CODE_FOR_FILE);
        }

        $product = $this->product->load($productId);
        $product->setStoreId($storeId);

        $attrCode = $this->getAttribute()->getAttributeCode();
        $mediaGalleryData = $product->getData($attrCode);
        $position = 0;
        if (!is_array($mediaGalleryData)) {
            $mediaGalleryData = ['images' => []];
        }
        foreach ($mediaGalleryData['images'] as &$image) {
            if (isset($image['position']) && $image['position'] > $position) {
                $position = $image['position'];
            }
        }
        $position++;
        $mediaGalleryData['images'][] = array_merge([
            'file' => $finalName,
            'label' => $videoData['video_title'],
            'position' => $position,
            'disabled' => (int)$exclude
        ], $videoData);

        $product->setData($attrCode, $mediaGalleryData);
        if ($mediaAttribute !== null) {
            $product->setMediaAttribute($product, $mediaAttribute, $finalName);
        }
        
        
        // $this->createHandler->execute($product);
        $product->save();
        return $finalName;
    }

    /**
     * Media directory name for the temporary file storage
     * pub/media/tmp
     *
     * @return string
     */
    private function getVideoThumbnailSaveDir()
    {
        return $this->directoryList->getPath(DirectoryList::MEDIA) . DIRECTORY_SEPARATOR . 'tmp/catalog/product/';
    }
}
