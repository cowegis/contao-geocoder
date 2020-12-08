<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Routing;

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Symfony\Component\Routing\RouterInterface;

final class SearchUrlGenerator
{
    /** @var RouterInterface */
    private $router;

    /** @var Adapter<Config> $configAdapter */
    private $configAdapter;

    /** @param Adapter<Config> $configAdapter */
    public function __construct(RouterInterface $router, Adapter $configAdapter)
    {
        $this->router        = $router;
        $this->configAdapter = $configAdapter;
    }

    /** @param array<string,string> $params */
    public function generate(array $params = []): string
    {
        /** @psalm-suppress InternalMethod */
        if ($this->configAdapter->get('cowegis_geocoder_api_key')) {
            $params['key'] = (string) $this->configAdapter->get('cowegis_geocoder_api_key');
        }

        if (isset($params['providerId'])) {
            return $this->router->generate('cowegis_geocoder_provider_search', $params);
        }

        return $this->router->generate('cowegis_geocoder_search', $params);
    }

    public function searchWithDefaultProvider(string $keyword, int $limit = 0, string $format = 'json'): string
    {
        $params = $this->buildParams($keyword, $limit, $format);

        return $this->generate($params);
    }

    public function searchWithProvider(
        string $providerId,
        string $keyword,
        int $limit = 0,
        string $format = 'json'
    ): string {
        $params               = $this->buildParams($keyword, $limit, $format);
        $params['providerId'] = $providerId;

        return $this->generate($params);
    }

    /** @return array<string,string> */
    private function buildParams(string $keyword, int $limit, string $format) : array
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
