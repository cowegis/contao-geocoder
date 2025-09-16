<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\EventListener\Dca;

use Contao\DataContainer;
use Cowegis\ContaoGeocoder\Provider\Geocoder;
use Cowegis\ContaoGeocoder\Provider\ProviderFactory;
use Doctrine\DBAL\Connection;
use Netzmacht\Contao\Toolkit\Dca\Listener\AbstractListener;
use Netzmacht\Contao\Toolkit\Dca\Manager as DcaManager;
use Override;

use function implode;
use function is_array;
use function sprintf;

final class ProviderDcaListener extends AbstractListener
{
    public function __construct(
        DcaManager $dcaManager,
        private readonly ProviderFactory $providerFactory,
        private readonly Geocoder $geocoder,
        private readonly Connection $connection,
    ) {
        parent::__construct($dcaManager);
    }

    #[Override]
    public static function getName(): string
    {
        return 'tl_cowegis_geocoder_provider';
    }

    /**
     * @param mixed[] $row
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @psalm-suppress MixedArgument
     */
    public function formatLabel(array $row, string $label, DataContainer $dataContainer): string
    {
        $value = $this->getFormatter()->formatValue('type', $row['type'], $dataContainer);
        if (is_array($value)) {
            $value = implode(', ', $value);
        }

        return sprintf(
            '%s %s<small class="tl_gray">[%s]</small>',
            $row['title'],
            $row['isDefault'] ? sprintf('(%s) ', $this->getFormatter()->formatFieldLabel('isDefault')) : '',
            (string) $value,
        );
    }

    /** @return string[] */
    public function typeOptions(): array
    {
        return $this->providerFactory->typeNames();
    }

    /** @return string[] */
    public function providerOptions(DataContainer|null $dataContainer = null): array
    {
        $options = [];

        foreach ($this->geocoder as $provider) {
            if ($dataContainer instanceof DataContainer && ((string) $dataContainer->id) === $provider->providerId()) {
                continue;
            }

            $options[$provider->providerId()] = $provider->title();
        }

        return $options;
    }

    public function setDefault(DataContainer $dataContainer): void
    {
        /** @psalm-suppress MixedPropertyFetch */
        if (! $dataContainer->activeRecord || ! $dataContainer->activeRecord->isDefault) {
            return;
        }

        $this->connection->executeQuery(
            'UPDATE tl_cowegis_geocoder_provider SET isDefault=:default WHERE id != :id ',
            [
                'default' => '',
                'id'      => $dataContainer->id,
            ],
        );
    }
}
