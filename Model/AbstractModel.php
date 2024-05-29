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
     * @var array
     */
    protected array $storeCodeToId = [];

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
     * @return int
     */
    protected function getStoreIdByCode(string $code): int
    {
        if (!isset($this->storeCodeToId[$code])) {
            if (strtolower($code) === 'admin') {
                $this->storeCodeToId[$code] = Store::DEFAULT_STORE_ID;
            } else {
                $select = $this->connection->select()
                    ->from($this->connection->getTableName('store'), ['store_id'])
                    ->where('code = ?', $code);
                $this->storeCodeToId[$code] = (int) $this->connection->fetchOne($select);
            }
        }
        return $this->storeCodeToId[$code];
    }
}
