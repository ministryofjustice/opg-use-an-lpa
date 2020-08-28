<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Mezzio\Helper\UrlHelper;

class TranslationSwitchExtension extends AbstractExtension
{
    private $urlHelper;

    public function __construct(UrlHelper $urlHelper) {
        $this->urlHelper = $urlHelper;
    }

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_route_name', [$this, 'getRouteName']),
        ];
    }

    public function getRouteName(): string
    {
        return $this->urlHelper->getRouteResult()->getMatchedRouteName();
    }
}
