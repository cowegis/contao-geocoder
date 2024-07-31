<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Form;

use Cowegis\ContaoGeocoder\Provider\Geocoder;
use Cowegis\ContaoGeocoder\Provider\Provider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

use function sprintf;

final class PlaygroundFormType extends AbstractType
{
    public function __construct(private readonly Geocoder $geocoder)
    {
    }

    /** {@inheritDoc} */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'address',
                TextType::class,
                [
                    'label'        => 'Address query',
                    'contaoWidget' => ['be_class' => 'w50'],
                ],
            );

        $this->addGeocoderChoiceType($builder);

        $builder->add('submit', SubmitType::class);
    }

    private function addGeocoderChoiceType(FormBuilderInterface $builder): void
    {
        $builder->add(
            'provider',
            ChoiceType::class,
            [
                'label'                     => 'Geocoder',
                'contaoWidget'              => ['be_class' => 'w50'],
                'choice_translation_domain' => false,
                'choice_loader'             => new CallbackChoiceLoader(function (): array {
                    $choices = [];

                    foreach ($this->geocoder as $provider) {
                        $choices[$provider->providerId()] = $provider;
                    }

                    return $choices;
                }),
                'choice_label'              => static function (Provider $provider): string {
                    return sprintf('%s [%s]', $provider->title(), $provider->type());
                },
            ],
        );
    }
}
