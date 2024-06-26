<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\GraphCommerceCmsSampleData\Model;

use function array_combine;
use function array_column;
use function array_shift;
use function file_exists;

/**
 * Class MediaAssetSetup
 * used to setup media assets.
 */
class MediaAssetSetup extends AbstractModel
{
    /**
     * @param array $fixtures
     * @throws \Exception
     */
    public function install(array $fixtures): void
    {
        $fileName = $this->fixtureManager->getFixture(current($fixtures) ?: '');
        if (!file_exists($fileName)) {
            return;
        }

        $data = $this->csvReader->getData($fileName);
        $header = array_shift($data);

        $rows = [];
        foreach ($data as $item) {
            $item = array_combine($header, $item);
            if (isset($item['path'], $item['source'], $item['content_type'])) {
                $rows[] = $item;
            }
        }

        $mediaAssetsTb = $this->connection->getTableName('media_gallery_asset');
        $select = $this->connection->select()
            ->from($mediaAssetsTb, ['path', 'id'])
            ->where('path IN (?)', array_column($rows, 'path'));

        $existingAssets = $this->connection->fetchPairs($select);

        $request = [];
        foreach ($rows as $row) {
            if (!isset($existingAssets[$row['path']])) {
                $request[] = $row;
            }
        }

        if ($request) {
            $this->connection->insertOnDuplicate(
                $mediaAssetsTb,
                $request
            );
        }
    }
}
