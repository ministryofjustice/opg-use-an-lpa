<?php

declare(strict_types=1);

namespace Common\Middleware\I18n;

use Acpr\I18n\TranslatorInterface;
use Locale;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SetLocaleMiddleware implements MiddlewareInterface
{
    private string $fallbackLocale = 'en_GB';

    private const REGEX_LOCALE = '#^/(?P<locale>cy)(?:/|$)#';

    public function __construct(private UrlHelper $helper, private TranslatorInterface $translator, private ?string $defaultLocale = null)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();

        $path = $uri->getPath();

        if (! preg_match(self::REGEX_LOCALE, $path, $matches)) {
            Locale::setDefault($this->defaultLocale ?? $this->fallbackLocale);

            return $handler->handle($request->withAttribute('locale', Locale::getDefault()));
        }

        $locale = $matches['locale'];
        Locale::setDefault(Locale::canonicalize($locale . '_GB'));
        $this->translator->setLocale(Locale::getDefault());
        $this->helper->setBasePath($locale);

        $path = substr($path, strlen($locale) + 1);

        return $handler->handle(
            $request
                ->withUri($uri->withPath($path ?: '/'))
                ->withAttribute('locale', Locale::getDefault())
        );
    }
}
