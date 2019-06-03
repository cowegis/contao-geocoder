<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Action;

use Cowegis\ContaoGeocoder\Form\PlaygroundFormType;
use Cowegis\ContaoGeocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class BackendPlaygroundAction
{
    /** @var Provider */
    private $provider;

    /** @var TwigEngine */
    private $twig;

    /** @var FormFactoryInterface */
    private $formFactory;

    public function __construct(Provider $provider, TwigEngine $twig, FormFactoryInterface $formFactory)
    {
        $this->provider    = $provider;
        $this->twig        = $twig;
        $this->formFactory = $formFactory;
    }

    public function __invoke(Request $request) : Response
    {
        $address = $request->query->get('address');
        $result  = [];

        $form = $this->formFactory->create(PlaygroundFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $address = $form->get('address')->getData();
            \dump($address);
            $result = $this->provider->geocodeQuery(GeocodeQuery::create($address))->all();
        }

        return $this->twig->renderResponse(
            '@CowegisContaoGeocode/backend/playground.html.twig',
            [
                'result' => $result,
                'form' => $form
            ]
        );
    }
}
