<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Template;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

final class AdminTemplatesTest extends TestCase
{
    /**
     * @test
     */
    public function it_renders_http_urls_as_links(): void
    {
        $html = $this->render('grid/field/url.html.twig', ['data' => 'https://shop.example/en_US/?gclid=abc']);

        self::assertStringContainsString('<a href="https://shop.example/en_US/?gclid=abc"', $html);
    }

    /**
     * @test
     */
    public function it_does_not_render_other_urls_as_links(): void
    {
        $html = $this->render('grid/field/url.html.twig', ['data' => 'javascript:alert(document.domain)']);

        self::assertStringNotContainsString('<a ', $html);
        self::assertStringContainsString('javascript:alert(document.domain)', $html);
    }

    /**
     * @test
     */
    public function it_escapes_the_channel_name_in_the_widget_config_information(): void
    {
        $html = $this->render('widget_config/index_header.html.twig', [
            'sylius' => ['channel' => ['name' => '<script>alert(1)</script>'], 'localeCode' => 'en_US'],
        ]);

        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function render(string $template, array $context): string
    {
        $twig = new Environment(new FilesystemLoader(dirname(__DIR__, 2) . '/src/Resources/views/admin'));

        // Mimics the translator for a message containing HTML and placeholders, which the templates output with |raw
        $twig->addFilter(new TwigFilter('trans', static fn (string $id, array $parameters = []): string => strtr(sprintf('<p>%s %s</p>', $id, implode(' ', array_keys($parameters))), $parameters)));

        return $twig->render($template, $context);
    }
}
