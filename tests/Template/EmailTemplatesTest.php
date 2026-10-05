<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Template;

use PHPUnit\Framework\TestCase;
use Setono\SyliusConsentManagementPlugin\Model\Cookie;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

final class EmailTemplatesTest extends TestCase
{
    /**
     * @test
     */
    public function it_links_each_new_cookie_to_the_admin(): void
    {
        $cookie = new Cookie();
        $cookie->setName('_ga');

        $html = self::createTwig()->load('new_cookies.html.twig')->renderBlock('body', ['cookies' => [$cookie]]);

        self::assertStringContainsString('<li><a href="https://shop.example.com/admin/cookies/edit">_ga</a></li>', $html);
    }

    /**
     * @test
     */
    public function it_translates_the_new_cookies_email(): void
    {
        $template = self::createTwig()->load('new_cookies.html.twig');

        self::assertSame('IMPORTANT: New cookies discovered in your store', trim($template->renderBlock('subject', ['cookies' => []])));

        $body = $template->renderBlock('body', ['cookies' => []]);
        self::assertStringContainsString('<p>New cookies have been discovered in your store:</p>', $body);
        // An untranslated message would be rendered as its key
        self::assertStringNotContainsString('setono_sylius_consent_management.', $body);
    }

    private static function createTwig(): Environment
    {
        $translator = new Translator('en');
        $translator->addLoader('yaml', new YamlFileLoader());
        $translator->addResource('yaml', dirname(__DIR__, 2) . '/src/Resources/translations/messages.en.yaml', 'en');

        $twig = new Environment(new FilesystemLoader(dirname(__DIR__, 2) . '/src/Resources/views/email'));
        $twig->addExtension(new TranslationExtension($translator));
        $twig->addFunction(new TwigFunction('url', static fn (string $route, array $parameters = []): string => 'https://shop.example.com/admin/cookies/edit'));

        return $twig;
    }
}
