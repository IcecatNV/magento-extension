<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Controller\Adminhtml\Index;

use Icecat\DataFeed\Helper\Data;
use Icecat\DataFeed\Model\IceCatUpdateProduct;
use Icecat\DataFeed\Model\ProductReviewFactory;
use Icecat\DataFeed\Service\IcecatApiService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ProductRepository;
use Magento\Store\Api\StoreRepositoryInterface;

class DeleteReview extends Action
{
    private Data $data;
    private IcecatApiService $icecatApiService;
    private ProductRepository $productRepository;
    private IceCatUpdateProduct $iceCatUpdateProduct;
    private StoreRepositoryInterface $storeRepository;
    private ProductReviewFactory $productReview;

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
        ProductReviewFactory $productReview
    ) {
        parent::__construct($context);
        $this->data = $data;
        $this->icecatApiService = $icecatApiService;
        $this->productRepository = $productRepository;
        $this->iceCatUpdateProduct = $iceCatUpdateProduct;
        $this->storeRepository = $storeRepository;
        $this->_productReview = $productReview;
    }

    public function execute()
    {
        $attachmentId = $this->_request->getParam('id');
        try {
            $model = $this->_productReview->create();
            $model->load($attachmentId);
            $productId = $model->getData()['product_id'];
            $model->delete();
            return $this->_redirect('catalog/product/edit', ['id' => $productId, '_current' => true]);
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
    }
}
