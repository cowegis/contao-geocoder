<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Action;

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\StringUtil;
use Cowegis\ContaoGeocoder\Provider\Geocoder;
use Geocoder\Collection;
use Geocoder\Location;
use Geocoder\Model\AdminLevel;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use function array_filter;
use function assert;
use function in_array;
use function is_array;
use function is_string;
use function parse_url;
use function sprintf;

use const PHP_URL_HOST;

/**
 * @psalm-type TAdminLevel = array{
 *   level?: int,
 *   name?: string,
 *   code?: string,
 * }
 * @psalm-type TAddress = array{
 *   street?: string,
 *   street_number?: int|string,
 *   city?: string,
 *   postcode?: string,
 *   country?: string,
 *   country_code?: string,
 *   state?: string,
 *   state_county?: string,
 *   adminLevels?: list<TAdminLevel>,
 * }
 * @psalm-type TRecord = array{
 *     boundingbox?: array{float, float, float, float},
 *     lat?: float,
 *     lng?: float,
 *     lon?: float,
 *     address: TAddress,
 * }
 */
final readonly class SearchAction
{
    /** @param Adapter<Config> $configAdapter */
    public function __construct(private Geocoder $geocoder, private Adapter $configAdapter)
    {
    }

    public function __invoke(Request $request, string|null $providerId = null): Response
    {
        $this->checkFirewall($request);

        $qeocoder = $this->selectGeocoder($providerId);
        $query    = $this->buildQuery($request);
        $result   = $qeocoder->geocodeQuery($query);
        $format   = $request->query->get('format', 'json');
        assert(is_string($format));

        switch ($format) {
            case 'json':
                return new JsonResponse($this->transformJson($result));

            default:
                throw new BadRequestHttpException(sprintf('Unsupported output format "%s"', $format));
        }
    }

    private function buildQuery(Request $request): GeocodeQuery
    {
        $query = GeocodeQuery::create((string) $request->query->get('q'));
        $query = $query->withLocale($request->getLocale());

        if ($request->query->has('limit')) {
            $query = $query->withLimit($request->query->getInt('limit'));
        }

        return $query;
    }

    /** @return list<TRecord> */
    private function transformJson(Collection $collection): array
    {
        $data = [];

        foreach ($collection as $item) {
            assert($item instanceof Location);

            $record      = [];
            $coordinates = $item->getCoordinates();
            $boundingBox = $item->getBounds();

            if ($boundingBox) {
                $record['boundingbox'] = [
                    $boundingBox->getSouth(),
                    $boundingBox->getNorth(),
                    $boundingBox->getWest(),
                    $boundingBox->getEast(),
                ];
            }

            if ($coordinates) {
                $record['lat'] = $coordinates->getLatitude();
                $record['lon'] = $coordinates->getLongitude();
                $record['lng'] = $coordinates->getLongitude();
            }

            $country = $item->getCountry();
            $address = [
                'street'        => $item->getStreetName(),
                'street_number' => $item->getStreetNumber(),
                'city'          => $item->getLocality(),
                'postcode'      => $item->getPostalCode(),
                'country'       => $country ? $country->getName() : null,
                'country_code'  => $country ? $country->getCode() : null,
                'state'         => $item->getAdminLevels()->has(1) ? $item->getAdminLevels()->get(1)->getName() : null,
                'state_county'  => $item->getAdminLevels()->has(2) ? $item->getAdminLevels()->get(2)->getName() : null,
                'adminLevels'   => [],
            ];

            /** @psalm-var AdminLevel $adminLevel */
            foreach ($item->getAdminLevels() as $adminLevel) {
                $address['adminLevels'][] = array_filter(
                    [
                        'level' => $adminLevel->getLevel(),
                        'name'  => $adminLevel->getName(),
                        'code'  => $adminLevel->getCode(),
                    ],
                );
            }

            $record['address'] = array_filter($address);
            $data[]            = $record;
        }

        return $data;
    }

    private function selectGeocoder(string|null $providerId): Provider
    {
        if ($providerId !== null) {
            return $this->geocoder->using($providerId);
        }

        return $this->geocoder;
    }

    private function checkFirewall(Request $request): void
    {
        $this->checkApiKey($request);
        $this->checkReferrer($request);
    }

    private function checkApiKey(Request $request): void
    {
        /** @psalm-suppress InternalMethod */
        $apiKey = (string) $this->configAdapter->get('cowegis_geocoder_api_key');
        if ($apiKey === '') {
            return;
        }

        if ($request->query->get('key') === $apiKey) {
            return;
        }

        throw new AccessDeniedHttpException();
    }

    private function checkReferrer(Request $request): void
    {
        /** @psalm-suppress InternalMethod */
        if (! $this->configAdapter->get('cowegis_geocoder_referrer_check')) {
            return;
        }

        /** @psalm-suppress InternalMethod */
        $allowedDomains = StringUtil::deserialize($this->configAdapter->get('cowegis_geocoder_referrer_domains'), true);
        $referrer       = (string) $request->headers->get('referer');
        $referrer       = (string) parse_url($referrer, PHP_URL_HOST);
        assert(is_array($allowedDomains));

        // No referer given, skip
        if ($referrer === '') {
            return;
        }

        if (! in_array($referrer, $allowedDomains, true)) {
            throw new BadRequestHttpException();
        }
    }
}
