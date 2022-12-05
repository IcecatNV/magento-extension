<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Ui\Component\Listing\Columns;

use Magento\Ui\Component\Listing\Columns\Column;

class DeleteAttachment extends Column
{
    /**
     * {@inheritdoc}
     * @since 100.1.0
     */
    public function prepareDataSource(array $dataSource)
    {
        $dataSource = parent::prepareDataSource($dataSource);

        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            $item[$this->getData('name')]['edit'] = [
                'href' => $this->context->getUrl(
                    'icecat/index/deleteAttachment',
                    ['id' => $item['id']]
                ),
                'label' => __('Delete'),
                'hidden' => false,
            ];
        }

        return $dataSource;
    }
}
