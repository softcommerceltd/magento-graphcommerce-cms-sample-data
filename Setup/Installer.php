<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\GraphCommerceCmsSampleData\Setup;

use Magento\Framework\Setup;
use SoftCommerce\GraphCommerceCmsSampleData\Model\CategorySetup;
use SoftCommerce\GraphCommerceCmsSampleData\Model\CmsPageSetup;
use SoftCommerce\GraphCommerceCmsSampleData\Model\MediaAssetSetup;

class Installer implements Setup\SampleData\InstallerInterface
{
    /**
     * @var CategorySetup
     */
    private CategorySetup $categorySetup;

    /**
     * @var CmsPageSetup
     */
    private CmsPageSetup $cmsPageSetup;

    /**
     * @var MediaAssetSetup
     */
    private MediaAssetSetup $mediaAssetSetup;

    /**
     * @param CategorySetup $categorySetup
     * @param CmsPageSetup $cmsPageSetup
     * @param MediaAssetSetup $mediaAssetSetup
     */
    public function __construct(
        CategorySetup $categorySetup,
        CmsPageSetup $cmsPageSetup,
        MediaAssetSetup $mediaAssetSetup
    ) {
        $this->categorySetup = $categorySetup;
        $this->cmsPageSetup = $cmsPageSetup;
        $this->mediaAssetSetup = $mediaAssetSetup;
    }

    /**
     * @inheritdoc
     */
    public function install(): void
    {
        $this->mediaAssetSetup->install(['SoftCommerce_GraphCommerceCmsSampleData::fixtures/media-asset.csv']);
        $this->cmsPageSetup->install(['SoftCommerce_GraphCommerceCmsSampleData::fixtures/cms-pages.csv']);
        $this->categorySetup->install(['SoftCommerce_GraphCommerceCmsSampleData::fixtures/categories.csv']);
    }
}
