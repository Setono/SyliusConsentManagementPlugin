<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Controller;

use Setono\SyliusConsentManagementPlugin\Decider\Sample\SampleTokenManagerInterface;
use Setono\SyliusConsentManagementPlugin\Factory\CookieFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Recorder\CookieRecorderInterface;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Receives the cookies sampled in the visitor's browser (see SampleCookiesClientSideSubscriber). The recorder skips
 * names that can't be cookie names and limits how many cookies are recorded
 */
final class SampleController
{
    public function __construct(
        private readonly CookieRecorderInterface $cookieRecorder,
        private readonly CookieFactoryInterface $cookieFactory,
        private readonly SampleTokenManagerInterface $sampleTokenManager,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (!$request->isMethod('POST')) {
            throw new BadRequestHttpException();
        }

        // Only pages that were chosen for client-side sampling get a (short-lived, single-use) token
        $token = $request->query->get(SampleTokenManagerInterface::QUERY_PARAMETER);
        if (!is_string($token) || !$this->sampleTokenManager->consume($token)) {
            throw new AccessDeniedHttpException('Invalid, expired or used sample token');
        }

        // The sampling script posts JSON. Reading the body directly means this doesn't depend on FOSRestBundle's body listener
        try {
            $cookies = $request->toArray();
        } catch (JsonException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        if (!array_is_list($cookies)) {
            throw new BadRequestHttpException('The request body must be a JSON array of cookies');
        }

        $samples = [];
        foreach ($cookies as $cookie) {
            $cookie = self::assertCookie($cookie);

            // A browser can store a cookie without a name, e.g. after document.cookie = 'value'
            if ('' === $cookie['name']) {
                continue;
            }

            try {
                // Keyed by name, which also removes duplicates within the payload
                $samples[$cookie['name']] = $this->cookieFactory->createFromSample($cookie);
            } catch (\InvalidArgumentException $e) {
                throw new BadRequestHttpException($e->getMessage(), $e);
            }
        }

        $this->cookieRecorder->record($samples);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * @return array{name: string, ...<array-key, mixed>}
     */
    private static function assertCookie(mixed $cookie): array
    {
        if (!is_array($cookie)) {
            throw new BadRequestHttpException();
        }

        if (!array_key_exists('name', $cookie) || !is_string($cookie['name'])) {
            throw new BadRequestHttpException();
        }

        return $cookie;
    }
}
