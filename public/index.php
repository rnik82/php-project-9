<?php

use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;
use Valitron\Validator;
use DI\Container;
use Hexlet\Code\Connection;
use Hexlet\Code\UrlRepository;
use Hexlet\Code\Url;
use Hexlet\Code\UrlChecksRepository;
use Hexlet\Code\Check;
use Carbon\Carbon;
use Dotenv\Dotenv;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\RequestException;
use DiDom\Document;

//use Illuminate\Support;
//use Slim/Middleware/methodOverrideiddleware

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();
// теперь можно обратиться к добавленной через файл .env переменной, напр. $_ENV['DATABASE_URL']

// Создаем контейнер
$container = new Container();

// Те объекты, кот. мы кладем в контейнер на этом этапе (renderer, flash и  т.д.) мы затем
// можем использовать в коде таким обр. - $this->get('renderer')->...
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new PhpRenderer(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    return new Messages();
});

$container->set(\PDO::class, function () {
    $conn = Connection::get()->create($_ENV['DATABASE_URL']);
    $conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    return $conn;
});

$initFilePath = implode('/', [dirname(__DIR__), 'database.sql']);
$initSql = file_get_contents($initFilePath);
$container->get(\PDO::class)->exec($initSql);

$app = AppFactory::createFromContainer($container);

// роутер — объект, отвечающий за хранение и обработку маршрутов
$router = $app->getRouteCollector()->getRouteParser();

//Устанавливаем middleware для обработки ошибок
$app->addErrorMiddleware(true, true, true);

// Включаем поддержку переопределения метода в Slim,
// чтобы, например, в html можно было исп-ть pаtch (а не только get и post)
// $app->add(MethodOverrideMiddleware::class);

// Старт PHP сессии для пакета slim/flash
session_start();

$app->get('/', function ($request, $response) {
    $viewData = [
        'url' => ['name' => ''],
        'errors' => [],
    ];
    return $this->get('renderer')->render($response, 'index.phtml', $viewData);
})->setName('index');

$app->post('/urls', function ($request, $response) use ($router) {

    $urlRepository = $this->get(UrlRepository::class);

    // $urlData - это просто асс массив, типа ["name" => "https://mail.ru/"]
    $urlData = $request->getParsedBodyParam('url');

    $urlName = $urlData['name'];

    $validator = new Validator(['currentUrl' => $urlName]);
    $rules = ['required','url', ['lengthMax', 255]];

    $validator->mapFieldRules('currentUrl', $rules);

    if ($validator->validate()) {
        $databaseUrl = parse_url($urlName);
        $normalisedName = $databaseUrl['scheme'] . '://' . $databaseUrl['host'];

        $url = $urlRepository->findByName($normalisedName);

        if ($url) { // Eсли страница уже есть в репозитории то редирект
            $this->get('flash')->addMessage('success', 'Страница уже существует');
            $id = $url->getId();
            return $response->withRedirect(
                $router->urlFor('urls.show', ['id' => $id])
            );
        }

        // Если страницы нет, то сохраняем ее в Url репозитории и затем редирект
        $created_at = Carbon::now();
        $newUrl = Url::fromArray([$normalisedName, $created_at]);
        $urlRepository->save($newUrl);

        $this->get('flash')->addMessage('success', "Страница успешно добавлена");
        $id = (string)$newUrl->getId();

        return $response->withRedirect($router->urlFor('urls.show', ['id' => $id]));
    }

    // $errors - массив типа ["currentUrl" => [0 => "CurrentUrl is not a valid URL"]]
    $errors = $validator->errors();

    // $errorMessages - либо массив типа ["CurrentUrl is not a valid URL"], либо []
    $errorMessages = $errors['currentUrl'] ?? ["currentUrl" => []];

    $errorMessagesRu[0] = $errorMessages[0] === 'CurrentUrl is required'
        ? 'URL не должен быть пустым' : 'Некорректный URL';

    $viewData = [
        'url' => ['name' => $urlName],
        'errors' => $errorMessagesRu,
    ];
    return $this->get('renderer')
        ->render($response->withStatus(422), "index.phtml", $viewData);
});


$app->get('/urls/{id:[0-9]+}', function ($request, $response, $args) {

    $id = $args['id'];
    $urlRepository = $this->get(UrlRepository::class);
    $url = $urlRepository->findById($id);

    if (is_null($url)) {
        return $response->write('Page not found')->withStatus(404);
    }

    $messages = $this->get('flash')->getMessages();

    $checksRepository = $this->get(UrlChecksRepository::class);

    // $checks это либо [], либо типа [['id' => $id1, 'created_at' => $created_at1,
    // 'status_code' => status_code1, ...], ...]
    $checks = $checksRepository->findChecksByUrlId($id);

    $params = [
        'url' => $url,
        'checks' => $checks,
        'flash' => $messages
    ];

    return $this->get('renderer')->render($response, 'url.phtml', $params);
})->setName('urls.show');


$app->post(
    '/urls/{url_id:[0-9]+}/checks',
    function ($request, $response, $args) use ($router) {

        $url_id = $args['url_id'];
        $created_at = Carbon::now();

        $urlRepository = $this->get(UrlRepository::class);
        $url = $urlRepository->findById($url_id);
        $urlName = $url['name'];

        $client = new Client();

        $checksRepository = $this->get(UrlChecksRepository::class);

        try {
            $res = $client->request('GET', $urlName, ['http_errors' => false]);

            $status_code = $res->getStatusCode();
            $html = (string) $res->getBody();
            $document = new Document($html);

            // Получаем содержимое h1, title, content (description)
            $h1 = optional($document->find('h1::text'))[0];
            $title = optional($document->find('title::text'))[0];
            $description = optional(
                $document->find('meta[name=description][content]::attr(content)')
            )[0];

            $this->get('flash')->addMessage('success', 'Страница успешно проверена');
        } catch (ServerException $e) {
            // https://mock.httpstatus.io/500, ошибку 500 отдал наш сервер,
            // если например в базу записать не смогли или еще что нибудь - обрабатываем

            return $this->get('renderer')->render($response->withStatus(500), '500.phtml');
        } catch (RequestException $e) {
            // https://avito.com, ошибку 500 отдал сервер который мы опрашиваем - записываес код в БД
            $status_code = '500';
            $h1 = $title = $description = null;

            $this->get('flash')
                ->addMessage('warning', 'Проверка была выполнена успешно, но сервер ответил c ошибкой');
        } catch (ConnectException $e) {
            // https://wqdqwdqwdqwdwdq.wdq, http://localhost
            // предупредить пользователя и записать в БД статус (ошибки 4xx)

            $status_code = '404';
            $h1 = $title = $description = null;
            $this->get('flash')
                ->addMessage('warning', 'Произошла ошибка при проверке, не удалось подключиться');
        }
        $newCheck = Check::fromArray(
            [$url_id, $created_at, $status_code, $h1, $title, $description]
        );
        // записываем в url_checks
        $checksRepository->save($newCheck);
        return $response->withRedirect($router->urlFor('urls.show', ['id' => $url_id]));
    }
);


$app->get('/urls', function ($request, $response) {

    $urlRepository = $this->get(UrlRepository::class);
    // получаем асс массив всех url
    $urls = $urlRepository->getEntities();

    $checksRepository = $this->get(UrlChecksRepository::class);

    $updatedUrls = array_map(
        function ($url) use ($checksRepository) {
            $urlId = $url['id'];
            // ["url_check_date" => "2025-01...", "url_check_status_code" => "200"]
            $url_check_info = $checksRepository->getLatestCheckInfo($urlId);
            $url_check_info_upd = $url_check_info === []
                ? ["url_check_date" => "", "url_check_status_code" => ""]
                : $url_check_info;

            return [...$url, ...$url_check_info_upd];
        },
        $urls
    );

    $viewData = [
        'urls' => $updatedUrls,
    ];

    return $this->get('renderer')->render($response, 'urls.phtml', $viewData);
})->setName('urls.index');


$app->run();
