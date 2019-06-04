<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\Form;

use Cowegis\ContaoGeocoder\Model\ProviderRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use function sprintf;

final class PlaygroundFormType extends AbstractType
{
    /** @var ProviderRepository */
    private $providerRepository;

    public function __construct(ProviderRepository $providerRepository)
    {
        $this->providerRepository = $providerRepository;
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

                    foreach ($this->providerRepository->findAll() ?: [] as $provider) {
                        $choices[$provider->id] = $provider;
                    }

                    return $choices;
                }),
                'choice_label'              => static function ($choice, $key, $value) {
                    return sprintf('%s [%s]', $choice->title, $choice->type);
                },
                'preferred_choices'         => static function ($choice) {
                    return $choice->isDefault && $choice->scope !== 'frontend';
                },
            ]
        );
    }
}
