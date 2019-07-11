<?php

namespace Rakuten\RakutenPay\Setup;
 
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
 
class InstallSchema implements InstallSchemaInterface
{
    /**
     * RakutenPay orders table name
     */
    const RAKUTENPAY_ORDER = 'rakutenpay_order';
    
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $conn = $setup->getConnection();
        $tableName = $setup->getTable(self::RAKUTENPAY_ORDER);
        if ($conn->isTableExists($tableName) != true) {
            $table = $conn->newTable($tableName)
                ->addColumn(
                    'entity_id',
                    Table::TYPE_INTEGER,
                    11,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                    ],
                    'Entity ID'
                )
                ->addColumn(
                    'order_id',
                    TABLE::TYPE_INTEGER,
                    11,
                    [],
                    'Order id'
                )
                ->addColumn(
                    'charge_uuid',
                    Table::TYPE_TEXT,
                    80,
                    [],
                    'Charge Uuid'
                )
                ->addColumn(
                    'environment',
                    Table::TYPE_TEXT,
                    40,
                    [],
                    'Environment'
                )
                ->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                    'Created At'
                )
                ->addColumn(
                    'updated_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                    'Updated At'
                )
                ->setComment('RakutenPay Orders Table')
                ->setOption('type', 'InnoDB')
                ->setOption('charset', 'utf8');
            $conn->createTable($table);
        }
        $setup->endSetup();
        
    }

    /**
     * @param $setup
     */
    private function createRakutenPayOrderTable($setup)
    {
        $tableName = $setup->getTable(self::RAKUTENPAY_ORDER);
        if ($setup->getConnection()->isTableExists($tableName) != true) {
            $table = $setup->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    'entity_id',
                    Table::TYPE_INTEGER,
                    11,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                    ],
                    'Entity ID'
                )
                ->addColumn(
                    'order_id',
                    TABLE::TYPE_INTEGER,
                    11,
                    [],
                    'Order id'
                )
                ->addColumn(
                    'charge_uuid',
                    Table::TYPE_TEXT,
                    80,
                    [],
                    'Charge Uuid'
                )
                ->addColumn(
                    'environment',
                    Table::TYPE_TEXT,
                    40,
                    [],
                    'Environment'
                )
                ->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                    'Created At'
                )->addColumn(
                    'updated_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                    'Updated At'
                )
                ->setComment('RakutenPay Orders Table')
                ->setOption('type', 'InnoDB')
                ->setOption('charset', 'utf8');
            $setup->getConnection()->createTable($table);
        }
    }
}
