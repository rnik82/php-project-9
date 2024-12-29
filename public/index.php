<?php

use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;

// localhost:8080
// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $renderer = new PhpRenderer(__DIR__ . '/../templates');

    $viewData = [
        //'name' => 'John',
    ];
    return $renderer->render($response, 'index.phtml', $viewData);
});

$app->run();