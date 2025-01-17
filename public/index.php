<?php

use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;
use Valitron\Validator;
use DI\Container;
use Hexlet\Code\Connection;
use Hexlet\Code\UrlRepository;
use Hexlet\Code\Url;
use Carbon\Carbon; // -
use Dotenv\Dotenv;
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

// Включаем поддержку переопределения метода в Slim
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
        'url' => [],
        'errors' => [],
    ];
    return $this->get('renderer')->render($response, 'index.phtml', $viewData);
})->setName('index');

$app->post('/urls', function ($request, $response) use ($router) {
    
    $urlRepository = $this->get(UrlRepository::class); // это объект

    $urlData = $request->getParsedBodyParam('url'); // это просто асс массив, типа ["name" => "https://mail.ru/"]

    $urlName = getNormalisedUrl($urlData['name']);
    //dump($urlName);
    
    $validator = new Validator(['currentUrl' => $urlName]);
    $rules = ['required','url', ['lengthMax', 255]];
    
    $validator->mapFieldRules('currentUrl', $rules);

    if ($validator->validate()) {
        $url = $urlRepository->findByName($urlName);
        if ($url) { // 'Страница уже существует'
            $this->get('flash')->addMessage('success', 'Страница уже существует');
            $id = $url->getId();
            return $response->withRedirect($router->urlFor('urls.show', ['id' => $id]));
        }

        $created_at = Carbon::now();
        $newUrl = Url::fromArray([$urlName, $created_at]); // объект Url
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

    $params = [
        'url' => ['id' => $url->getId(),'name' => $url->getName(), 'created_at' => $url->getCreatedAt()],
        'flash' => $messages
    ];

    return $this->get('renderer')->render($response, 'url.phtml', $params);
})->setName('urls.show');


$app->get('/urls', function ($request, $response) {

    $urlRepository = $this->get(UrlRepository::class);
    $urls = $urlRepository->getEntities(); // асс массив всех url

    $viewData = [
        'urls' => $urls,
    ];

    return $this->get('renderer')->render($response, 'urls.phtml', $viewData);
})->setName('urls.index');

$app->run();
