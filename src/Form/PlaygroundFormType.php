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
    /** @var Geocoder */
    private $geocoder;

    public function __construct(Geocoder $geocoder)
    {
        $this->geocoder = $geocoder;
    }

    /** {@inheritDoc} */
    public function buildForm(FormBuilderInterface $builder, array $options) : void
    {
        $this->addGeocoderChoiceType($builder);

        $builder
            ->add('address', TextType::class, ['label' => 'Address'])
            ->add('submit', SubmitType::class);
    }

    private function addGeocoderChoiceType(FormBuilderInterface $builder) : void
    {
        $builder->add(
            'provider',
            ChoiceType::class,
            [
                'label' => 'Geocoder',
                'choice_translation_domain' => false,
                'choice_loader'             => new CallbackChoiceLoader(function () {
                    $choices = [];

                    /** @var Provider $provider */
                    foreach ($this->geocoder as $provider) {
                        $choices[$provider->id()] = $provider;
                    }

                    return $choices;
                }),
                'choice_label'              => static function (Provider $provider) {
                    return sprintf('%s [%s]', $provider->title(), $provider->type());
                },
            ]
        );
    }
}
