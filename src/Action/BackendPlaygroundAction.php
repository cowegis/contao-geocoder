<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Action;

use Cowegis\ContaoGeocoder\Form\PlaygroundFormType;
use Cowegis\ContaoGeocoder\Provider\Geocoder;
use Geocoder\Exception\Exception as GeocoderException;
use Geocoder\Query\GeocodeQuery;
use Netzmacht\Contao\Toolkit\View\Template\TemplateRenderer;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function is_array;

/**
 * @psalm-type TFormData = array{address: string, provider: string}
 */
final class BackendPlaygroundAction
{
    /** @var Geocoder */
    private $provider;

    /** @var TemplateRenderer */
    private $renderer;

    /** @var FormFactoryInterface */
    private $formFactory;

    public function __construct(Geocoder $provider, TemplateRenderer $renderer, FormFactoryInterface $formFactory)
    {
        $this->provider    = $provider;
        $this->renderer    = $renderer;
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
                /** @psalm-var TFormData $data */
                $data     = $form->getData();
                $query    = GeocodeQuery::create($data['address'])->withLocale($request->getLocale());
                $provider = $this->provider;

                if ($data['provider']) {
                    $provider = $this->provider->using($data['provider']);
                }

                $result = $provider->geocodeQuery($query)->all();
            } catch (GeocoderException $e) {
                $error = $e->getMessage();
            }
        }

        return new Response(
            $this->renderer->render(
                '@CowegisContaoGeocoder/backend/playground.html.twig',
                [
                    'form'   => $form->createView(),
                    'result' => $result,
                    'error'  => $error,
                ]
            )
        );
    }
}
