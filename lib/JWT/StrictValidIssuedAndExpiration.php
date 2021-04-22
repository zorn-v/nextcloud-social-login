<?php
declare(strict_types=1);

namespace OCA\SocialLogin\JWT;

use DateInterval;
use DateTimeInterface;
use Lcobucci\Clock\Clock;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint;
use Lcobucci\JWT\Validation\ConstraintViolation;

/**
 * This class is similar to Lcobucci\JWT\Validation\Constraint\StrictValidAt,
 * except it does not check the NOT_BEFORE field in the token, as e.g.
 * Keycloak does not set this field.
 */
final class StrictValidIssuedAndExpiration implements Constraint
{

    private Clock $clock;

    public function __construct(Clock $clock)
    {
        $this->clock  = $clock;
    }

    public function assert(Token $token): void
    {
        if (! $token instanceof UnencryptedToken) {
            throw new ConstraintViolation('You should pass a plain token');
        }

        $now = $this->clock->now();

        $this->assertIssueTime($token, $now);
        $this->assertExpiration($token, $now);
    }

    /** @throws ConstraintViolation */
    private function assertExpiration(UnencryptedToken $token, DateTimeInterface $now): void
    {
        if (! $token->claims()->has(Token\RegisteredClaims::EXPIRATION_TIME)) {
            throw new ConstraintViolation('"Expiration Time" claim missing');
        }

        if ($token->isExpired($now)) {
            throw new ConstraintViolation('The token is expired');
        }
    }

    /** @throws ConstraintViolation */
    private function assertIssueTime(UnencryptedToken $token, DateTimeInterface $now): void
    {
        if (! $token->claims()->has(Token\RegisteredClaims::ISSUED_AT)) {
            throw new ConstraintViolation('"Issued At" claim missing');
        }

        if (! $token->hasBeenIssuedBefore($now)) {
            throw new ConstraintViolation('The token was issued in the future');
        }
    }

}
