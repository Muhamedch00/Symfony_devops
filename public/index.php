<?php

use App\Kernel;

$autoloadRuntime = dirname(__DIR__) . '/vendor/autoload_runtime.php';

if (is_file($autoloadRuntime)) {
    include_once $autoloadRuntime;
} else {
    throw new RuntimeException('autoload_runtime.php is missing. Run `composer install`.');
}

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
