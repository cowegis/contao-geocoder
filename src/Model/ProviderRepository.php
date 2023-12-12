<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Model;

use Contao\Model\Collection;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;

/** @extends ContaoRepository<ProviderModel> */
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
     * @psalm-return Collection|null
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    public function findByIds(array $providerIds): Collection|null
    {
        return $this->__call('findMultipleByIds', [$providerIds]);
    }
}
