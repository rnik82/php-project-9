PORT ?= 8000
start: # Cтарт проекта. Команда запускает веб сервер по адресу http://0.0.0.0:8000 если в переменных окружения не указан порт (он нужен для деплоя приложения)
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

check: # для проверки локално на localhost:8080
	php -S localhost:8080 -t public public/index.php

install: # команда полезна при первом клонировании репозитория (или после удаления зависимостей)
	composer install

update: # обновить зависимости
	composer update
	
 validate: # проверяет файл composer.json на ошибки
	composer validate

lint:
	composer exec --verbose phpcs -- --standard=PSR12 src tests
	composer exec --verbose phpstan

lint-fix:
	composer exec --verbose phpcbf -- --standard=PSR12 src tests

test:
	composer exec --verbose phpunit tests

test-coverage:
	XDEBUG_MODE=coverage composer exec --verbose phpunit tests -- --coverage-clover build/logs/clover.xml

test-coverage-text:
	XDEBUG_MODE=coverage composer exec --verbose phpunit tests -- --coverage-text

#.PHONY: tests
