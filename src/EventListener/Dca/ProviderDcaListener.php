<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\EventListener\Dca;

use Contao\DataContainer;
use Cowegis\ContaoGeocoder\Model\ProviderRepository;
use Cowegis\ContaoGeocoder\Provider\ProviderFactory;
use Netzmacht\Contao\Toolkit\Dca\Listener\AbstractListener;
use Netzmacht\Contao\Toolkit\Dca\Manager as DcaManager;
use Netzmacht\Contao\Toolkit\Dca\Options\OptionsBuilder;
use function sprintf;

final class ProviderDcaListener extends AbstractListener
{
    /** @var string */
    protected static $name = 'tl_conwegis_geocode_provider';

    /** @var ProviderFactory */
    private $providerFactory;

    /** @var ProviderRepository */
    private $providerRepository;

    public function __construct(
        DcaManager $dcaManager,
        ProviderRepository $providerRepository,
        ProviderFactory $providerFactory
    ) {
        parent::__construct($dcaManager);

        $this->providerFactory    = $providerFactory;
        $this->providerRepository = $providerRepository;
    }

    /** @param mixed[] $row */
    public function formatLabel(array $row, string $label, DataContainer $dataContainer) : string
    {
        return sprintf(
            '%s <span class="tl_gray">%s</span>',
            $row['title'],
            $this->getFormatter()->formatValue('type', $row['type'], $dataContainer)
        );
    }

    /** @return string[] */
    public function typeOptions() : array
    {
        return $this->providerFactory->typeNames();
    }

    /** @return string[] */
    public function providerOptions(?DataContainer $dataContainer = null) : array
    {
        if ($dataContainer) {
            $collection = $this->providerRepository->findBy(['.id != ?'], [$dataContainer->id], ['order' => '.title']);
        } else {
            $collection = $this->providerRepository->findAll(['order' => '.title']);
        }

        return OptionsBuilder::fromCollection($collection, 'title')->getOptions();
    }
}
