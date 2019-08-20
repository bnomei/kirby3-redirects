<?php

use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    protected function setUp(): void
    {
        $this->setOutputCallback(function () {
        });
    }

    public function testRedirectCodesRoute()
    {
        $response = kirby()->render('/plugin-redirects/codes');
        $this->assertTrue($response->code() === 200);
        $this->assertStringStartsWith('application/json', $response->type());
    }

    public function testFindsHomePage()
    {
        $response = kirby()->render('/');
        $this->assertTrue($response->code() === 200);
        $this->assertStringContainsString('Home', $response->body());
    }

    public function testFindsTestPage()
    {
        $response = kirby()->render('/projects/ahmic');
        $this->assertTrue($response->code() === 200);
        $this->assertStringContainsString('Ahmic', $response->body());
    }
}
