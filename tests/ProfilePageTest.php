<?php

namespace App\Tests;

class ProfilePageTest extends AbstractTest
{
    public function urlProviderRedirectToLogin(): \Generator
    {
        yield ['/profile/transactions'];
        yield ['/profile/'];
    }
    /**
     * Тест на переход к авторизации
     * @dataProvider urlProviderRedirectToLogin
     */
    public function testPageRedirectToLogin($url): void
    {
        $client = static::createTestClient();
        $client->request('GET', $url);
        // dd($client);
        $this->assertTrue($client->getResponse()->isRedirect('/login'));
    }
}