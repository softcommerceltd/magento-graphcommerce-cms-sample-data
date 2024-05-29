<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\GraphCommerceCmsSampleData\Model;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\ResourceModel\Page as ResourcePage;
use Magento\Cms\Model\ResourceModel\Page\Collection;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\SampleData\Context;
use Magento\Store\Model\StoreManagerInterface;
use function array_combine;
use function array_shift;
use function current;
use function file_exists;
use function trim;

class CmsPageSetup extends AbstractModel
{
    /**
     * @var Collection
     */
    private Collection $collection;

    /**
     * @var PageFactory
     */
    private PageFactory $pageFactory;

    /**
     * @var ResourcePage
     */
    private ResourcePage $resource;

    /**
     * @var array|null
     */
    private ?array $pagesInMemory = null;

    /**
     * @param CollectionFactory $collectionFactory
     * @param PageFactory $pageFactory
     * @param ResourcePage $resource
     * @param ResourceConnection $resourceConnection
     * @param Context $sampleDataContext
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        PageFactory $pageFactory,
        ResourcePage $resource,
        ResourceConnection $resourceConnection,
        Context $sampleDataContext,
        StoreManagerInterface $storeManager
    ) {
        $this->collection = $collectionFactory->create();
        $this->pageFactory = $pageFactory;
        $this->resource = $resource;
        parent::__construct($resourceConnection, $sampleDataContext, $storeManager);
    }

    /**
     * @param array $fixtures
     * @return void
     * @throws AlreadyExistsException
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

                $storeCode = $row['store_code'] ?? 'admin';
                $storeId = $this->getStoreIdByCode($storeCode);
                unset($row['store_code']);
                $identifier = trim($row['identifier']);

                $page = $this->getPageByIdentifier($identifier, $storeId);

                $page->setData('store_id', [$storeId]);
                $page->addData($row);
                $page->setCustomLayoutUpdateXml(null);
                $page->setStores([$storeId]);

                $this->resource->save($page);
            }
        }
    }

    /**
     * @param string $identifier
     * @param int $storeId
     * @return PageInterface
     */
    private function getPageByIdentifier(string $identifier, int $storeId): PageInterface
    {
        if (null === $this->pagesInMemory) {
            $this->pagesInMemory = [];
            foreach ($this->collection->getItems() as $page) {
                $storeId = (int) current($page->getStoreId());
                $this->pagesInMemory[$page->getIdentifier()][$storeId] = $page;
            }
        }

        if (isset($this->pagesInMemory[$identifier][$storeId])) {
            return $this->pagesInMemory[$identifier][$storeId];
        }

        if (isset($this->pagesInMemory[$identifier][0])) {
            return $this->pagesInMemory[$identifier][0];
        }

        $page = $this->pageFactory->create();
        $this->pagesInMemory[$identifier][$storeId] = $page;

        return $this->pagesInMemory[$identifier][$storeId];
    }
}
