<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Controller\Adminhtml\Index;

use Icecat\DataFeed\Helper\Data;
use Icecat\DataFeed\Service\IcecatApiService;
use Icecat\DataFeed\Model\IceCatUpdateProduct;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\StoreRepositoryInterface;
use \Icecat\DataFeed\Model\ProductAttachmentFactory;

class DeleteAttachment extends Action
{
    private Data $data;
    private IcecatApiService $icecatApiService;
    private ProductRepository $productRepository;
    private IceCatUpdateProduct $iceCatUpdateProduct;
    private StoreRepositoryInterface $storeRepository;
    private ProductAttachmentFactory $productAttachment;

    /**
     * @param Context $context
     * @param Data $data
     * @param IcecatApiService $icecatApiService
     * @param ProductRepository $productRepository
     * @param IceCatUpdateProduct $iceCatUpdateProduct
     */
    public function __construct(
        Context                  $context,
        Data                     $data,
        IcecatApiService         $icecatApiService,
        ProductRepository        $productRepository,
        IceCatUpdateProduct      $iceCatUpdateProduct,
        StoreRepositoryInterface $storeRepository,
        ProductAttachmentFactory $productAttachment
    ) {
        parent::__construct($context);
        $this->data = $data;
        $this->icecatApiService = $icecatApiService;
        $this->productRepository = $productRepository;
        $this->iceCatUpdateProduct = $iceCatUpdateProduct;
        $this->storeRepository = $storeRepository;
        $this->_productAttachment = $productAttachment;
    }

    public function execute()
    {
        $attachmentId = $this->_request->getParam('id');
        try {
            $model = $this->_productAttachment->create();
            $model->load($attachmentId);
            $productId = $model->getData()['product_id'];
            $model->delete();
            return $this->_redirect('catalog/product/edit', ['id' => $productId, '_current' => true]);
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
    }
}
