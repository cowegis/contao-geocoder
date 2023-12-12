<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Action;

use Cowegis\ContaoGeocoder\Form\PlaygroundFormType;
use Cowegis\ContaoGeocoder\Provider\Geocoder;
use Cowegis\ContaoGeocoder\Provider\Provider;
use Geocoder\Exception\Exception as GeocoderException;
use Geocoder\Query\GeocodeQuery;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment as Twig;

/** @psalm-type TFormData = array{address: string, provider: ?Provider} */
final class BackendPlaygroundAction
{
    public function __construct(
        private readonly Geocoder $provider,
        private readonly Twig $twig,
        private readonly FormFactoryInterface $formFactory,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $result    = [];
        $error     = '';
        $submitted = false;
        $form      = $this->formFactory->create(PlaygroundFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $submitted = true;

            try {
                /** @psalm-var TFormData $data */
                $data     = $form->getData();
                $query    = GeocodeQuery::create($data['address'])->withLocale($request->getLocale());
                $provider = $this->provider;

                if ($data['provider']) {
                    $provider = $data['provider'];
                }

                $result = $provider->geocodeQuery($query)->all();
            } catch (GeocoderException $e) {
                $error = $e->getMessage();
            }
        }

        return new Response(
            $this->twig->render(
                '@CowegisContaoGeocoder/backend/playground.html.twig',
                [
                    'form'      => $form->createView(),
                    'result'    => $result,
                    'error'     => $error,
                    'submitted' => $submitted,
                ],
            ),
        );
    }
}
