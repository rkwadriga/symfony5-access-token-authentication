<?php declare(strict_types=1);

namespace App\Helpers;

use Faker\Factory;
use Symfony\Component\Security\Core\User\UserInterface;

class AccessTokenHelper
{
    const ACCESS_TOKEN_LIFE_TIME = '1 hour';
    const REFRESH_TOKEN_LIFE_TIME = '3 month';

    public static function generateAccessToken(UserInterface $entity): string
    {
        $faker = Factory::create();
        $string = sprintf('_%s:%s-%s=', $entity->getUsername(), $faker->uuid,  microtime(true));
        return hash('sha256', $string);
    }

    public static function getAccessTokenExpiredAt(?\DateTime $createdAt = null): \DateTime
    {
        if ($createdAt === null) {
            $createdAt = new \DateTime();
        }
        return $createdAt->add(\DateInterval::createFromDateString('+' . self::ACCESS_TOKEN_LIFE_TIME));
    }

    public static function isRefreshTokenExpired(\DateTime $updatedAt): bool
    {
        $expiredAt = clone $updatedAt;
        $expiredAt->add(\DateInterval::createFromDateString('+' . self::REFRESH_TOKEN_LIFE_TIME));
        return $expiredAt < new \DateTime();
    }
}