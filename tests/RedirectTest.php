<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Bnomei\Redirect;
use PHPUnit\Framework\TestCase;

class RedirectTest extends TestCase
{
    private $exampleOK;

    public function setUp(): void
    {
        $this->exampleOK = new Redirect('/old', '/', 302);
    }

    public function test__construct()
    {
        $this->assertInstanceOf(Redirect::class, $this->exampleOK);
    }

    public function testFrom()
    {
        $this->assertEquals('/old', $this->exampleOK->from());
    }

    public function testTo()
    {
        $this->assertEquals('/', $this->exampleOK->to());
    }

    public function testCode()
    {
        $this->assertEquals(302, $this->exampleOK->code());
    }

    public function testToArray()
    {
        $this->assertCount(3, $this->exampleOK->toArray());
    }

    public function testMatches()
    {
        $this->assertTrue($this->exampleOK->matches('/old'));

        $this->assertFalse($this->exampleOK->matches('old'));
        $this->assertFalse($this->exampleOK->matches('/other'));
    }

    public function testNormalizeCode()
    {
        $this->assertEquals(301, Redirect::normalizeCode(null));
        $this->assertEquals(301, Redirect::normalizeCode(0));
        $this->assertEquals(301, Redirect::normalizeCode('null'));
        $this->assertEquals(301, Redirect::normalizeCode('false'));
        $this->assertEquals(301, Redirect::normalizeCode(''));
        $this->assertEquals(302, Redirect::normalizeCode(302));
        $this->assertEquals(302, Redirect::normalizeCode('302'));
        $this->assertEquals(302, Redirect::normalizeCode('_302'));
    }

    public function testUrl()
    {
        // url
        $this->assertEquals('https://example.net', Redirect::url('https://example.net'));

        // path but not page
        $this->assertEquals('/relative', Redirect::url('relative'));

        // existing pages
        $this->assertEquals('/', Redirect::url('/'));
        $this->assertEquals('/', Redirect::url('home'));
        $this->assertEquals('/', Redirect::url('/home'));
        $this->assertEquals('/projects', Redirect::url('/projects'));
        $this->assertEquals('/projects', Redirect::url('projects'));
        $this->assertEquals('/projects/ahmic', Redirect::url('projects/ahmic'));
        $this->assertEquals('/projects/ahmic', Redirect::url('/projects/ahmic'));
    }

    public function testDebugInfo()
    {
        $this->assertIsArray($this->exampleOK->__debugInfo());
    }
}
