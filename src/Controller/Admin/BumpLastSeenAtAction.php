<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Controller\Admin;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

final class BumpLastSeenAtAction
{
    use ORMTrait;

    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly UrlGeneratorInterface $urlGenerator,
        /** @var class-string<CookieInterface> $cookieClass */
        private readonly string $cookieClass,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(Request $request): Response
    {
        $now = new \DateTimeImmutable();
        $ids = $request->request->all('ids');

        /** @var mixed $id */
        foreach ($ids as $id) {
            Assert::numeric($id);

            $cookie = $this->getManager($this->cookieClass)->find($this->cookieClass, $id);
            if (!$cookie instanceof CookieInterface) {
                self::addFlash($request, 'error', 'setono_sylius_consent_management.cookie_not_found');

                return new RedirectResponse($this->resolveRedirectUrl($request));
            }

            $cookie->setLastSeenAt($now);
        }

        $this->getManager($this->cookieClass)->flush();

        self::addFlash($request, 'success', 'setono_sylius_consent_management.cookies_bumped');

        return new RedirectResponse($this->resolveRedirectUrl($request));
    }

    private static function addFlash(Request $request, string $type, string $message): void
    {
        $session = $request->getSession();
        if ($session instanceof Session) {
            $session->getFlashBag()->add($type, $message);
        }
    }

    private function resolveRedirectUrl(Request $request): string
    {
        $referrer = $request->headers->get('referer');
        if (is_string($referrer) && '' !== $referrer) {
            return $referrer;
        }

        return $this->urlGenerator->generate('setono_sylius_consent_management_admin_cookie_index');
    }
}
