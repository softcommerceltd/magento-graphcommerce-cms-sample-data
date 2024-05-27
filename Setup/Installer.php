<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\GraphCommerceCmsSampleData\Setup;

use Magento\Framework\Setup;
use SoftCommerce\GraphCommerceCmsSampleData\Model\Category;
use SoftCommerce\GraphCommerceCmsSampleData\Model\CmsPage;

class Installer implements Setup\SampleData\InstallerInterface
{
    /**
     * @var Category
     */
    private Category $categorySetup;

    /**
     * @var CmsPage
     */
    private CmsPage $cmsPage;

    /**
     * @param Category $categorySetup
     * @param CmsPage $cmsPage
     */
    public function __construct(
        Category $categorySetup,
        CmsPage $cmsPage
    ) {
        $this->categorySetup = $categorySetup;
        $this->cmsPage = $cmsPage;
    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
        $this->cmsPage->install(['SoftCommerce_GraphCommerceCmsSampleData::fixtures/cms-pages.csv']);
        // $this->categorySetup->install(['SoftCommerce_GraphCommerceCmsSampleData::fixtures/categories.csv']);
    }
}
