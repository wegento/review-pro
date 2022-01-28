<?php

namespace WeGento\ReviewPro\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $installer->getConnection()->addColumn(
            $installer->getTable('review_detail'),
            'admin_replay',
            [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment' => 'Admin Replay',
            ]
        );

        $setup->endSetup();
    }
}
