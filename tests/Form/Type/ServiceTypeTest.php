<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Form\Type;

use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusConsentManagementPlugin\Form\Type\ServiceTranslationType;
use Setono\SyliusConsentManagementPlugin\Form\Type\ServiceType;
use Setono\SyliusConsentManagementPlugin\Model\Service;
use Setono\SyliusConsentManagementPlugin\Model\ServiceTranslation;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @covers \Setono\SyliusConsentManagementPlugin\Form\Type\ServiceType
 *
 * See https://symfony.com/doc/current/form/unit_testing.html
 */
final class ServiceTypeTest extends TypeTestCase
{
    use ProphecyTrait;

    protected function getExtensions(): array
    {
        $localeProvider = new class() implements TranslationLocaleProviderInterface {
            public function getDefaultLocaleCode(): string
            {
                return 'en_US';
            }

            public function getDefinedLocalesCodes(): array
            {
                return ['en_US'];
            }
        };

        $serviceType = new ServiceType(Service::class);
        $serviceTranslationType = new ServiceTranslationType(ServiceTranslation::class);
        $resourceTranslationType = new ResourceTranslationsType($localeProvider);

        $channel = new Channel();
        $channel->setCode('FASHION_WEB');

        $channelRepository = $this->prophesize(ChannelRepositoryInterface::class);
        $channelRepository->findAll()->willReturn([$channel]);
        $channelChoiceType = new ChannelChoiceType($channelRepository->reveal());

        return [
            new PreloadedExtension([$serviceType, $serviceTranslationType, $resourceTranslationType, $channelChoiceType], []),
        ];
    }

    /**
     * @test
     */
    public function submit_valid_data(): void
    {
        $model = new Service();
        $form = $this->factory->create(ServiceType::class, $model);

        $form->submit([
            'category' => 'marketing',
            'translations' => [
                'en_US' => [
                    'name' => 'name',
                    'description' => 'description',
                ],
            ],
            'privacyPolicy' => 'https://example.com/privacy-policy.html',
            'enabled' => '1',
            'channels' => ['FASHION_WEB'],
        ]);

        self::assertTrue($form->isSynchronized());

        $expected = new Service();
        $expected->getTranslation('en_US')->setDescription('description');
        $expected->setPrivacyPolicy('https://example.com/privacy-policy.html');
        $expected->enable();

        self::assertSame('marketing', $model->getCategory());
        self::assertSame('name', $model->getTranslation('en_US')->getName());
        self::assertSame('description', $model->getTranslation('en_US')->getDescription());
        self::assertTrue($model->isEnabled());
    }
}
