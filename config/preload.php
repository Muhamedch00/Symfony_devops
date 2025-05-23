<?php
$preloadFile = dirname(DIR) . '/var/cache/prod/App_KernelProdContainer.preload.php';
if (is_file($preloadFile)) {
    require_once $preloadFile;
}     
---public/index
<?php
use App\Kernel;
$autoloadRuntime = dirname(DIR) . '/vendor/autoload_runtime.php';
if (is_file($autoloadRuntime)) {
    require $autoloadRuntime;
} else {
    class MissingAutoloadRuntimeException extends \RuntimeException {}
    throw new MissingAutoloadRuntimeException('autoload_runtime.php is missing. Run composer install.');
}
return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
