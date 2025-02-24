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

        foreach ($cookies as $cookie) {
            self::assertCookie($cookie);

            /**
             * TODO: Remove when https://github.com/vimeo/psalm/issues/11248 is fixed
             *
             * @psalm-suppress MixedArgument
             */
            $obj = $this->cookieRepository->findOneByName($cookie['name']);
            if (null === $obj) {
                /**
                 * TODO: Remove when https://github.com/vimeo/psalm/issues/11248 is fixed
                 *
                 * @psalm-suppress MixedArgument
                 */
                $obj = $this->cookieFactory->createWithName($cookie['name']);
                if (null === $cookie['expires']) {
                    $obj->setSession(true);
                } else {
                    /**
                     * TODO: Remove when https://github.com/vimeo/psalm/issues/11248 is fixed
                     *
                     * @psalm-suppress MixedOperand
                     */
                    $obj->setTtl(self::timestampToInterval((int) ($cookie['expires'] / 1000)));
                }
            }

            $obj->incrementSamples();
            $obj->setLastSeenAt(new \DateTimeImmutable());

            $this->cookieRepository->add($obj);
        }

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    private static function timestampToInterval(int $timestamp): \DateInterval
    {
        $now = new \DateTimeImmutable();
        $then = new \DateTimeImmutable('@' . $timestamp);

        return $now->diff($then);
    }

    /**
     * @psalm-assert array $cookie
     * @psalm-assert non-empty-string $cookie['name']
     * @psalm-assert float|null $cookie['expires']
     */
    private static function assertCookie(mixed $cookie): void
    {
        if (!is_array($cookie)) {
            throw new BadRequestHttpException();
        }

        if (!array_key_exists('name', $cookie) || !array_key_exists('expires', $cookie)) {
            throw new BadRequestHttpException();
        }

        if (!is_string($cookie['name']) || '' === $cookie['name'] || (!is_float($cookie['expires']) && null !== $cookie['expires'])) {
            throw new BadRequestHttpException();
        }
    }
}
