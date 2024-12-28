#!/usr/bin/env php
<?php

$app = AppFactory::create();

$app->get('/', function ($request, $response) {
    return $response->write('Main page (first handler)');
});