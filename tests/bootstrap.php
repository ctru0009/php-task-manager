<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/Support/TestDatabase.php';
require __DIR__ . '/Support/TestServer.php';
require __DIR__ . '/Support/HttpResponse.php';
require __DIR__ . '/Support/HttpClient.php';
require __DIR__ . '/Support/DatabaseTestCase.php';
require __DIR__ . '/Support/HttpTestCase.php';

// The application has no autoloader. Tests load its classes directly so the
// directory structure and require_once style stay exactly as they are.
require_once __DIR__ . '/../config/database.php';

foreach (glob(__DIR__ . '/../models/*.php') as $model) {
    require_once $model;
}

foreach (glob(__DIR__ . '/../includes/*.php') as $include) {
    require_once $include;
}

TestDatabase::createSchema(__DIR__ . '/../schema.sql');
TestServer::start();
