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
 * Receives the cookies sampled in the visitor's browser (see SampleCookiesClientSideSubscriber)
 */
final class SampleController
{
    /**
     * A page rarely sets more cookies than this. Larger payloads aren't samples from a browser
     */
    public const MAX_COOKIES = 50;

    /**
     * The characters allowed in a cookie name (a 'token' in RFC 6265). Anything else can't be a cookie a browser has stored
     */
    private const NAME_PATTERN = '/^[!#$%&\'*+\-.^_`|~0-9A-Za-z]+$/';

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

        // Only pages that were chosen for client-side sampling get a (short-lived) token
        $token = $request->query->get(SampleTokenManagerInterface::QUERY_PARAMETER);
        if (!is_string($token) || !$this->sampleTokenManager->isValid($token)) {
            throw new AccessDeniedHttpException('Invalid or expired sample token');
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

        if (count($cookies) > self::MAX_COOKIES) {
            throw new BadRequestHttpException(sprintf('At most %d cookies can be sampled at a time', self::MAX_COOKIES));
        }

        $samples = [];
        foreach ($cookies as $cookie) {
            $cookie = self::assertCookie($cookie);

            if (1 !== preg_match(self::NAME_PATTERN, $cookie['name'])) {
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
     * @return array{name: non-empty-string, ...<array-key, mixed>}
     */
    private static function assertCookie(mixed $cookie): array
    {
        if (!is_array($cookie)) {
            throw new BadRequestHttpException();
        }

        if (!array_key_exists('name', $cookie) || !is_string($cookie['name']) || '' === $cookie['name']) {
            throw new BadRequestHttpException();
        }

        return $cookie;
    }
}
