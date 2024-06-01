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
use SoftCommerce\GraphCommerceCms\Setup\Patch\Data\InstallCategoryRowContentAttribute;

/**
 * Class ModifyCategorySampleData
 * used to modify category sample data.
 */
class ModifyCategorySampleData implements DataPatchInterface, PatchVersionInterface
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
     * @inheritdoc
     */
    public function apply(): void
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
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [
            InstallPageBuilderContentSampleData::class,
            InstallCategoryRowContentAttribute::class
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getVersion(): string
    {
        return '2.0.0';
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
