<?php

namespace GenComm\GenPay\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * GenPay orders table name
     */
    const GENPAY_ORDER = 'genpay_order';

    /**
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        if ($installer->tableExists(self::GENPAY_ORDER)) {
            return;
        }

        $installer->startSetup();
        $table = $installer->getConnection()
            ->newTable($installer->getTable(self::GENPAY_ORDER))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                11,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true
                ],
                'ID'
            )
            ->addColumn(
                'entity_id',
                TABLE::TYPE_INTEGER,
                11,
                [
                    'nullable' => false,
                ],
                'Entity ID'
            )
            ->addColumn(
                'charge_uuid',
                Table::TYPE_TEXT,
                80,
                [],
                'Charge Uuid'
            )
            ->addColumn(
                'increment_id',
                Table::TYPE_TEXT,
                80,
                [],
                'Increment ID'
            )
            ->addColumn(
                'status',
                Table::TYPE_TEXT,
                80,
                [],
                'Status'
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
            ->setComment('GenPay Order')
            ->setOption('charset', 'utf8');
        $installer->getConnection()->createTable($table);
        $installer->endSetup();
    }
}
