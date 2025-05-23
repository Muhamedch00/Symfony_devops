<?php

use App\Kernel;

$autoloadRuntime = dirname(_DIR_) . '/vendor/autoload_runtime.php';

if (is_file($autoloadRuntime)) {
    require_once $autoloadRuntime;
} else {
    class MissingAutoloadRuntimeException extends \RuntimeException {}
    throw new MissingAutoloadRuntimeException('autoload_runtime.php is missing. Run composer install.');
}

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
