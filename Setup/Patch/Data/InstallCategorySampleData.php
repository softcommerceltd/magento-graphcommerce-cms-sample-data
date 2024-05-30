<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\GraphCommerceCmsSampleData\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\SampleData\Executor;
use SoftCommerce\GraphCommerceCms\Setup\Patch\Data\InstallCategoryRowContentAttribute;
use SoftCommerce\GraphCommerceCmsSampleData\Setup\Installer;

/**
 * Class InstallCategorySampleData
 * used to install category sample data.
 */
class InstallCategorySampleData implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var Executor
     */
    private Executor $executor;

    /**
     * @var Installer
     */
    private Installer $installer;

    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @param Executor $executor
     * @param Installer $installer
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        Executor $executor,
        Installer $installer,
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->executor = $executor;
        $this->installer = $installer;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(): void
    {
        $this->executor->exec($this->installer);
        $this->modifyCategoryData();
    }

    /**
     * @return void
     */
    private function modifyCategoryData(): void
    {
        $connection = $this->moduleDataSetup->getConnection();

        $categoryValueTable = $this->moduleDataSetup->getTable('catalog_category_entity_varchar');

        $select = $connection->select()
            ->from(['ccev' => $categoryValueTable], 'entity_id')
            ->joinLeft(
                ['ea' => 'eav_attribute'],
                'ea.attribute_id = ccev.attribute_id'
            )
            ->where('ccev.value = ?', 'what-is-new')
            ->where('ccev.store_id = ?', 0)
            ->where('ea.attribute_code = ?', 'url_key');

        if (!$categoryId = $connection->fetchOne($select)) {
            return;
        }

        $connection->update(
            $this->moduleDataSetup->getTable('catalog_category_entity'),
            ['position' => 100],
            ['entity_id = ?' => $categoryId]
        );

        $select = $connection->select()
            ->from(['ccev' => $categoryValueTable], 'value_id')
            ->joinLeft(
                ['ea' => 'eav_attribute'],
                'ea.attribute_id = ccev.attribute_id'
            )
            ->where('ccev.value = ?', 'Default Category')
            ->where('ccev.store_id = ?', 0)
            ->where('ea.attribute_code = ?', 'name');

        if (!$valueId = $connection->fetchOne($select)) {
            return;
        }

        $connection->update(
            $categoryValueTable,
            ['value' => 'Shop'],
            ['value_id = ?' => $valueId]
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies(): array
    {
        return [
            InstallSampleStores::class,
            InstallCategoryRowContentAttribute::class
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
