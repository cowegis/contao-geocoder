<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\EventListener\Dca;

use Contao\DataContainer;
use Cowegis\ContaoGeocoder\Provider\Geocoder;
use Cowegis\ContaoGeocoder\Provider\ProviderFactory;
use Netzmacht\Contao\Toolkit\Dca\Listener\AbstractListener;
use Netzmacht\Contao\Toolkit\Dca\Manager as DcaManager;
use Symfony\Component\Routing\RouterInterface;
use function implode;
use function is_array;
use function sprintf;

final class ProviderDcaListener extends AbstractListener
{
    /** @var string */
    protected static $name = 'tl_cowegis_geocoder_provider';

    /** @var ProviderFactory */
    private $providerFactory;

    /** @var Geocoder */
    private $geocoder;

    /** @var RouterInterface */
    private $router;

    public function __construct(
        DcaManager $dcaManager,
        ProviderFactory $providerFactory,
        Geocoder $geocoder,
        RouterInterface $router
    ) {
        parent::__construct($dcaManager);

        $this->providerFactory = $providerFactory;
        $this->router          = $router;
        $this->geocoder        = $geocoder;
    }

    /** @param mixed[] $row */
    public function formatLabel(array $row, string $label, DataContainer $dataContainer) : string
    {
        $value = $this->getFormatter()->formatValue('type', $row['type'], $dataContainer);
        if (is_array($value)) {
            $value = implode(', ', $value);
        }

        return sprintf(
            '%s <small class="tl_gray">[%s]</small>',
            $row['title'],
            (string) $value
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
        $options = [];

        foreach ($this->geocoder as $provider) {
            if ($dataContainer && ((string) $dataContainer->id) === $provider->providerId()) {
                continue;
            }

            $options[$provider->providerId()] = $provider->title();
        }

        return $options;
    }

    public function playgroundButton(?string $href, string $label, string $title) : string
    {
        return sprintf(
            '<a href="%s" title="%s">%s</a> ',
            $this->router->generate('cowegis_geocoder_playground'),
            $title,
            $label
        );
    }
}
