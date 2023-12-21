<?php

declare(strict_types=1);

namespace App\Service\Authentication\Token;

use Jose\Component\Checker\{AlgorithmChecker,
    AudienceChecker,
    ClaimChecker,
    ClaimCheckerManager,
    ExpirationTimeChecker,
    HeaderChecker,
    HeaderCheckerManager,
    IssuedAtChecker,
    IssuerChecker,
    NotBeforeChecker};
use Jose\Component\Core\{AlgorithmManager, JWK, Util\JsonConverter};
use Jose\Component\Signature\{Algorithm\ES256, JWSTokenSupport, JWSVerifier, Serializer\CompactSerializer};
use Psr\Clock\ClockInterface;
use RuntimeException;
use Throwable;

class OutOfBandCoreIdentityVerifier
{
    /** @var ClaimChecker[] */
    private array $claimCheckers;

    /** @var HeaderChecker[] */
    private array $headerCheckers;

    /** @var string[] */
    private array $mandatoryClaims;

    public function __construct(
        private JWK $signingKey,
        string $issuer,
        string $clientId,
        ClockInterface $clock,
    ) {
        $this->headerCheckers = [
            new AlgorithmChecker(['ES256'], true),
        ];

        $this->mandatoryClaims = ['sub', 'vc'];

        $this->claimCheckers = [
            new IssuerChecker([$issuer], true),
            new IssuedAtChecker(0, true, $clock),
            new AudienceChecker($clientId, true),
            new ExpirationTimeChecker(0, false, $clock),
            new NotBeforeChecker(0, true, $clock),
            // Add 'vot' == P2 custom checker
        ];
    }

    /**
     * @param string $jwt An encoded and signed JWT token
     * @return array{
     *     birthdate: array,
     *     name: array
     * } Identity data pulled from the Verified Credential claim of the passed in JWT
     * @throws Throwable
     */
    public function verify(string $jwt): array
    {
        $jws           = (new CompactSerializer())->unserialize($jwt);
        $headerChecker = new HeaderCheckerManager($this->headerCheckers, [new JWSTokenSupport()]);
        $headerChecker->check($jws, 0);

        $verifier = new JWSVerifier(new AlgorithmManager([new ES256()]));
        if (! $verifier->verifyWithKey($jws, $this->signingKey, 0)) {
            throw new RuntimeException('Invalid signature');
        }

        $claims = JsonConverter::decode($jws->getPayload() ?? '{}');

        $claimChecker = new ClaimCheckerManager($this->claimCheckers);
        $claimChecker->check($claims, $this->mandatoryClaims);

        return $claims['vc']['credentialSubject'];
    }
}
