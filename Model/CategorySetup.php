<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\GraphCommerceCmsSampleData\Model;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\SampleData\Context;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use SoftCommerce\Core\Model\Store\WebsiteStorageInterface;
use function array_combine;
use function array_keys;
use function array_shift;
use function file_exists;

/**
 * Class CategorySetup
 * used to setup sample data for category entity.
 */
class CategorySetup extends AbstractModel
{
    /**
     * @var CategoryRepositoryInterface
     */
    private CategoryRepositoryInterface $categoryRepository;

    /**
     * @var CategoryResource
     */
    private CategoryResource $categoryResource;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var WebsiteStorageInterface
     */
    private WebsiteStorageInterface $websiteStorage;

    /**
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CategoryResource $categoryResource
     * @param LoggerInterface $logger
     * @param WebsiteStorageInterface $websiteStorage
     * @param ResourceConnection $resourceConnection
     * @param Context $sampleDataContext
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        CategoryResource $categoryResource,
        LoggerInterface $logger,
        WebsiteStorageInterface $websiteStorage,
        ResourceConnection $resourceConnection,
        Context $sampleDataContext,
        StoreManagerInterface $storeManager
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->categoryResource = $categoryResource;
        $this->logger = $logger;
        $this->websiteStorage = $websiteStorage;
        parent::__construct($resourceConnection, $sampleDataContext, $storeManager);
    }

    /**
     * @param array $fixtures
     * @return void
     * @throws LocalizedException
     */
    public function install(array $fixtures): void
    {
        foreach ($fixtures as $fileName) {
            $fileName = $this->fixtureManager->getFixture($fileName);
            if (!file_exists($fileName)) {
                continue;
            }

            $rows = $this->csvReader->getData($fileName);
            $header = array_shift($rows);

            foreach ($rows as $row) {
                $row = array_combine($header, $row);

                if (!isset($row['store_code'], $row['url_key'])) {
                    continue;
                }

                $storeId = $this->getStoreIdByCode($row['store_code']);
                if (null === $storeId) {
                    continue;
                }

                if ($storeId === 0) {
                    $storeId = $this->websiteStorage->getDefaultStoreId();
                }

                if (!$categoryId = $this->getCategoryIdByUrlKey($row['url_key'])) {
                    continue;
                }

                try {
                    $category = $this->categoryRepository->get($categoryId, $storeId);
                } catch (NoSuchEntityException $e) {
                    $this->logger->critical($e->getMessage());
                    continue;
                }

                unset($row['store_code']);
                unset($row['url_key']);

                $category->addData($row);

                foreach (array_keys($row) as $attributeCode) {
                    try {
                        $this->categoryResource->saveAttribute($category, $attributeCode);
                    } catch (\Exception $e) {
                        $this->logger->critical($e->getMessage());
                    }
                }
            }
        }
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
}
