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
// + Slim/Middleware/methodOverrideiddleware

// localhost:8080
// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad(); // теперь можно обратиться к добавленной через файл .env переменной, напр. $_ENV['DATABASE_URL']

$container = new Container();
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

// Включаем поддержку переопределения метода в Slim, чтобы, например, в html можно было исп-ть pаtch (а не только get и post)
// $app->add(MethodOverrideMiddleware::class);

// Старт PHP сессии для пакета slim/flash
session_start();

function getNormalisedUrl(string $url): string
{
    $databaseUrl = parse_url($url);
    return $databaseUrl['scheme'] . '://' . $databaseUrl['host'];
}

$app->get('/', function ($request, $response) {
    $viewData = [
        'url' => ['name' => ''],
        'errors' => [],
    ];
    return $this->get('renderer')->render($response, 'index.phtml', $viewData);
})->setName('index');

$app->post('/urls', function ($request, $response) use ($router) {
    
    $urlRepository = $this->get(UrlRepository::class); // это объект

    $urlData = $request->getParsedBodyParam('url'); // это просто асс массив, типа ["name" => "https://mail.ru/"]

    $urlName = $urlData['name'];
    //dump($urlName);
    
    $validator = new Validator(['currentUrl' => $urlName]);
    $rules = ['required','url', ['lengthMax', 255]];
    
    $validator->mapFieldRules('currentUrl', $rules);

    if ($validator->validate()) {
        $normalisedName = getNormalisedUrl($urlName);
        $url = $urlRepository->findByName($normalisedName);
        if ($url) { // 'Страница уже существует'
            $this->get('flash')->addMessage('success', 'Страница уже существует');
            $id = $url->getId();
            return $response->withRedirect($router->urlFor('urls.show', ['id' => $id]));
        }

        $created_at = Carbon::now();
        $newUrl = Url::fromArray([$normalisedName, $created_at]); // объект Url
        $urlRepository->save($newUrl);

        $this->get('flash')->addMessage('success', "Страница успешно добавлена");
        $id = $newUrl->getId();

        return $response->withRedirect($router->urlFor('urls.show', ['id' => $id]));
    }

    $errors = $validator->errors(); // -> ["currentUrl" => [0 => "CurrentUrl is not a valid URL"]]
    $errorMessages = $errors['currentUrl']; // ["CurrentUrl is required", "CurrentUrl is not a valid URL"]

    $errorMessagesRu[0] = $errorMessages[0] === 'CurrentUrl is required'
        ? 'URL не должен быть пустым' : 'Некорректный URL';

    $viewData = [
        'url' => ['name' => $urlName],
        'errors' => $errorMessagesRu,
    ];
    return $this->get('renderer')->render($response, "index.phtml", $viewData);
});


$app->get('/urls/{id}', function ($request, $response, $args) {

    $id = $args['id'];
    $urlRepository = $this->get(UrlRepository::class);
    $url = $urlRepository->findById($id);

    if (is_null($url)) {
        return $response->write('Page not found')->withStatus(404);
    }

    $messages = $this->get('flash')->getMessages();

    $checksRepository = $this->get(UrlChecksRepository::class);

    //dump($checksRepository->getEntities());

    // $checks это либо [], либо типа [['id' => $id1, 'created_at' => $created_at1, 'status_code' => status_code1], ['id' => $id2, ...]]
    $checks = $checksRepository->findChecksByUrlId($id);
    //dump($checks);

    $params = [
        'url' => $url,
        'checks' => $checks,
        'flash' => $messages
    ];

    return $this->get('renderer')->render($response, 'url.phtml', $params);
})->setName('urls.show');


$app->post('/urls/{url_id}/checks', function ($request, $response, $args) use ($router) {
    
    $url_id = $args['url_id'];
    $created_at = Carbon::now();

    $urlRepository = $this->get(UrlRepository::class);
    $url = $urlRepository->findById($url_id);
    $name = $url['name'];

    $client = new Client();

    try {
        $res = $client->request('GET', $name); // 'https://example.com/api/resource'
        $status_code = $res->getStatusCode();
    } catch (ConnectException $e) {
        echo "Connect error: " . $e->getMessage();
    }

    $checksRepository = $this->get(UrlChecksRepository::class);

    $newCheck = Check::fromArray([$url_id, $created_at, $status_code]); // получаем объект Check при каждом нажатии "Запустить проверку"

    $checksRepository->save($newCheck);

    $this->get('flash')->addMessage('success', 'Страница успешно проверена');

    //$url = $router->urlFor('urls.show', ['id' => $url_id]);
    return $response->withRedirect($router->urlFor('urls.show', ['id' => $url_id]));
});


$app->get('/urls', function ($request, $response) {

    $urlRepository = $this->get(UrlRepository::class);
    $urls = $urlRepository->getEntities(); // асс массив всех url

    $checksRepository = $this->get(UrlChecksRepository::class);
    //$checks = $checksRepository->getEntities(); // асс массив всех checks

    $updatedUrls = array_map(
        function ($url) use ($checksRepository) {
            $urlId = $url['id'];
            $url_check_info = $checksRepository->getLatestCheckInfo($urlId); // ["url_check_date" => "2025-01-21 19:17:27", "url_check_status_code" => "200"]
            $url_check_info_upd = $url_check_info === [] 
                ? ["url_check_date" => "", "url_check_status_code" => ""] 
                : $url_check_info;

            return [...$url, ...$url_check_info_upd];
        }, $urls
    );

    $viewData = [
        'urls' => $updatedUrls,
    ];

    return $this->get('renderer')->render($response, 'urls.phtml', $viewData);
})->setName('urls.index');


$app->run();
