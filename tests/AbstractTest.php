<?php

namespace App\Tests;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractTest extends WebTestCase
{
    protected static ?KernelBrowser $client = null;
    protected static $container;

    /**
     * Совместимый с базовым метод getClient()
     *
     * @param AbstractBrowser|null $newClient
     * @return AbstractBrowser|null
     */
    public static function getClient(?AbstractBrowser $newClient = null): ?AbstractBrowser
    {
        if (null === static::$client || $newClient) {
            static::$client = static::createClient();
            static::$container = static::$client->getContainer();
        }

        static::$client->getKernel()->boot();

        return static::$client;
    }

    /**
     * Получить клиент с точной типизацией KernelBrowser
     */
    protected static function getKernelBrowser(): KernelBrowser
    {
        return static::$client ?? static::getClient();
    }

    protected function getClientInstance(): KernelBrowser
    {
        return static::$client ?? static::getClient();
    }

    /**
     * Получить EntityManager
     */
    protected static function getEntityManager()
    {
        return static::$container->get('doctrine')->getManager();
    }

    protected function setUp(): void
    {
        parent::setUp();
        static::getClient();
        $this->loadFixtures($this->getFixtures());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        static::$client = null;
    }

    /**
     * Массив фикстур для загрузки
     */
    protected function getFixtures(): array
    {
        return [];
    }

    /**
     * Загрузка фикстур
     */
    protected function loadFixtures(array $fixtures = []): void
    {
        $loader = new Loader();

        foreach ($fixtures as $fixture) {
            if (!\is_object($fixture)) {
                $fixture = new $fixture();
            }

            if ($fixture instanceof ContainerAwareInterface) {
                $fixture->setContainer(static::$container);
            }

            $loader->addFixture($fixture);
        }

        $em = static::getEntityManager();
        $purger = new ORMPurger($em);
        $executor = new ORMExecutor($em, $purger);
        $executor->execute($loader->getFixtures());
    }

    /**
     * Авторизация пользователя в тестах
     */
    protected function login(string $email = 'admin@mail.ru', string $password = 'password'): void
    {
        $client = $this->getClientInstance();
        $crawler = $client->request('GET', '/login');
        $this->assertResponseOk();

        $form = $crawler->selectButton('Войти')->form([
            'email' => $email,
            'password' => $password,
        ]);
        $client->submit($form);

        $this->assertResponseRedirect();
        $client->followRedirect();
        $this->assertResponseOk();
    }

    /**
     * Генерация CSRF токена для форм
     */
    protected function generateCsrfToken(string $id): string
    {
        return static::$container->get('security.csrf.token_manager')->getToken($id)->getValue();
    }

    public function assertResponseOk(?Response $response = null, ?string $message = null, string $type = 'text/html'): void
    {
        $this->failOnResponseStatusCheck($response, 'isOk', $message, $type);
    }

    public function assertResponseRedirect(?Response $response = null, ?string $message = null, string $type = 'text/html'): void
    {
        $this->failOnResponseStatusCheck($response, 'isRedirect', $message, $type);
    }

    public function assertResponseNotFound(?Response $response = null, ?string $message = null, string $type = 'text/html'): void
    {
        $this->failOnResponseStatusCheck($response, 'isNotFound', $message, $type);
    }

    public function assertResponseForbidden(?Response $response = null, ?string $message = null, string $type = 'text/html'): void
    {
        $this->failOnResponseStatusCheck($response, 'isForbidden', $message, $type);
    }

    public function assertResponseCode(int $expectedCode, ?Response $response = null, ?string $message = null, string $type = 'text/html'): void
    {
        $this->failOnResponseStatusCheck($response, $expectedCode, $message, $type);
    }

    private function failOnResponseStatusCheck(?Response $response = null, $func = null, ?string $message = null, string $type = 'text/html'): void
    {
        if (null === $func) {
            $func = 'isOk';
        }

        if (null === $response && static::$client) {
            $response = static::$client->getResponse();
        }

        try {
            if (is_int($func)) {
                $this->assertEquals($func, $response->getStatusCode());
            } else {
                $this->assertTrue($response->{$func}());
            }
            return;
        } catch (\Exception $e) {
            // ignore
        }

        $err = $this->guessErrorMessageFromResponse($response, $type);
        if ($message) {
            $message = rtrim($message, '.') . ". ";
        }

        if (is_int($func)) {
            $template = "Failed asserting Response status code %s equals %s.";
        } else {
            $template = "Failed asserting that Response[%s] %s.";
            $func = preg_replace('#([a-z])([A-Z])#', '$1 $2', $func);
        }

        $message .= sprintf($template, $response->getStatusCode(), $func, $err);

        $max_length = 100;
        if (mb_strlen($err, 'utf-8') < $max_length) {
            $message .= " " . $this->makeErrorOneLine($err);
        } else {
            $message .= " " . $this->makeErrorOneLine(mb_substr($err, 0, $max_length, 'utf-8') . '...');
            $message .= "\n\n" . $err;
        }

        $this->fail($message);
    }

    private function guessErrorMessageFromResponse(Response $response, string $type = 'text/html'): string
    {
        $content = $response->getContent();
        if ('text/html' === $type) {
            if (preg_match('/<title>(.*?)<\/title>/', $content, $matches)) {
                return $matches[1];
            }
        }
        return 'Unknown error';
    }

    private function makeErrorOneLine(string $text): string
    {
        return preg_replace('#[\n\r]+#', ' ', $text);
    }
}
