<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Factory;

use Setono\SyliusConsentManagementPlugin\Model\CategoryInterface;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Webmozart\Assert\Assert;

final class ConsentEntryFactory implements ConsentEntryFactoryInterface
{
    public function __construct(
        private readonly FactoryInterface $decorated,
        private readonly RepositoryInterface $categoryRepository,
    ) {
    }

    public function createNew(): ConsentEntryInterface
    {
        /** @var ConsentEntryInterface|object $obj */
        $obj = $this->decorated->createNew();
        Assert::isInstanceOf($obj, ConsentEntryInterface::class);

        /** @var CategoryInterface $category */
        foreach ($this->categoryRepository->findAll() as $category) {
            if (!$category->isNecessary()) {
                continue;
            }

            $obj->addConsentedCategory($category);
        }

        return $obj;
    }

    public function createFromRequest(Request $request): ConsentEntryInterface
    {
        $obj = $this->createNew();

        $obj->setIp((string) $request->getClientIp());

        $url = $request->getUri();
        if ($request->isXmlHttpRequest() && $request->headers->has('referer')) {
            $referrer = $request->headers->get('referer');
            if (is_string($referrer)) {
                $url = $referrer;
            }
        }
        $obj->setUrl($url);

        $userAgent = $request->headers->get('user-agent');
        if (is_string($userAgent)) {
            $obj->setUserAgent($userAgent);
        }

        return $obj;
    }
}
