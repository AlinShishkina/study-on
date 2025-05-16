<?php

declare(strict_types=1);

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\DataFixtures\CourseFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

/**
 * Абстрактный тестовый класс с базовой логикой для тестов
 */
abstract class AbstractTest extends WebTestCase
{
    protected static $client;

    /**
     * Создание клиента тестирования
     */
    protected static function createTestClient(array $options = [], array $server = [])
    {
        if (!static::$client) {
            static::$client = static::createClient($options, $server);
        }

        return static::$client;
    }

    /**
     * Настройка перед каждым тестом
     */
    protected function setUp(): void
    {
        parent::setUp();
        self::$client = static::createTestClient();
        $this->loadFixtures($this->getFixtures());
    }

    /**
     * Очистка после каждого теста
     */
    final protected function tearDown(): void
    {
        parent::tearDown();
        static::$client = null;
    }

    /**
     * Упрощённый доступ к EntityManager
     */
    protected static function getEntityManager()
    {
        return static::getContainer()->get('doctrine')->getManager();
    }

    /**
     * Список фикстур, которые будут загружены перед тестом
     */
    protected function getFixtures(): array
    {
        return [];
    }

    /**
     * Загрузка указанных фикстур в базу данных
     */
    protected function loadFixtures(array $fixtures = [])
    {
        $loader = new Loader();

        foreach ($fixtures as $fixture) {
            if (!\is_object($fixture)) {
                $fixture = new $fixture();
            }

            if ($fixture instanceof ContainerAwareInterface) {
                $fixture->setContainer(static::getContainer());
            }

            $loader->addFixture($fixture);
        }

        $em = static::getEntityManager();
        $purger = new ORMPurger($em);
        $executor = new ORMExecutor($em, $purger);
        $executor->execute($loader->getFixtures());
    }

    /**
     * Проверка: ответ успешен (HTTP 200)
     */
    public function assertResponseOk(
        ?Response $response = null,
        ?string $message = null,
        string $type = 'text/html'
    ) {
        $this->failOnResponseStatusCheck($response, 'isOk', $message, $type);
    }

    /**
     * Проверка: ответ - редирект (HTTP 3xx)
     */
    public function assertResponseRedirect(
        ?Response $response = null,
        ?string $message = null,
        string $type = 'text/html'
    ) {
        $this->failOnResponseStatusCheck($response, 'isRedirect', $message, $type);
    }

    /**
     * Проверка: ответ - не найден (HTTP 404)
     */
    public function assertResponseNotFound(
        ?Response $response = null,
        ?string $message = null,
        string $type = 'text/html'
    ) {
        $this->failOnResponseStatusCheck($response, 'isNotFound', $message, $type);
    }

    /**
     * Проверка: доступ запрещён (HTTP 403)
     */
    public function assertResponseForbidden(
        ?Response $response = null,
        ?string $message = null,
        string $type = 'text/html'
    ) {
        $this->failOnResponseStatusCheck($response, 'isForbidden', $message, $type);
    }

    /**
     * Проверка: ответ с указанным HTTP-кодом
     */
    public function assertResponseCode(
        int $expectedCode,
        ?Response $response = null,
        ?string $message = null,
        string $type = 'text/html'
    ) {
        $this->failOnResponseStatusCheck($response, $expectedCode, $message, $type);
    }

    /**
     * Предположение об ошибке из тела ответа
     */
    public function guessErrorMessageFromResponse(Response $response, string $type = 'text/html')
    {
        try {
            $crawler = new Crawler();
            $crawler->addContent($response->getContent(), $type);

            if (!\count($crawler->filter('title'))) {
                $add = '';
                $content = $response->getContent();

                if ('application/json' === $response->headers->get('Content-Type')) {
                    $data = json_decode($content);
                    if ($data) {
                        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        $add = ' ОТРЕФОРМАТИРОВАНО';
                    }
                }
                $title = '[' . $response->getStatusCode() . ']' . $add . ' - ' . $content;
            } else {
                $title = $crawler->filter('title')->text();
            }
        } catch (\Exception $e) {
            $title = $e->getMessage();
        }

        return trim($title);
    }

    /**
     * Общая логика проверки статуса ответа и вывод сообщения при ошибке
     */
    private function failOnResponseStatusCheck(
        ?Response $response = null,
        int|string|null $func = null,
        ?string $message = null,
        string $type = 'text/html'
    ): void {
        if (null === $func) {
            $func = 'isOk';
        }

        if (null === $response && self::$client) {
            $response = self::$client->getResponse();
        }

        try {
            if (is_int($func)) {
                $this->assertEquals($func, $response->getStatusCode());
            } else {
                $this->assertTrue($response->{$func}());
            }

            return;
        } catch (\Exception $e) {
            // Перехват и продолжение для получения ошибки ниже
        }

        $err = $this->guessErrorMessageFromResponse($response, $type);
        if ($message) {
            $message = rtrim($message, '.') . ". ";
        } else {
            $message = '';
        }

        if (is_int($func)) {
            $template = "Ожидался статус ответа %s, получен %s.";
            $message .= sprintf($template, $func, $response->getStatusCode());
        } else {
            $template = "Ожидалось, что ответ [%s] удовлетворяет условию: %s.";
            $funcFormatted = preg_replace('#([a-z])([A-Z])#', '$1 $2', $func);
            $message .= sprintf($template, $response->getStatusCode(), $funcFormatted);
        }

        $max_length = 100;
        if (mb_strlen($err, 'utf-8') < $max_length) {
            $message .= " " . $this->makeErrorOneLine($err);
        } else {
            $message .= " " . $this->makeErrorOneLine(mb_substr($err, 0, $max_length, 'utf-8') . '...');
            $message .= "\n\n" . $err;
        }

        $this->fail($message);
    }

    /**
     * Преобразует текст ошибки в одну строку
     */
    private function makeErrorOneLine($text)
    {
        return preg_replace('#[\n\r]+#', ' ', $text);
    }
}
