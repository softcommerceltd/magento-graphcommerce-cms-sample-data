<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\GraphCommerceCmsSampleData\Model;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Magento\CatalogUrlRewrite\Model\CategoryUrlRewriteGenerator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\SampleData\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Psr\Log\LoggerInterface;
use SoftCommerce\Core\Model\Store\WebsiteStorageInterface;
use function array_combine;
use function array_keys;
use function array_shift;
use function file_exists;

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
     * @var UrlFinderInterface
     */
    private UrlFinderInterface $urlFinder;

    /**
     * @var WebsiteStorageInterface
     */
    private WebsiteStorageInterface $websiteStorage;

    /**
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CategoryResource $categoryResource
     * @param LoggerInterface $logger
     * @param UrlFinderInterface $urlFinder
     * @param WebsiteStorageInterface $websiteStorage
     * @param Context $sampleDataContext
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        CategoryResource $categoryResource,
        LoggerInterface $logger,
        UrlFinderInterface $urlFinder,
        WebsiteStorageInterface $websiteStorage,
        Context $sampleDataContext,
        StoreManagerInterface $storeManager
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->categoryResource = $categoryResource;
        $this->logger = $logger;
        $this->urlFinder = $urlFinder;
        $this->websiteStorage = $websiteStorage;
        parent::__construct($sampleDataContext, $storeManager);
    }

    /**
     * @param array $fixtures
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
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
                $categoryId = $this->getCategoryIdByUrl($row['url_key'], $storeId);

                if (!$categoryId) {
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
     * @param string $categoryUrl
     * @param int $storeId
     * @return int
     */
    private function getCategoryIdByUrl(string $categoryUrl, int $storeId): int
    {
        if ($storeId === 0) {
            $storeId = $this->websiteStorage->getDefaultStoreId();
        }

        $result = $this->urlFinder->findOneByData(
            [
                UrlRewrite::STORE_ID => $storeId,
                UrlRewrite::REQUEST_PATH => $categoryUrl,
                UrlRewrite::ENTITY_TYPE => CategoryUrlRewriteGenerator::ENTITY_TYPE,
                UrlRewrite::REDIRECT_TYPE => 0
            ]
        );

        return (int) $result?->getEntityId();
    }
}
