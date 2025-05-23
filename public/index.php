<?php

use App\Kernel;

// Needed for Composer autoload – cannot be replaced by `use`.
// SonarQube false positive: require_once is necessary here.
require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
