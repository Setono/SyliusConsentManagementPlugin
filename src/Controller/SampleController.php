<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Controller;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\SyliusConsentManagementPlugin\Cookie\CookieName;
use Setono\SyliusConsentManagementPlugin\Factory\CookieFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Recorder\CookieRecorderInterface;
use Setono\SyliusConsentManagementPlugin\Sampling\SampleTokenManagerInterface;
use Setono\SyliusConsentManagementPlugin\Sampling\SamplingRateLimiterInterface;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * Receives the cookies sampled in the visitor's browser (see SampleCookiesClientSideSubscriber). Cookies with invalid
 * names are skipped, and the recorder limits how many cookies are recorded
 */
final class SampleController implements LoggerAwareInterface
{
    /**
     * Chrome stores at most 180 cookies per domain, so a real sample has fewer cookies than this
     */
    public const MAX_SAMPLES = 200;

    /**
     * Enough for MAX_SAMPLES cookies of 4 KiB each (the most a browser stores per cookie) and their other fields
     */
    public const MAX_BODY_SIZE = 1024 * 1024;

    private LoggerInterface $logger;

    public function __construct(
        private readonly CookieRecorderInterface $cookieRecorder,
        private readonly CookieFactoryInterface $cookieFactory,
        private readonly SampleTokenManagerInterface $sampleTokenManager,
        private readonly SamplingRateLimiterInterface $samplingRateLimiter,
    ) {
        $this->logger = new NullLogger();
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

        $rateLimit = $this->samplingRateLimiter->consumeRecording($request);
        if (!$rateLimit->isAccepted()) {
            throw new TooManyRequestsHttpException(max(0, $rateLimit->getRetryAfter()->getTimestamp() - time()), 'Too many samples from this client');
        }

        // Oversized payloads are rejected before they're decoded, and before any cookie is built from them
        if (strlen($request->getContent()) > self::MAX_BODY_SIZE) {
            throw new BadRequestHttpException(sprintf('The request body must be at most %d bytes', self::MAX_BODY_SIZE));
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

        if (count($cookies) > self::MAX_SAMPLES) {
            throw new BadRequestHttpException(sprintf('The request body must contain at most %d cookies', self::MAX_SAMPLES));
        }

        $samples = $invalidNames = [];
        foreach ($cookies as $cookie) {
            $cookie = self::assertCookie($cookie);

            // Skipped instead of rejecting the whole payload, because browsers store such cookies too, e.g. a cookie
            // without a name after document.cookie = 'value'
            if (!CookieName::isValid($cookie['name'])) {
                $invalidNames[] = $cookie['name'];

                continue;
            }

            try {
                // The recorder records duplicate names once
                $samples[] = $this->cookieFactory->createFromSample($cookie);
            } catch (\InvalidArgumentException $e) {
                throw new BadRequestHttpException($e->getMessage(), $e);
            }
        }

        if ([] !== $invalidNames) {
            $this->logger->info('Skipped {count} sampled cookies with invalid names', ['count' => count($invalidNames), 'names' => $invalidNames]);
        }

        $this->cookieRecorder->record($samples, $this->samplingRateLimiter->createNewCookieLimiter($request));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
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
