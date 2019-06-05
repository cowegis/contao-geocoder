<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Model;

use Contao\Model\Collection;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;

final class ProviderRepository extends ContaoRepository
{
    public function __construct()
    {
        parent::__construct(ProviderModel::class);
    }

    /**
     * @param int[] $providerIds
     *
     * @return ProviderModel[]|Collection|null
     */
    public function findByIds(array $providerIds) : ?Collection
    {
        return $this->findMultipleByIds($providerIds);
    }
}
