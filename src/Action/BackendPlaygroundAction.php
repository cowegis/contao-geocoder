<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Action;

use Cowegis\ContaoGeocoder\Form\PlaygroundFormType;
use Cowegis\ContaoGeocoder\Provider\Geocoder;
use Geocoder\Exception\Exception as GeocoderException;
use Geocoder\Query\GeocodeQuery;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class BackendPlaygroundAction
{
    /** @var Geocoder */
    private $provider;

    /** @var TwigEngine */
    private $twig;

    /** @var FormFactoryInterface */
    private $formFactory;

    public function __construct(Geocoder $provider, TwigEngine $twig, FormFactoryInterface $formFactory)
    {
        $this->provider    = $provider;
        $this->twig        = $twig;
        $this->formFactory = $formFactory;
    }

    public function __invoke(Request $request) : Response
    {
        $result = [];
        $error  = '';
        $form   = $this->formFactory->create(PlaygroundFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            try {
                $data     = $form->getData();
                $query    = GeocodeQuery::create($data['address'])->withLocale($GLOBALS['TL_LANGUAGE']);
                $provider = $this->provider;

                if ($data['provider']) {
                    $provider = $data['provider'];
                }

                $result = $provider->geocodeQuery($query)->all();
            } catch (GeocoderException $e) {
                $error = $e->getMessage();
            }
        }

        return $this->twig->renderResponse(
            '@CowegisContaoGeocode/backend/playground.html.twig',
            [
                'form'   => $form->createView(),
                'result' => $result,
                'error'  => $error,
            ]
        );
    }
}
