<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\EventListener\Dca;

use Contao\DataContainer;
use Cowegis\ContaoGeocoder\Provider\Geocoder;
use Cowegis\ContaoGeocoder\Provider\ProviderFactory;
use Doctrine\DBAL\Connection;
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

    /** @var Connection */
    private $connection;

    public function __construct(
        DcaManager $dcaManager,
        ProviderFactory $providerFactory,
        Geocoder $geocoder,
        RouterInterface $router,
        Connection $connection
    ) {
        parent::__construct($dcaManager);

        $this->providerFactory = $providerFactory;
        $this->router          = $router;
        $this->geocoder        = $geocoder;
        $this->connection      = $connection;
    }

    /**
     * @param mixed[] $row
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @psalm-suppress MixedArgument
     */
    public function formatLabel(array $row, string $label, DataContainer $dataContainer) : string
    {
        $value = $this->getFormatter()->formatValue('type', $row['type'], $dataContainer);
        if (is_array($value)) {
            $value = implode(', ', $value);
        }

        return sprintf(
            '%s %s<small class="tl_gray">[%s]</small>',
            $row['title'],
            $row['isDefault'] ? sprintf('(%s) ', $this->getFormatter()->formatFieldLabel('isDefault')) : '',
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
            if ($dataContainer !== null && ((string) $dataContainer->id) === $provider->providerId()) {
                continue;
            }

            $options[$provider->providerId()] = $provider->title();
        }

        return $options;
    }

    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    public function playgroundButton(?string $href, string $label, string $title) : string
    {
        return sprintf(
            '<a href="%s" title="%s">%s</a> ',
            $this->router->generate('cowegis_geocoder_playground'),
            $title,
            $label
        );
    }

    public function setDefault(DataContainer $dataContainer): void
    {
        if (!$dataContainer->activeRecord || !$dataContainer->activeRecord->isDefault) {
            return;
        }

        $this->connection->executeQuery(
            'UPDATE tl_cowegis_geocoder_provider SET isDefault=:default WHERE id != :id ',
            [
                'default' => '',
                'id' => $dataContainer->id
            ]
        );
    }
}
