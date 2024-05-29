<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\GraphCommerceCmsSampleData\Setup\Patch\Schema;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;

/**
 * Class InstallSampleStores
 * used to install sample stores.
 */
class InstallSampleStores implements SchemaPatchInterface, PatchVersionInterface
{
    /**
     * @var SchemaSetupInterface
     */
    private SchemaSetupInterface $schemaSetup;

    /**
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(SchemaSetupInterface $schemaSetup)
    {
        $this->schemaSetup = $schemaSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(): void
    {
        $this->schemaSetup->startSetup();
        // $connection = $this->schemaSetup->getConnection();

        // $this->handleDefaultStore($connection);
        // $this->handleSecondaryStore($connection);

        $this->schemaSetup->endSetup();
    }

    /**
     * @param AdapterInterface $connection
     * @return void
     */
    private function handleDefaultStore(AdapterInterface $connection): void
    {
        $select = $connection->select()
            ->from(
                ['store' => $this->schemaSetup->getTable('store')],
                'store_id'
            )
            ->joinLeft(
                ['group' => $this->schemaSetup->getTable('store_group')],
                'store.store_id = group.default_store_id',
            )
            ->where('group.website_id = ?', 1);

        if (!$storeId = $connection->fetchOne($select)) {
            return;
        }

        $connection->update(
            $this->schemaSetup->getTable('store'),
            [
                'code' => 'en',
                'name' => 'EN Store View',
                'sort_order' => 0,
                'is_active' => 1
            ],
            ['store_id = ?' => $storeId]
        );
    }

    /**
     * @param AdapterInterface $connection
     * @return void
     */
    private function handleSecondaryStore(AdapterInterface $connection): void
    {
        $select = $connection->select()
            ->from($this->schemaSetup->getTable('store'), 'store_id')
            ->where('code = ?', 'de');

        if ($connection->fetchOne($select)) {
            return;
        }

        $connection->insert(
            $this->schemaSetup->getTable('store'),
            [
                'code' => 'de',
                'website_id' => 1,
                'group_id' => 1,
                'name' => 'DE Store View',
                'sort_order' => 1,
                'is_active' => 1
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion(): string
    {
        return '2.0.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases(): array
    {
        return [];
    }
}
