<?php

declare(strict_types=1);

namespace Setono\SyliusCookieConsentPlugin\ClientId;

use Symfony\Component\HttpFoundation\RequestStack;

final class CookieBasedClientId implements ClientIdInterface
{
    private ClientIdInterface $decorated;

    private RequestStack $requestStack;

    private string $cookieName;

    public function __construct(ClientIdInterface $decorated, RequestStack $requestStack, string $cookieName)
    {
        $this->decorated = $decorated;
        $this->requestStack = $requestStack;
        $this->cookieName = $cookieName;
    }

    public function get(): string
    {
        $request = $this->requestStack->getMasterRequest();
        if (null === $request) {
            return $this->decorated->get();
        }

        if (!$request->cookies->has($this->cookieName)) {
            return $this->decorated->get();
        }

        $cookie = $request->cookies->get($this->cookieName);
        if (!is_string($cookie)) {
            // todo log this
            return $this->decorated->get();
        }

        return $cookie;
    }
}
