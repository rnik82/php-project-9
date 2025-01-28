PORT ?= 8000
start: # Cтарт проекта. Команда запускает веб сервер по адресу http://0.0.0.0:8000 если в переменных окружения не указан порт (он нужен для деплоя приложения)
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

install: # команда полезна при первом клонировании репозитория (или после удаления зависимостей)
	composer install

update: # обновить зависимости
	composer update
	
validate: # проверяет файл composer.json на ошибки
	composer validate

lint: # запуск линтера phpstan
	composer exec --verbose phpcs -- --standard=PSR12 src public

lint-fix: # исправить ошибки линтера
	composer exec --verbose phpcbf -- --standard=PSR12 src public

