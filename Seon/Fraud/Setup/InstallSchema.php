<?php

namespace Seon\Fraud\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class InstallSchema implements InstallSchemaInterface {

    /**
     * Upgrades DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context) {
        $setup->startSetup();

        //if (version_compare($context->getVersion(), '1.0.1') < 0) {            
        $orderTable = 'sales_order';
        $orderGridTable = 'sales_order_grid';

        //Order table
        $setup->getConnection()->addColumn(
                $setup->getTable($orderTable), 'seon_transaction_id', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null,
            'nullable' => true,
            'comment' => 'Seon API Transaction ID'
                ]
        )->addColumn(
                $setup->getTable($orderTable), 'proxy_score', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null,
            'nullable' => true,
            'comment' => 'Seon API Proxy Score'
                ]
        )->addColumn(
                $setup->getTable($orderTable), 'is_fraud', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null,
            'nullable' => true,
            'comment' => 'Seon API Is Fraud'
                ]
        )->addColumn(
                $setup->getTable($orderTable), 'fraud_score', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null,
            'nullable' => true,
            'comment' => 'Seon API Fraud Score'
                ]
        );

        //Order Grid table
        $setup->getConnection()->addColumn(
                $setup->getTable($orderGridTable), 'is_fraud', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null,
            'nullable' => true,
            'comment' => 'Seon API Is Fraud'
                ]
        )->addColumn(
                $setup->getTable($orderGridTable), 'fraud_score', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null,
            'nullable' => true,
            'comment' => 'Seon API Fraud Score'
                ]
        );
        //}
        $setup->endSetup();
    }

}
