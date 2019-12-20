<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Bnomei\Redirects;
use PHPUnit\Framework\TestCase;

class RedirectsTest extends TestCase
{
    public function testConstruct()
    {
        $redirects = new Redirects();
        $this->assertInstanceOf(Redirects::class, $redirects);
    }

    public function testSingleton()
    {
        $redirects = Redirects::singleton();
        $this->assertInstanceOf( Redirects::class, $redirects);
    }

    public function testDoesNotRedirectOtherPage()
    {
        $options = [
            'site.url' => 'http://homestead.test/',
            'request.uri' => '/projects/ahmic',
        ];
        $redirects = new Redirects($options);

        $this->assertIsArray($redirects->option());
        $this->assertEquals('http://homestead.test/', $redirects->option('site.url'));
        $this->assertNull($redirects->option('does not exist'));

        $check = $redirects->checkForRedirect($redirects->option());
        $this->assertNull($check);
    }

    public function testRedirectPage()
    {
        $options = [
            'site.url' => 'http://homestead.test/',
            'request.uri' => '/building/ahmic',
        ];
        $redirects = new Redirects($options);
        $check = $redirects->checkForRedirect($redirects->option());
        $this->assertTrue($check->code() === 301);
    }

    public function testRedirectExtension()
    {
        $options = [
            'site.url' => 'http://homestead.test/',
            'request.uri' => '/building/ahmic.html',
        ];
        $redirects = new Redirects($options);
        $check = $redirects->checkForRedirect($redirects->option());
        $this->assertTrue($check->code() === 302);
    }

    public function testRedirectQuery()
    {
        $options = [
            'site.url' => 'http://homestead.test/',
            'request.uri' => '/projects?id=1',
        ];
        $redirects = new Redirects($options);
        $check = $redirects->checkForRedirect($redirects->option());
        $this->assertTrue($check->code() === 303);
    }

    public function testRedirectExternal()
    {
        $options = [
            'site.url' => 'http://homestead.test/',
            'request.uri' => '/projects/external',
        ];
        $redirects = new Redirects($options);
        $check = $redirects->checkForRedirect($redirects->option());
        $this->assertTrue($check->code() === 301);
    }

    public function testStaticCodes()
    {
        $codes = Redirects::codes();
        $this->assertIsArray($codes);
        $this->assertCount(25, $codes);
    }

    public function testStaticCodesForced()
    {
        $codes = Redirects::codes(true);
        $this->assertIsArray($codes);
        $this->assertCount(25, $codes);
    }

    public function testNoMap()
    {
        $options = [
            'site.url' => 'http://homestead.test/',
            'request.uri' => '/projects/ahmic',
            'map' => null
        ];
        $redirects = new Redirects($options);
        $check = $redirects->checkForRedirect($redirects->option());
        $this->assertNull($check);
    }

    public function testAppendRemove()
    {
        $redirects = Redirects::singleton();
        $hash = md5((string) time());
        $redirects->append(
            ['fromuri'=>'/old1-'.$hash, 'touri'=>'/new1', 'code'=>302]
        );
        $success = $redirects->append([
            ['fromuri'=>'/old2-'.$hash, 'touri'=>'/new2', 'code'=>302],
            ['fromuri'=>'/old3-'.$hash, 'touri'=>'/new3']
        ]);
        $this->assertTrue($success);
        $this->assertStringContainsString($hash, file_get_contents(
            __DIR__ . '/content/site.txt'
        ));

        $redirects->remove(
            ['fromuri'=>'/old1-'.$hash, 'touri'=>'/new1']
        );
        $success = $redirects->remove([
            ['fromuri'=>'/old2-'.$hash, 'touri'=>'/new2'],
            ['fromuri'=>'/old3-'.$hash, 'touri'=>'/new3'],
        ]);
        $this->assertTrue($success);
        $this->assertStringNotContainsString($hash, file_get_contents(
            __DIR__ . '/content/site.txt'
        ));

        // can not update if is not a site/page
        $redirects = new Redirects([
            'map' => []
        ]);
        $success = $redirects->append([['fromuri'=>'/old-'.$hash, 'touri'=>'/new']]);
        $this->assertFalse($success);
    }
}
