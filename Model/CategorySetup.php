<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\GraphCommerceCmsSampleData\Model;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\SampleData\Context;
use Magento\Store\Model\StoreManagerInterface;
use SoftCommerce\Core\Model\Utils\GetEntityMetadataInterface;
use function array_combine;
use function array_shift;
use function file_exists;

/**
 * Class CategorySetup
 * used to setup sample data for category entity.
 */
class CategorySetup extends AbstractModel
{
    /**
     * @var GetEntityMetadataInterface
     */
    private GetEntityMetadataInterface $getEntityMetadata;

    /**
     * @var array
     */
    private array $attributeInMemory = [];

    /**
     * @var array
     */
    private array $tableInMemory = [];

    /**
     * @param GetEntityMetadataInterface $getEntityMetadata
     * @param ResourceConnection $resourceConnection
     * @param Context $sampleDataContext
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        GetEntityMetadataInterface $getEntityMetadata,
        ResourceConnection $resourceConnection,
        Context $sampleDataContext,
        StoreManagerInterface $storeManager
    ) {
        $this->getEntityMetadata = $getEntityMetadata;
        parent::__construct($resourceConnection, $sampleDataContext, $storeManager);
    }

    /**
     * @param array $fixtures
     * @return void
     * @throws LocalizedException
     */
    public function install(array $fixtures): void
    {
        $fileName = $this->fixtureManager->getFixture(current($fixtures) ?: '');
        if (!file_exists($fileName)) {
            return;
        }

        $rows = $this->csvReader->getData($fileName);
        $header = array_shift($rows);
        $linkField = $this->getEntityMetadata->getLinkField(CategoryInterface::class);
        $saveRequest = [];

        foreach ($rows as $row) {
            $row = array_combine($header, $row);

            if (!isset($row['store_code'], $row['url_key'])) {
                continue;
            }

            $storeId = $this->getStoreIdByCode($row['store_code']);
            if (null === $storeId) {
                continue;
            }

            if (!$categoryId = $this->getCategoryIdByUrlKey($row['url_key'])) {
                continue;
            }

            unset($row['store_code']);
            unset($row['url_key']);

            foreach ($row as $attributeCode => $attributeValue) {
                $attribute = $this->getAttributeByCode($attributeCode);
                $attributeId = $attribute['attribute_id'] ?? null;
                $attributeType = $attribute['backend_type'] ?? null;

                if ($attributeId && $attributeType && $entityTable = $this->getEntityTable($attributeType)) {
                    $saveRequest[$entityTable][] = [
                        $linkField => $categoryId,
                        'attribute_id' => $attributeId,
                        'store_id' => $storeId,
                        'value' => $attributeValue
                    ];
                }
            }
        }

        foreach ($saveRequest as $tableName => $data) {
            $this->connection->insertOnDuplicate($tableName, $data);
        }
    }

    /**
     * @param string $code
     * @return array
     */
    private function getAttributeByCode(string $code): array
    {
        if (!isset($this->attributeInMemory[$code])) {
            $select = $this->connection->select()
                ->from($this->connection->getTableName('eav_attribute'), ['attribute_id', 'backend_type'])
                ->where('attribute_code = ?', $code);
            $this->attributeInMemory[$code] = $this->connection->fetchRow($select);
        }

        return $this->attributeInMemory[$code] ?? [];
    }

    /**
     * @param string $urlKey
     * @return int|null
     */
    private function getCategoryIdByUrlKey(string $urlKey): ?int
    {
        $select = $this->connection->select()
            ->from(
                ['ccev' => $this->connection->getTableName('catalog_category_entity_varchar')],
                'entity_id'
            )
            ->joinLeft(
                ['ea' => 'eav_attribute'],
                'ea.attribute_id = ccev.attribute_id'
            )
            ->where('ccev.value = ?', $urlKey)
            ->where('ccev.store_id = ?', 0)
            ->where('ea.attribute_code = ?', 'url_key');

        return ((int) $this->connection->fetchOne($select)) ?: null;
    }

    /**
     * @param string $tableTypeId
     * @return string|null
     */
    private function getEntityTable(string $tableTypeId): ?string
    {
        $entityTable = "catalog_category_entity_$tableTypeId";

        if (isset($this->tableInMemory[$entityTable])) {
            return $entityTable;
        }

        if ($this->connection->isTableExists(
            $this->connection->getTableName($entityTable)
        )) {
            $this->tableInMemory[$entityTable] = $entityTable;
            return $entityTable;
        }

        return null;
    }
}
