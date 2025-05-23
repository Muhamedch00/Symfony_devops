<?php

$preloadFile = dirname(__DIR__) . '/var/cache/prod/App_KernelProdContainer.preload.php';

if (is_file($preloadFile)) {
    include_once $preloadFile;
}
