<?php

/**
 * The majority of the code for EncryptedCookiePersistence comes from:
 * https://github.com/mezzio/mezzio-session-cache
 *
 * Thus these tests focus on things not covered in:
 * https://github.com/mezzio/mezzio-session-cache/blob/master/test/CacheSessionPersistenceTest.php
 *
 * i.e. The specific amends we've made.
 *
 * This test doesn't mock the encryption. To do so would require the BlockCipher to be injected in, in an area
 * where we'd rather reduce moving parts. We also want a way to detect any changes made to the BlockCipher algorithm
 * and mode.
 */

declare(strict_types=1);

namespace CommonTest\Service\Session;

use PHPUnit\Framework\Attributes\Test;
use Common\Service\Session\EncryptedCookiePersistence;
use Common\Service\Session\Encryption\EncryptInterface;
use Common\Service\Session\KeyManager\Key;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Crypt\BlockCipher;
use Mezzio\Authentication\UserInterface;
use Mezzio\Session\Session;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use ParagonIE\HiddenString\HiddenString;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class EncryptedCookiePersistenceTest extends TestCase
{
    use ProphecyTrait;

    /*
     * Name given to the cookie
     */
    private const COOKIE_NAME = 'test-cookie-name';

    /*
     * The cookie's path
     */
    private const COOKIE_PATH = '/';

    /*
     * Number of seconds before the session expires.
     */
    private const SESSION_EXPIRES = 60;

    /*
     * Number of seconds before the cookie expires.
     */
    private const COOKIE_EXPIRES = 600;

    private ObjectProphecy|EncryptInterface $encrypterProphecy;

    private Key $testKey;

    public function setUp(): void
    {
        // A real key used within the tests. It doesn't matter what it is.
        $this->testKey = new Key('test-id', new EncryptionKey(new HiddenString(random_bytes(32))));

        $this->encrypterProphecy = $this->prophesize(EncryptInterface::class);
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $cp = new EncryptedCookiePersistence(
            $this->encrypterProphecy->reveal(),
            self::COOKIE_NAME,
            self::COOKIE_PATH,
            'nocache',
            self::SESSION_EXPIRES,
            null,
            self::COOKIE_EXPIRES,
            null,
            true,
            true
        );
        $this->assertInstanceOf(EncryptedCookiePersistence::class, $cp);
    }

    #[Test]
    public function it_creates_a_session_when_no_data_in_request(): void
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        /*
         * From Psr\Http\Message;
         * If the header does not appear in the message, this method MUST return an empty array
         */
        $requestProphecy->getHeaderLine('Cookie')->willReturn('')->shouldBeCalled();

        $this->encrypterProphecy->decodeCookieValue('')->willReturn([]);

        //---

        $cp = new EncryptedCookiePersistence(
            $this->encrypterProphecy->reveal(),
            self::COOKIE_NAME,
            self::COOKIE_PATH,
            'nocache',
            self::SESSION_EXPIRES,
            null,
            self::COOKIE_EXPIRES,
            null,
            true,
            true
        );

        $session = $cp->initializeSessionFromRequest($requestProphecy->reveal());

        // The returned session should be empty
        $this->assertEmpty($session->toArray());
    }

    //--------------------------------------------------------------------------------------------------
    // Test writing session data into a response.
    #[Test]
    public function it_persists_a_session_with_data(): void
    {
        $testData = [
            'bool'   => true,
            'string' => 'here',
            'int'    => 123,
            'float'  => 123.9,
        ];

        $responseProphecy = $this->prophesize(ResponseInterface::class);

        // Boiler plate
        $responseProphecy->hasHeader('Expires')->willReturn(false);
        $responseProphecy->hasHeader('Last-Modified')->willReturn(false);
        $responseProphecy->hasHeader('Cache-Control')->willReturn(false);
        $responseProphecy->hasHeader('Pragma')->willReturn(false);
        $responseProphecy->getHeader('Set-Cookie')->willReturn([]);

        // Test the specific response
        $responseProphecy->withoutHeader('Set-Cookie')->willReturn($responseProphecy->reveal())->shouldBeCalled();
        $responseProphecy->withHeader('Pragma', 'no-cache')->willReturn($responseProphecy->reveal())->shouldBeCalled();
        $responseProphecy->withHeader('Expires', 'Thu, 19 Nov 1981 08:52:00 GMT')
            ->willReturn($responseProphecy->reveal())->shouldBeCalled();
        $responseProphecy->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->willReturn($responseProphecy->reveal())->shouldBeCalled();

        // Dig into the detail around the cookie that was set
        // Essentially a test that the FigCookies library works. Not sure we need it
        $responseProphecy->withAddedHeader(
            'Set-Cookie',
            Argument::that(
                function ($input) {
                    $this->assertIsString($input);

                    $cookieName = self::COOKIE_NAME;
                    $cookiePath = self::COOKIE_PATH;

                    $regexCookie  = "^{$cookieName}=(.+?);";
                    $regexPath    = "Path={$cookiePath};";
                    $regexExpires = 'Expires=([\w]{3}, \d+ [\w]{3} \d{4} \d{2}:\d{2}:\d{2} \w+);';

                    // Validate the full pattern
                    $this->assertMatchesRegularExpression(
                        "|{$regexCookie} {$regexPath} {$regexExpires} Secure; HttpOnly; SameSite=None$|",
                        $input
                    );

                    //--------
                    // Extract the cookie data

                    preg_match("|{$regexCookie}|", $input, $matches);

                    // Decompose the value into the key id, and the data
                    [$keyId, $payload] = explode('.', $matches[1], 2);

                    $this->assertEquals('ENCRYPTED', $keyId);
                    $this->assertEquals('CIPHER_TEXT', $payload);

                    //--------
                    // Extract the Expires time

                    preg_match("|{$regexExpires}|", $input, $matches);

                    $time = strtotime($matches[1]);

                    // Check it
                    $this->assertEquals(time() + self::COOKIE_EXPIRES, $time, '', 3);

                    return true;
                }
            )
        )->willReturn($responseProphecy->reveal())->shouldBeCalled();

        $this->encrypterProphecy->encodeCookieValue(
            Argument::that(function ($input) use ($testData) {
                $this->assertArrayHasKey('int', $input);
                $this->assertArrayHasKey('string', $input);
                $this->assertArrayHasKey('bool', $input);
                $this->assertArrayHasKey('float', $input);

                $this->assertEquals($testData['int'], $input['int']);
                $this->assertEquals($testData['string'], $input['string']);
                $this->assertEquals($testData['float'], $input['float']);
                $this->assertEquals($testData['bool'], $input['bool']);

                return true;
            })
        )->willReturn('ENCRYPTED.CIPHER_TEXT');

        $cp = new EncryptedCookiePersistence(
            $this->encrypterProphecy->reveal(),
            self::COOKIE_NAME,
            self::COOKIE_PATH,
            'nocache',
            self::SESSION_EXPIRES,
            null,
            self::COOKIE_EXPIRES,
            null,
            true,
            true
        );

        // We use a concrete session object here. It just moves data around; that's no test benefit to mocking it
        $response = $cp->persistSession(new Session($testData), $responseProphecy->reveal());

        // We expect the response we put in to be returned
        $this->assertEquals($responseProphecy->reveal(), $response);
    }

    #[Test]
    public function it_persists_a_session_same_site_strict_when_user_logged_in(): void
    {
        $testData = [
            'bool'   => true,
            'string' => 'here',
            'int'    => 123,
            'float'  => 123.9,
        ];

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(StatusCodeInterface::STATUS_OK);

        // Boiler plate
        $responseProphecy->hasHeader('Expires')->willReturn(false);
        $responseProphecy->hasHeader('Last-Modified')->willReturn(false);
        $responseProphecy->hasHeader('Cache-Control')->willReturn(false);
        $responseProphecy->hasHeader('Pragma')->willReturn(false);
        $responseProphecy->getHeader('Set-Cookie')->willReturn([]);

        // Test the specific response
        $responseProphecy->withoutHeader('Set-Cookie')->willReturn($responseProphecy->reveal())->shouldBeCalled();
        $responseProphecy->withHeader('Pragma', 'no-cache')->willReturn($responseProphecy->reveal())->shouldBeCalled();
        $responseProphecy->withHeader('Expires', 'Thu, 19 Nov 1981 08:52:00 GMT')
            ->willReturn($responseProphecy->reveal())->shouldBeCalled();
        $responseProphecy->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->willReturn($responseProphecy->reveal())->shouldBeCalled();

        // Dig into the detail around the cookie that was set
        // Essentially a test that the FigCookies library works. Not sure we need it
        $responseProphecy->withAddedHeader(
            'Set-Cookie',
            Argument::that(
                function ($input) {
                    $this->assertIsString($input);

                    $cookieName = self::COOKIE_NAME;
                    $cookiePath = self::COOKIE_PATH;

                    $regexCookie  = "^{$cookieName}=(.+?);";
                    $regexPath    = "Path={$cookiePath};";
                    $regexExpires = 'Expires=([\w]{3}, \d+ [\w]{3} \d{4} \d{2}:\d{2}:\d{2} \w+);';

                    // Validate the full pattern
                    $this->assertMatchesRegularExpression(
                        "|{$regexCookie} {$regexPath} {$regexExpires} Secure; HttpOnly; SameSite=None$|",
                        $input
                    );

                    //--------
                    // Extract the cookie data

                    preg_match("|{$regexCookie}|", $input, $matches);

                    // Decompose the value into the key id, and the data
                    [$keyId, $payload] = explode('.', $matches[1], 2);

                    $this->assertEquals('ENCRYPTED', $keyId);
                    $this->assertEquals('CIPHER_TEXT', $payload);

                    //--------
                    // Extract the Expires time

                    preg_match("|{$regexExpires}|", $input, $matches);

                    $time = strtotime($matches[1]);

                    // Check it
                    $this->assertEquals(time() + self::COOKIE_EXPIRES, $time, '', 3);

                    return true;
                }
            )
        )->willReturn($responseProphecy->reveal())->shouldBeCalled();

        $this->encrypterProphecy->encodeCookieValue(
            Argument::that(function ($input) use ($testData) {
                $this->assertArrayHasKey('int', $input);
                $this->assertArrayHasKey('string', $input);
                $this->assertArrayHasKey('bool', $input);
                $this->assertArrayHasKey('float', $input);

                $this->assertEquals($testData['int'], $input['int']);
                $this->assertEquals($testData['string'], $input['string']);
                $this->assertEquals($testData['float'], $input['float']);
                $this->assertEquals($testData['bool'], $input['bool']);

                return true;
            })
        )->willReturn('ENCRYPTED.CIPHER_TEXT');

        $cp = new EncryptedCookiePersistence(
            $this->encrypterProphecy->reveal(),
            self::COOKIE_NAME,
            self::COOKIE_PATH,
            'nocache',
            self::SESSION_EXPIRES,
            null,
            self::COOKIE_EXPIRES,
            null,
            true,
            true
        );

        $testData[UserInterface::class] = '';

        // We use a concrete session object here. It just moves data around; that's no test benefit to mocking it
        $response = $cp->persistSession(new Session($testData), $responseProphecy->reveal());

        // We expect the response we put in to be returned
        $this->assertEquals($responseProphecy->reveal(), $response);
    }

    #[Test]
    public function it_persists_a_session_with_no_data(): void
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        // Boiler plate
        $responseProphecy->hasHeader('Expires')->willReturn(false);
        $responseProphecy->hasHeader('Last-Modified')->willReturn(false);
        $responseProphecy->hasHeader('Cache-Control')->willReturn(false);
        $responseProphecy->hasHeader('Pragma')->willReturn(false);
        $responseProphecy->getHeader('Set-Cookie')->willReturn([]);

        // Test the specific response
        $responseProphecy->withoutHeader('Set-Cookie')->willReturn($responseProphecy->reveal())->shouldBeCalled();
        $responseProphecy->withHeader('Pragma', 'no-cache')->willReturn($responseProphecy->reveal())->shouldBeCalled();
        $responseProphecy->withHeader('Expires', 'Thu, 19 Nov 1981 08:52:00 GMT')
            ->willReturn($responseProphecy->reveal())->shouldBeCalled();
        $responseProphecy->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->willReturn($responseProphecy->reveal())->shouldBeCalled();

        $responseProphecy->withAddedHeader(
            'Set-Cookie',
            Argument::that(
                function ($input) {
                    $this->assertIsString($input);

                    $cookieName    = self::COOKIE_NAME;
                    $patternCookie = "^{$cookieName}=(.+?);";

                    //--------
                    // Extract the cookie data

                    preg_match("|{$patternCookie}|", $input, $matches);

                    // Decompose the value into the key id, and the data
                    [$keyId, $payload] = explode('.', $matches[1], 2);

                    $this->assertEquals('ENCRYPTED', $keyId);
                    $this->assertEquals('CIPHER_TEXT', $payload);

                    return true;
                }
            )
        )->willReturn($responseProphecy->reveal())->shouldBeCalled();

        $this->encrypterProphecy->encodeCookieValue(Argument::type('array'))->willReturn('ENCRYPTED.CIPHER_TEXT');

        $cp = new EncryptedCookiePersistence(
            $this->encrypterProphecy->reveal(),
            self::COOKIE_NAME,
            self::COOKIE_PATH,
            'nocache',
            self::SESSION_EXPIRES,
            null,
            self::COOKIE_EXPIRES,
            null,
            true,
            true
        );

        // We use a concrete session object here. It just moves data around; there's no test benefit to mocking it
        $response = $cp->persistSession(new Session([]), $responseProphecy->reveal());

        // We expect the response we put in to be returned
        $this->assertEquals($responseProphecy->reveal(), $response);
    }


    //--------------------------------------------------------------------------------------------------
    // Test reading session data out of a request.
    /**
     * When cookie data is present along with a valid time, we expect the included data to be returned in the session.
     *
     * Note: We never rely on the `Expires` field in the cookie. This is for the browser, not for us.
     */
    #[Test]
    public function a_valid_session_unexpired_session_cookie_will_contain_data(): void
    {
        $testData = [
            'bool'                                       => true,
            'string'                                     => 'here',
            'int'                                        => 123,
            'float'                                      => 123.9,
            EncryptedCookiePersistence::SESSION_TIME_KEY => time(),
        ];

        $value = 'ENCRYPTED.CIPHER_TEXT';

        $this->encrypterProphecy->decodeCookieValue($value)->willReturn($testData);

        $testCookieValue =
            'test-cookie-name=' . $value . '; Path=/; Expires=Wed, 08 May 2019 15:34:49 GMT; Secure; HttpOnly';

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $requestProphecy->getHeaderLine('Cookie')->willReturn($testCookieValue);


        $cp = new EncryptedCookiePersistence(
            $this->encrypterProphecy->reveal(),
            self::COOKIE_NAME,
            self::COOKIE_PATH,
            'nocache',
            self::SESSION_EXPIRES,
            null,
            self::COOKIE_EXPIRES,
            null,
            true,
            true
        );

        $session = $cp->initializeSessionFromRequest($requestProphecy->reveal());

        // The returned session should exactly match that which was passed in.
        $this->assertEquals($testData, $session->toArray());
    }

    /**
     * This is copied across from EncryptedCookiePersistence.
     * We want our own copy in the test to allow us to fail if it's changed.
     *
     * @return BlockCipher
     */
    private function getBlockCipher(): BlockCipher
    {
        return BlockCipher::factory(
            'openssl',
            [
                'algo' => 'aes',
                'mode' => 'gcm',
            ]
        )->setBinaryOutput(true);
    }
}
