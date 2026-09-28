<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Template;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Model\Cookie;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class EmailTemplatesTest extends TestCase
{
    /**
     * @test
     */
    public function it_links_each_new_cookie_to_the_admin(): void
    {
        $twig = new Environment(new FilesystemLoader(dirname(__DIR__, 2) . '/src/Resources/views/email'));
        $twig->addFilter(new TwigFilter('trans', static fn (string $id): string => $id));
        $twig->addFunction(new TwigFunction('url', static fn (string $route, array $parameters = []): string => 'https://shop.example.com/admin/cookies/edit'));

        $cookie = new Cookie();
        $cookie->setName('_ga');

        $html = $twig->load('new_cookies.html.twig')->renderBlock('body', ['cookies' => [$cookie]]);

        self::assertStringContainsString('<li><a href="https://shop.example.com/admin/cookies/edit">_ga</a></li>', $html);
    }
}
