<?php

namespace Icecat\DataFeed\Setup;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

/**
 * Class Uninstall
 */
class Uninstall implements UninstallInterface
{
    private $resourceConnection;

    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }
    public function uninstall(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        $tableName = 'eav_entity_attribute';
        $tableNameForGroup = "eav_attribute";
        $select = $this->resourceConnection->getConnection()->select()
        ->from(
            ['c' => $tableNameForGroup],
            ['attribute_id']
        )
        ->where(
            "c.attribute_code  LIKE '%icecat_%'"
        );
        $selectIds = $this->resourceConnection->getConnection()->fetchAll($select);
        if ($selectIds) {
            foreach ($selectIds as $groupId) {
                $this->resourceConnection->getConnection()->delete($tableName, ["attribute_id = " . $groupId['attribute_id']]);
            }
        }

        $setup->endSetup();
    }
}
