<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Action;

use Cowegis\ContaoGeocoder\Provider\Geocoder;
use Geocoder\Collection;
use Geocoder\Exception\ProviderNotRegistered;
use Geocoder\Location;
use Geocoder\Model\AdminLevel;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use function array_filter;
use function assert;

final class SearchAction
{
    /** @var Geocoder */
    private $geocoder;

    public function __construct(Geocoder $geocoder)
    {
        $this->geocoder = $geocoder;
    }

    public function __invoke(?string $providerId = null, Request $request): Response
    {
        $qeocoder = $this->selectGeocoder($providerId);
        $query    = $this->buildQuery($request);
        $result   = $qeocoder->geocodeQuery($query);
        $format   = $request->query->get('format', 'json');

        switch ($format) {
            case 'json':
                return new JsonResponse($this->transformJson($result));

            default:
                throw new BadRequestHttpException(sprintf('Unsupported output format "%s"', $format));
        }
    }

    /**
     * @param Request $request
     * @return GeocodeQuery
     */
    protected function buildQuery(Request $request) : GeocodeQuery
    {
        $query = GeocodeQuery::create($request->query->get('q'));
        $query = $query->withLocale($request->getLocale());

        if ($request->query->has('limit')) {
            $query = $query->withLimit($request->query->getInt('limit'));
        }

        return $query;
    }

    private function transformJson(Collection $collection): array
    {
        $data = [];

        foreach ($collection as $item) {
            assert($item instanceof Location);

            $record = [];
            $coordinates = $item->getCoordinates();
            $boundingBox = $item->getBounds();

            if ($boundingBox) {
                $record['boundingbox'] = [
                    $boundingBox->getSouth(),
                    $boundingBox->getNorth(),
                    $boundingBox->getWest(),
                    $boundingBox->getEast()
                ];
            }

            if ($coordinates) {
                $record['lat'] = $coordinates->getLatitude();
                $record['lng'] = $coordinates->getLongitude();
            }

            $address = [
                'street'        => $item->getStreetName(),
                'street_number' => $item->getStreetNumber(),
                'city'          => $item->getLocality(),
                'postcode'      => $item->getPostalCode(),
                'country'       => $item->getCountry() ? $item->getCountry()->getName() : null,
                'country_code'  => $item->getCountry() ? $item->getCountry()->getCode() : null,
                'state'         => $item->getAdminLevels()->has(1) ? $item->getAdminLevels()->get(1)->getName() : null,
                'state_county'  => $item->getAdminLevels()->has(2) ? $item->getAdminLevels()->get(2)->getName() : null,
                'adminLevels'   => []
            ];

            foreach ($item->getAdminLevels() as $adminLevel) {
                assert($adminLevel instanceof AdminLevel);

                $address['adminLevels'][] = array_filter(
                    [
                        'level' => $adminLevel->getLevel(),
                        'name'  => $adminLevel->getName(),
                        'code'  => $adminLevel->getCode()
                    ]
                );
            }

            $record['address'] = array_filter($address);
            $data[]            = $record;
        }

        return $data;
    }

    private function selectGeocoder(?string $providerId): Provider
    {
        if ($providerId) {
            return $this->geocoder->using($providerId);
        }

        return $this->geocoder;
    }
}
