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
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\File\Csv;
use Magento\Framework\Setup\SampleData\Context;
use Magento\Framework\Setup\SampleData\FixtureManager;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use function array_shift;
use function current;
use function file_exists;
use function trim;

class CmsPage
{
    /**
     * @var Csv
     */
    private Csv $csvReader;

    /**
     * @var FixtureManager
     */
    private FixtureManager $fixtureManager;

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
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var array|null
     */
    private ?array $pagesInMemory = null;

    /**
     * @var array
     */
    private array $storeCodeToId = [];

    /**
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param PageFactory $pageFactory
     * @param ResourcePage $resource
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        PageFactory $pageFactory,
        ResourcePage $resource,
        StoreManagerInterface $storeManager
    ) {
        $this->csvReader = $context->getCsvReader();
        $this->fixtureManager = $context->getFixtureManager();
        $this->collection = $collectionFactory->create();
        $this->pageFactory = $pageFactory;
        $this->resource = $resource;
        $this->storeManager = $storeManager;
    }

    /**
     * @param array $fixtures
     * @return void
     * @throws NoSuchEntityException
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
                $data = [];
                foreach ($row as $key => $value) {
                    $data[$header[$key]] = $value;
                }
                $row = $data;

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
     * @param string $code
     * @return int
     * @throws NoSuchEntityException
     */
    private function getStoreIdByCode(string $code): int
    {
        if (!isset($this->storeCodeToId[$code])) {
            if ($code === 'admin') {
                $this->storeCodeToId[$code] = Store::DEFAULT_STORE_ID;
            } else {
                $this->storeCodeToId[$code] = $this->storeManager->getStore($code)->getId();
            }
        }
        return (int) $this->storeCodeToId[$code];
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
