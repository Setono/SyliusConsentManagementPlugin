<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Controller;

use Setono\SyliusConsentManagementPlugin\Factory\CookieFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Recorder\CookieRecorderInterface;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class SampleController
{
    public function __construct(
        private readonly CookieRecorderInterface $cookieRecorder,
        private readonly CookieFactoryInterface $cookieFactory,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (!$request->isMethod('POST')) {
            throw new BadRequestHttpException();
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
