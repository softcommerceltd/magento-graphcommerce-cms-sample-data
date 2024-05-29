<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\GraphCommerceCmsSampleData\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\File\Csv;
use Magento\Framework\Setup\SampleData\Context;
use Magento\Framework\Setup\SampleData\FixtureManager;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class AbstractModel
{
    /**
     * @var AdapterInterface
     */
    protected AdapterInterface $connection;

    /**
     * @var FixtureManager
     */
    protected FixtureManager $fixtureManager;

    /**
     * @var Csv
     */
    protected Csv $csvReader;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var int|null
     */
    private ?int $defaultStoreId = null;

    /**
     * @var array
     */
    private array $storeCodeToId = [];

    /**
     * @param ResourceConnection $resourceConnection
     * @param Context $sampleDataContext
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        Context $sampleDataContext,
        StoreManagerInterface $storeManager
    ) {
        $this->connection = $resourceConnection->getConnection();
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->storeManager = $storeManager;
    }

    /**
     * @param string $code
     * @return int|null
     */
    protected function getStoreIdByCode(string $code): ?int
    {
        if (!isset($this->storeCodeToId[$code])) {
            if (strtolower($code) === 'admin') {
                $this->storeCodeToId[$code] = Store::DEFAULT_STORE_ID;
            } else {
                $select = $this->connection->select()
                    ->from($this->connection->getTableName('store'), ['store_id'])
                    ->where('code = ?', $code);
                $this->storeCodeToId[$code] = $this->connection->fetchOne($select);
            }
        }
        return isset($this->storeCodeToId[$code]) ? (int) $this->storeCodeToId[$code] : null;
    }

    /**
     * @return int|null
     */
    protected function getDefaultStoreId(): ?int
    {
        if (null === $this->defaultStoreId) {
            $select = $this->connection->select()
                ->from(
                    ['store' => $this->connection->getTableName('store')],
                    'store_id'
                )
                ->joinLeft(
                    ['group' => $this->connection->getTableName('store_group')],
                    'store.store_id = group.default_store_id',
                )
                ->where('group.website_id = ?', 1);

            $this->defaultStoreId = (int) $this->connection->fetchOne($select);
        }

        return $this->defaultStoreId;
    }
}
