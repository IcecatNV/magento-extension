<?php

namespace Icecat\DataFeed\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class ViewAttachment extends Column
{
    private StoreManagerInterface $storeManager;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param StoreManagerInterface $storeManager
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreManagerInterface $storeManager,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->storeManager = $storeManager;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');
                if (isset($item['attachment_file'])) {
                    $item[$name] = [
                        'edit' => [
                            'href' => $this->storeManager->getStore()->getBaseurl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $item['attachment_file'],
                            'label' => __($item['attachment_file']),
                            'target' => '_blank',
                            'hidden' => false,
                        ]
                    ];
                }
            }
        }
        return $dataSource;
    }
}
