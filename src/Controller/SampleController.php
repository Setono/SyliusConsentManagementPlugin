<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Controller;

use Setono\SyliusConsentManagementPlugin\Factory\CookieFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Repository\CookieRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class SampleController
{
    public function __construct(
        private readonly CookieRepositoryInterface $cookieRepository,
        private readonly CookieFactoryInterface $cookieFactory,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (!$request->isMethod('POST')) {
            throw new BadRequestHttpException();
        }

        $cookies = $request->request->all();
        if (!array_is_list($cookies)) {
            throw new BadRequestHttpException();
        }

        /** @var mixed $cookie */
        foreach ($cookies as $cookie) {
            $cookie = self::assertCookie($cookie);

            $obj = $this->cookieRepository->findOneByName($cookie['name']);
            if (null === $obj) {
                try {
                    $obj = $this->cookieFactory->createFromSample($cookie);
                } catch (\InvalidArgumentException $e) {
                    throw new BadRequestHttpException($e->getMessage(), $e);
                }
            }

            $obj->incrementSamples();
            $obj->setLastSeenAt(new \DateTimeImmutable());

            $this->cookieRepository->add($obj);
        }

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
