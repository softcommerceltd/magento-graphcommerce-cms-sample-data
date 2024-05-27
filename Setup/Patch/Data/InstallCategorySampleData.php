<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\GraphCommerceCmsSampleData\Setup\Patch\Data;

use Magento\CmsSampleData\Setup\Installer;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\SampleData\Executor;
use SoftCommerce\GraphCommerceCms\Setup\Patch\Data\InstallCategoryRowContentAttribute;

/**
 * Class InstallCategorySampleData
 * used to install category sample data.
 */
class InstallCategorySampleData implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var Executor
     */
    private Executor $executor;

    /**
     * @var Installer
     */
    private Installer $installer;

    /**
     * @param Executor $executor
     * @param Installer $installer
     */
    public function __construct(
        Executor $executor,
        Installer $installer
    ) {
        $this->executor = $executor;
        $this->installer = $installer;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(): void
    {
        $this->executor->exec($this->installer);
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies(): array
    {
        return [
            InstallCategoryRowContentAttribute::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion(): string
    {
        return '2.0.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases(): array
    {
        return [];
    }
}
