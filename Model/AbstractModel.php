<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\GraphCommerceCmsSampleData\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\File\Csv;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Framework\Setup\SampleData\FixtureManager;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class AbstractModel
{
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
     * @param SampleDataContext $sampleDataContext
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        SampleDataContext $sampleDataContext,
        StoreManagerInterface $storeManager
    ) {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->storeManager = $storeManager;
    }

    /**
     * @param string $code
     * @return int
     * @throws NoSuchEntityException
     */
    protected function getStoreIdByCode(string $code): int
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
}
