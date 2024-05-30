<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\GraphCommerceCmsSampleData\Setup\Patch\Data;

use Magento\CatalogSampleData\Setup\Patch\Data\InstallCatalogSampleData;
use Magento\CmsSampleData\Setup\Patch\Data\InstallCmsSampleData;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Class InstallSampleStores
 * used to install sample stores.
 */
class InstallSampleStores implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();

        $this->handleDefaultWebsiteStore($connection);
        $this->handleSecondaryStore($connection);
    }

    /**
     * @param AdapterInterface $connection
     * @return void
     */
    private function handleDefaultWebsiteStore(AdapterInterface $connection): void
    {
        $connection->update(
            $this->moduleDataSetup->getTable('store_website'),
            [
                'name' => 'GraphCommerce'
            ],
            ['website_id = ?' => 1]
        );

        $connection->update(
            $this->moduleDataSetup->getTable('store_group'),
            [
                'name' => 'GC Store'
            ],
            ['group_id = ?' => 1]
        );

        $connection->update(
            $this->moduleDataSetup->getTable('store'),
            [
                'code' => 'en',
                'name' => 'English'
            ],
            ['store_id = ?' => 1]
        );
    }

    /**
     * @param AdapterInterface $connection
     * @return void
     */
    private function handleSecondaryStore(AdapterInterface $connection): void
    {
        $select = $connection->select()
            ->from($this->moduleDataSetup->getTable('store'), 'store_id')
            ->where('code = ?', 'de');

        if ($connection->fetchOne($select)) {
            return;
        }

        $connection->insert(
            $this->moduleDataSetup->getTable('store'),
            [
                'code' => 'de',
                'website_id' => 1,
                'group_id' => 1,
                'name' => 'German',
                'sort_order' => 2,
                'is_active' => 1
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies(): array
    {
        return [
            InstallCatalogSampleData::class,
            InstallCmsSampleData::class
        ];
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
