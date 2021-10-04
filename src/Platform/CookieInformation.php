<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Platform;

use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use const JSON_THROW_ON_ERROR;
use JsonException;
use Psl\Type;
use Setono\SyliusConsentManagementPlugin\Model\FormerConsent;
use Throwable;
use Webmozart\Assert\Assert;

final class CookieInformation implements PlatformInterface
{
    public function supports(string $cookieName, string $cookieValue): bool
    {
        return 'CookieInformationConsent' === $cookieName;
    }

    public function getFormerConsent(string $cookieValue): ?FormerConsent
    {
        $data = self::parseCookieValue($cookieValue);

        try {
            $data = Type\shape([
                'timestamp' => Type\string(),
                'consent_url' => Type\string(),
                'user_agent' => Type\string(),
                'consents_approved' => Type\dict(Type\int(), Type\string()),
                'user_uid' => Type\string(),
            ], true)->assert($data);
        } catch (Type\Exception\AssertException $e) {
            return null;
        }

        $marketingGranted = in_array('cookie_cat_marketing', $data['consents_approved'], true);
        $preferencesGranted = in_array('cookie_cat_functional', $data['consents_approved'], true);
        $statisticsGranted = in_array('cookie_cat_statistic', $data['consents_approved'], true);

        try {
            $createdAt = self::parseTimestamp($data['timestamp']);
        } catch (Throwable $e) {
            return null;
        }

        $formerConsent = new FormerConsent($marketingGranted, $preferencesGranted, $statisticsGranted);
        $formerConsent->clientId = $data['user_uid'];
        $formerConsent->createdAt = $createdAt;
        $formerConsent->userAgent = $data['user_agent'];
        $formerConsent->url = $data['consent_url'];

        return $formerConsent;
    }

    private static function parseCookieValue(string $cookieValue): array
    {
        $json = rawurldecode($cookieValue);

        try {
            $result = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($result)) {
                return [];
            }

            return $result;
        } catch (JsonException $e) {
            return [];
        }
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    private static function parseTimestamp(string $timestamp): DateTimeImmutable
    {
        $dateTime = DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.ve', $timestamp);
        Assert::isInstanceOf($dateTime, DateTimeImmutable::class);

        return $dateTime;
    }
}
