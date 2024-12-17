<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Platform;

use DateTimeImmutable;
use Setono\SyliusConsentManagementPlugin\Model\FormerConsent;

final class Cookiebot implements PlatformInterface
{
    private const KEYS = ['stamp', 'necessary', 'preferences', 'statistics', 'marketing', 'ver', 'utc', 'region'];

    private ?array $data = null;

    public function supports(string $cookieName, string $cookieValue): bool
    {
        if ('CookieConsent' !== $cookieName) {
            return false;
        }

        $cookieData = $this->getDataFromCookieValue($cookieValue);

        if (!self::arrayHasKeys($cookieData, self::KEYS)) {
            return false;
        }

        return true;
    }

    public function getFormerConsent(string $cookieValue): ?FormerConsent
    {
        $data = $this->getDataFromCookieValue($cookieValue);
        if (!self::arrayHasKeys($data, self::KEYS)) {
            return null;
        }

        $clientId = $data['stamp'];
        if (!is_string($clientId)) {
            return null;
        }

        $marketingGranted = $data['marketing'];
        if (!is_bool($marketingGranted)) {
            return null;
        }

        $preferencesGranted = $data['preferences'];
        if (!is_bool($preferencesGranted)) {
            return null;
        }

        $statisticsGranted = $data['statistics'];
        if (!is_bool($statisticsGranted)) {
            return null;
        }

        $timestamp = $data['utc'];
        if (!is_int($timestamp)) {
            return null;
        }

        try {
            $createdAt = self::getDateTimeFromTimestamp($timestamp);
        } catch (\Throwable) {
            return null;
        }

        $formerConsent = new FormerConsent($marketingGranted, $preferencesGranted, $statisticsGranted);
        $formerConsent->clientId = $clientId;
        $formerConsent->createdAt = $createdAt;

        return $formerConsent;
    }

    private function getDataFromCookieValue(string $cookieValue): array
    {
        if (null === $this->data) {
            $this->data = self::parseCookieValue($cookieValue);
        }

        return $this->data;
    }

    private static function parseCookieValue(string $cookieValue): array
    {
        $badJson = str_replace("'", '"', rawurldecode($cookieValue));
        $json = preg_replace('/([a-z]+):/', '"$1":', $badJson);

        try {
            $result = json_decode((string) $json, true, 512, \JSON_THROW_ON_ERROR);
            if (!is_array($result)) {
                return [];
            }

            return $result;
        } catch (\JsonException) {
            return [];
        }
    }

    private static function getDateTimeFromTimestamp(int $timestamp): \DateTimeInterface
    {
        $timestamp = (int) round($timestamp / 1000);

        return new DateTimeImmutable('@' . $timestamp);
    }

    /**
     * Returns true if the $arr has ALL the given $keys
     *
     * @param array<array-key, string> $keys
     */
    private static function arrayHasKeys(array $arr, array $keys): bool
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $arr)) {
                return false;
            }
        }

        return true;
    }
}
