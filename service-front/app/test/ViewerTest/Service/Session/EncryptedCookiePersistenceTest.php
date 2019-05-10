<?php
/**
 * The majority of the code for EncryptedCookiePersistence comes from:
 * https://github.com/zendframework/zend-expressive-session-cache
 *
 * Thus these tests focus on things not covered in:
 * https://github.com/zendframework/zend-expressive-session-cache/blob/master/test/CacheSessionPersistenceTest.php
 *
 * i.e. The specific amends we've made.
 *
 * This test doesn't mock the encryption. To do so would require the BlockCipher to be injected in, in an area
 * where we'd rather reduce moving parts. We also want a way to detect any changes made to the BlockCipher algorithm
 * and mode.
 *
 */
declare(strict_types=1);

namespace ZendTest\Expressive\Session\Cache;

use ParagonIE\ConstantTime\Base64UrlSafe;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Prophecy\Prophecy\ObjectProphecy;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use ParagonIE\HiddenString\HiddenString;
use Viewer\Service\Session\Config;
use Viewer\Service\Session\EncryptedCookiePersistence;
use Viewer\Service\Session\KeyManager\Key;
use Viewer\Service\Session\KeyManager\KeyManagerInterface;
use PHPUnit\Framework\TestCase;
use Zend\Crypt\BlockCipher;
use Zend\Expressive\Session\Session;

class EncryptedCookiePersistenceTest extends TestCase
{
    /*
     * Name given to the cookie
     */
    const COOKIE_NAME = 'test-cookie-name';

    /*
     * The cookie's path
     */
    const COOKIE_PATH = '/';

    /*
     * Number of seconds before the session expires.
     */
    const SESSION_EXPIRES = 1000;

    /**
     * @var ObjectProphecy|Config
     */
    private $configProphecy;

    /**
     * @var ObjectProphecy|KeyManagerInterface
     */
    private $keyManagerProphecy;

    /**
     * @var Key
     */
    private $testKey;


    public function setUp()
    {
        // A real key used within the tests. It doesn't matter what it is.
        $this->testKey = new Key('test-id', new EncryptionKey(new HiddenString(random_bytes(32))));

        $this->configProphecy = $this->prophesize(Config::class);
        $this->keyManagerProphecy = $this->prophesize(KeyManagerInterface::class);

        // Setup the default config
        $this->configProphecy->getCookieName()->willReturn(self::COOKIE_NAME);
        $this->configProphecy->getCookiePath()->willReturn(self::COOKIE_PATH);
        $this->configProphecy->getCacheLimiter()->willReturn('nocache');
        $this->configProphecy->getSessionExpired()->willReturn(self::SESSION_EXPIRES);
        $this->configProphecy->getLastModified()->willReturn(null);
        $this->configProphecy->getPersistent()->willReturn(false);
        $this->configProphecy->getCookieDomain()->willReturn(null);
        $this->configProphecy->getCookieSecure()->willReturn(true);
        $this->configProphecy->getCookieHttpOnly()->willReturn(true);

        // Setup the key manager mock
        $this->keyManagerProphecy->getEncryptionKey()->willReturn($this->testKey);
        $this->keyManagerProphecy->getDecryptionKey($this->testKey->getId())->willReturn($this->testKey);
    }

    /**
     * This is copied across from EncryptedCookiePersistence.
     * We want our own copy in the test to allow us to fail if it's changed.
     *
     * @return BlockCipher
     */
    private function getBlockCipher() : BlockCipher
    {
        return BlockCipher::factory('openssl', [
            'algo' => 'aes',
            'mode' => 'gcm'
        ])->setBinaryOutput(true);
    }

    public function testCanInstantiate()
    {
        $cp = new EncryptedCookiePersistence($this->keyManagerProphecy->reveal(), $this->configProphecy->reveal());
        $this->assertInstanceOf(EncryptedCookiePersistence::class, $cp);
    }

    //--------------------------------------------------------------------------------------------------
    // Test writing session data into a response.

    /**
     * We expect no methods to be called on the $responseProphecy.
     * i.e. the response is returned unaltered.
     */
    public function testPersistingSessionWithoutData()
    {
        $responseProphecy = $this->prophesize( ResponseInterface::class );

        $cp = new EncryptedCookiePersistence($this->keyManagerProphecy->reveal(), $this->configProphecy->reveal());

        // We use a concrete session object here. It just moves data around; there's no test benefit to mocking it
        $response = $cp->persistSession(new Session([]), $responseProphecy->reveal());

        // We expect the response we put in to be returned
        $this->assertEquals($responseProphecy->reveal(), $response);
    }

    /**
     * We expect $responseProphecy to be checked for its current state.
     * Then all required new values set, including the `Set-Cookie` header containing the session data.
     */
    public function testPersistingSessionWithData()
    {
        $testData = [
            'bool'=>true,
            'string'=>'here',
            'int'=>123,
            'float'=>123.9,
        ];

        $responseProphecy = $this->prophesize( ResponseInterface::class );

        // Boiler plate
        $responseProphecy->hasHeader('Expires')->willReturn(false);
        $responseProphecy->hasHeader('Last-Modified')->willReturn(false);
        $responseProphecy->hasHeader('Cache-Control')->willReturn(false);
        $responseProphecy->hasHeader('Pragma')->willReturn(false);
        $responseProphecy->getHeader('Set-Cookie')->willReturn([]);


        // Test the specific response
        $responseProphecy->withoutHeader('Set-Cookie')->willReturn($responseProphecy->reveal())->shouldBeCalled();
        $responseProphecy->withHeader('Pragma', 'no-cache')->willReturn($responseProphecy->reveal())->shouldBeCalled();
        $responseProphecy->withHeader('Expires', 'Thu, 19 Nov 1981 08:52:00 GMT')->willReturn($responseProphecy->reveal())->shouldBeCalled();
        $responseProphecy->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate')->willReturn($responseProphecy->reveal())->shouldBeCalled();

        // Dig into the detail around the cookie that was set
        $responseProphecy->withAddedHeader('Set-Cookie', Argument::that(function ($input) use ($testData) {

            $this->assertIsString($input);

            $cookieName = self::COOKIE_NAME;
            $cookiePath = self::COOKIE_PATH;

            $patternCookie = "^{$cookieName}=(.+?);";
            $patternPath = "Path={$cookiePath};";
            $patternExpires = "Expires=([\w]{3}, \d+ [\w]{3} \d{4} \d{2}:\d{2}:\d{2} \w+);";
            $patternSecure = "Secure;";
            $patternHttpOnly = "HttpOnly$";

            // Validate the full pattern
            $this->assertRegExp("|{$patternCookie} {$patternPath} {$patternExpires} {$patternSecure} {$patternHttpOnly}|", $input);

            //--------
            // Extract the cookie data

            preg_match("|{$patternCookie}|", $input, $matches);

            // Decompose teh value into the key id, and the data
            list($keyId, $payload) = explode('.', $matches[1], 2);

            $this->assertEquals($this->testKey->getId(), $keyId);

            $ciphertext = Base64UrlSafe::decode($payload);

            $plaintext = $this->getBlockCipher()->setKey($this->testKey->getKeyMaterial())->decrypt($ciphertext);

            $value = json_decode($plaintext, true);

            $this->assertArrayHasKey('int', $value);
            $this->assertArrayHasKey('string', $value);
            $this->assertArrayHasKey('bool', $value);
            $this->assertArrayHasKey('float', $value);
            $this->assertArrayHasKey(EncryptedCookiePersistence::SESSION_TIME_KEY, $value);

            $this->assertEquals($testData['int'], $value['int']);
            $this->assertEquals($testData['string'], $value['string']);
            $this->assertEquals($testData['float'], $value['float']);
            $this->assertEquals($testData['bool'], $value['bool']);

            $this->assertEquals(time(), $value[EncryptedCookiePersistence::SESSION_TIME_KEY], '', 3);

            //--------
            // Extract the Expires time

            preg_match("|{$patternExpires}|", $input, $matches);

            $time = strtotime($matches[1]);

            // Check it
            $this->assertEquals(time() + self::SESSION_EXPIRES, $time, '', 3);

            return true;
        }))->willReturn($responseProphecy->reveal())->shouldBeCalled();

        $cp = new EncryptedCookiePersistence($this->keyManagerProphecy->reveal(), $this->configProphecy->reveal());

        // We use a concrete session object here. It just moves data around; that's no test benefit to mocking it
        $response = $cp->persistSession(new Session($testData), $responseProphecy->reveal());

        // We expect the response we put in to be returned
        $this->assertEquals($responseProphecy->reveal(), $response);
    }


    //--------------------------------------------------------------------------------------------------
    // Test reading session data out of a request.

    /**
     * When no cookie data is present in the header, we expect an empty/new session to be returned.
     */
    public function testReadingSessionDataWhenThereIsNone()
    {
        $requestProphecy = $this->prophesize( ServerRequestInterface::class );

        /*
         * From Psr\Http\Message;
         * If the header does not appear in the message, this method MUST return an empty array
         */
        $requestProphecy->getHeaderLine('Cookie')->willReturn('')->shouldBeCalled();

        //---

        $cp = new EncryptedCookiePersistence($this->keyManagerProphecy->reveal(), $this->configProphecy->reveal());

        $session = $cp->initializeSessionFromRequest($requestProphecy->reveal());

        // The returned session should be empty
        $this->assertEmpty($session->toArray());
    }

    /**
     * When cookie data is present, but the time the cookie was created is missing, the session becomes invalid.
     * We expect an empty/new session to be returned.
     *
     * Note: We never rely on the `Expires` field in the cookie. This is for the browser, not for us.
     */
    public function testReadingSessionDataDirectFromTheHeaderWithNoTime()
    {
        $testData = [
            'bool'=>true,
            'string'=>'here',
            'int'=>123,
            'float'=>123.9,
        ];

        $plaintext = json_encode($testData);
        $ciphertext = $this->getBlockCipher()->setKey($this->testKey->getKeyMaterial())->encrypt($plaintext);
        $value = $this->testKey->getId() . '.' . Base64UrlSafe::encode($ciphertext);

        $testCookieValue = 'test-cookie-name='.$value.'; Path=/; Expires=Wed, 08 May 2019 15:34:49 GMT; Secure; HttpOnly';

        $requestProphecy = $this->prophesize( ServerRequestInterface::class );


        $requestProphecy->getHeaderLine('Cookie')->willReturn($testCookieValue);


        //---

        $cp = new EncryptedCookiePersistence($this->keyManagerProphecy->reveal(), $this->configProphecy->reveal());

        $session = $cp->initializeSessionFromRequest($requestProphecy->reveal());

        // The returned session should be empty
        $this->assertEmpty($session->toArray());
    }

    /**
     * When cookie data is present along with a valid time, we expect the included data to be returned in the session.
     *
     * Note: We never rely on the `Expires` field in the cookie. This is for the browser, not for us.
     */
    public function testReadingSessionDataDirectFromTheHeaderWithTime()
    {
        $testData = [
            'bool'=>true,
            'string'=>'here',
            'int'=>123,
            'float'=>123.9,
            EncryptedCookiePersistence::SESSION_TIME_KEY => time()
        ];

        $plaintext = json_encode($testData);
        $ciphertext = $this->getBlockCipher()->setKey($this->testKey->getKeyMaterial())->encrypt($plaintext);
        $value = $this->testKey->getId() . '.' . Base64UrlSafe::encode($ciphertext);

        $testCookieValue = 'test-cookie-name='.$value.'; Path=/; Expires=Wed, 08 May 2019 15:34:49 GMT; Secure; HttpOnly';

        $requestProphecy = $this->prophesize( ServerRequestInterface::class );


        $requestProphecy->getHeaderLine('Cookie')->willReturn($testCookieValue);


        //---

        $cp = new EncryptedCookiePersistence($this->keyManagerProphecy->reveal(), $this->configProphecy->reveal());

        $session = $cp->initializeSessionFromRequest($requestProphecy->reveal());

        // The returned session should exactly match that which was passed in.
        $this->assertEquals($testData, $session->toArray());
    }

    /**
     * When cookie data is present along with a valid time, but that time is outside of the allowed window,
     * we expect a empty/new session to be returned.
     *
     * i.e. the session has timed out.
     *
     * Note: We never rely on the `Expires` field in the cookie. This is for the browser, not for us.
     */
    public function testReadingSessionDataDirectFromTheHeaderWithExpiredTime()
    {
        $testData = [
            'bool'=>true,
            'string'=>'here',
            'int'=>123,
            'float'=>123.9,
            EncryptedCookiePersistence::SESSION_TIME_KEY => time() - self::SESSION_EXPIRES - 1  // 1 second after is should expire
        ];

        $plaintext = json_encode($testData);
        $ciphertext = $this->getBlockCipher()->setKey($this->testKey->getKeyMaterial())->encrypt($plaintext);
        $value = $this->testKey->getId() . '.' . Base64UrlSafe::encode($ciphertext);

        $testCookieValue = 'test-cookie-name='.$value.'; Path=/; Expires=Wed, 08 May 2019 15:34:49 GMT; Secure; HttpOnly';

        $requestProphecy = $this->prophesize( ServerRequestInterface::class );


        $requestProphecy->getHeaderLine('Cookie')->willReturn($testCookieValue);

        //---

        $cp = new EncryptedCookiePersistence($this->keyManagerProphecy->reveal(), $this->configProphecy->reveal());

        $session = $cp->initializeSessionFromRequest($requestProphecy->reveal());

        // The returned session should be empty
        $this->assertEmpty($session->toArray());
    }

}
