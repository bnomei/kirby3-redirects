<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Bnomei\Redirects;
use PHPUnit\Framework\TestCase;

class RedirectsTest extends TestCase
{
    public function testConstruct()
    {
        $redirects = new Bnomei\Redirects();
        $this->assertInstanceOf(Bnomei\Redirects::class, $redirects);
    }

    public function testDoesNotRedirectOtherPage()
    {
        $options = [
            'site.url' => 'http://homestead.test/',
            'request.uri' => '/projects/ahmic',
        ];
        $redirects = new Bnomei\Redirects($options);
        $check = $redirects->checkForRedirect($redirects->option());
        $this->assertNull($check);
    }

    public function testRedirectPage()
    {
        $options = [
            'site.url' => 'http://homestead.test/',
            'request.uri' => '/building/ahmic',
        ];
        $redirects = new Bnomei\Redirects($options);
        $check = $redirects->checkForRedirect($redirects->option());
        $this->assertTrue($check['code'] === 301);
    }

    public function testRedirectExtension()
    {
        $options = [
            'site.url' => 'http://homestead.test/',
            'request.uri' => '/building/ahmic.html',
        ];
        $redirects = new Bnomei\Redirects($options);
        $check = $redirects->checkForRedirect($redirects->option());
        $this->assertTrue($check['code'] === 302);
    }

    public function testRedirectQuery()
    {
        $options = [
            'site.url' => 'http://homestead.test/',
            'request.uri' => '/projects?id=1',
        ];
        $redirects = new Bnomei\Redirects($options);
        $check = $redirects->checkForRedirect($redirects->option());
        $this->assertTrue($check['code'] === 303);
    }

    public function testRedirectExternal()
    {
        $options = [
            'site.url' => 'http://homestead.test/',
            'request.uri' => '/projects/external',
        ];
        $redirects = new Bnomei\Redirects($options);
        $check = $redirects->checkForRedirect($redirects->option());
        $this->assertTrue($check['code'] === 301);
    }

    public function testStaticCodes()
    {
        $codes = Bnomei\Redirects::codes();
        $this->assertIsArray($codes);
        $this->assertCount(25, $codes);
    }

    public function testStaticCodesForced()
    {
        $codes = Bnomei\Redirects::codes(true);
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
        $redirects = new Bnomei\Redirects($options);
        $check = $redirects->checkForRedirect($redirects->option());
        $this->assertNull($check);
    }
}
