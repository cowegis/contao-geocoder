<?php

declare(strict_types=1);

namespace Cowegis\ContaoGeocoder\EventListener\Hook;

use Netzmacht\Contao\Toolkit\Routing\RequestScopeMatcher;
use Netzmacht\Contao\Toolkit\View\Assets\AssetsManager;

final class BackendListener
{
    /** @var RequestScopeMatcher */
    private $scopeMatcher;

    /** @var AssetsManager */
    private $assetsManager;

    public function __construct(RequestScopeMatcher $scopeMatcher, AssetsManager $assetsManager)
    {
        $this->scopeMatcher = $scopeMatcher;
        $this->assetsManager = $assetsManager;
    }

    public function onInitializeSystem(): void
    {
        if (!$this->scopeMatcher->isBackendRequest()) {
            return;
        }

        $this->assetsManager->addStylesheet('bundles/cowegiscontaogeocoder/css/backend.css');
    }
}
