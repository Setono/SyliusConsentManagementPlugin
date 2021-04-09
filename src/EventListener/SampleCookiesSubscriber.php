<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventListener;

use Setono\SyliusConsentManagementPlugin\Factory\CookieFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Repository\CookieRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class SampleCookiesSubscriber implements EventSubscriberInterface
{
    private CookieRepositoryInterface $cookieRepository;

    private CookieFactoryInterface $cookieFactory;

    public function __construct(CookieRepositoryInterface $cookieRepository, CookieFactoryInterface $cookieFactory)
    {
        $this->cookieRepository = $cookieRepository;
        $this->cookieFactory = $cookieFactory;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'sample',
        ];
    }

    public function sample(RequestEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }
        $request = $event->getRequest();

        /**
         * @var string $name
         * @var mixed $value
         */
        foreach ($request->cookies->all() as $name => $value) {
            if (null !== $this->cookieRepository->findOneByName($name)) {
                continue;
            }

            $obj = $this->cookieFactory->createWithData($name, $request->getUri());
            $this->cookieRepository->add($obj);
        }
    }
}
