<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\EventListener\Hook;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Netzmacht\Contao\Toolkit\Routing\RequestScopeMatcher;
use Netzmacht\Contao\Toolkit\View\Assets\AssetsManager;

final readonly class BackendListener
{
    public function __construct(private RequestScopeMatcher $scopeMatcher, private AssetsManager $assetsManager)
    {
    }

    #[AsHook('initializeSystem')]
    public function onInitializeSystem(): void
    {
        if (! $this->scopeMatcher->isBackendRequest()) {
            return;
        }

        $this->assetsManager->addStylesheet('bundles/cowegiscontaogeocoder/css/backend.css');
    }
}
