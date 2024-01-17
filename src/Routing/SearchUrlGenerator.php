<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Routing;

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class SearchUrlGenerator
{
    /** @param Adapter<Config> $configAdapter */
    public function __construct(private readonly RouterInterface $router, private readonly Adapter $configAdapter)
    {
    }

    /** @param array<string,string> $params */
    public function generate(array $params = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        /** @psalm-suppress InternalMethod */
        if ($this->configAdapter->get('cowegis_geocoder_api_key')) {
            $params['key'] = (string) $this->configAdapter->get('cowegis_geocoder_api_key');
        }

        if (isset($params['providerId'])) {
            return $this->router->generate('cowegis_geocoder_provider_search', $params, $referenceType);
        }

        return $this->router->generate('cowegis_geocoder_search', $params);
    }

    public function searchWithDefaultProvider(
        string $keyword,
        int $limit = 0,
        string $format = 'json',
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
    ): string {
        $params = $this->buildParams($keyword, $limit, $format);

        return $this->generate($params, $referenceType);
    }

    public function searchWithProvider(
        string $providerId,
        string $keyword,
        int $limit = 0,
        string $format = 'json',
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
    ): string {
        $params               = $this->buildParams($keyword, $limit, $format);
        $params['providerId'] = $providerId;

        return $this->generate($params, $referenceType);
    }

    /** @return array<string,string> */
    private function buildParams(string $keyword, int $limit, string $format): array
    {
        $params = ['q' => $keyword];
        if ($limit > 0) {
            $params['limit'] = (string) $limit;
        }

        if ($format !== 'json') {
            $params['format'] = $format;
        }

        return $params;
    }
}
