<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;

// Chargement de l'autoloader si disponible
$autoloadFile = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($autoloadFile)) {
    /** @var ClassLoader $loader */
    $loader = require_once $autoloadFile;
}

// Gestion du fichier de preload avec optimisation opcache
$preloadFile = dirname(__DIR__) . '/var/cache/prod/App_KernelProdContainer.preload.php';

if (is_file($preloadFile)) {
    // Optimisation opcache si disponible
    if (function_exists('opcache_compile_file')) {
        opcache_compile_file($preloadFile);
    }
    
    // Inclusion du fichier de preload
    include_once $preloadFile;
}

// Configuration additionnelle de preload pour les classes critiques
if (function_exists('opcache_is_script_cached')) {
    $criticalClasses = [
        dirname(__DIR__) . '/src/Kernel.php',
        dirname(__DIR__) . '/src/Entity/User.php',
        dirname(__DIR__) . '/src/Entity/Client.php',
    ];
    
    foreach ($criticalClasses as $classFile) {
        if (is_file($classFile) && !opcache_is_script_cached($classFile)) {
            opcache_compile_file($classFile);
        }
    }
}

// Preload des extensions communes
if (extension_loaded('opcache')) {
    // Configuration opcache pour production
    ini_set('opcache.preload_user', 'www-data');
    ini_set('opcache.memory_consumption', '256');
    ini_set('opcache.max_accelerated_files', '20000');
    ini_set('opcache.validate_timestamps', '0');
}

// Log de debug pour le preloading (uniquement en développement)
if (defined('APP_ENV') && APP_ENV === 'dev') {
    error_log('Preload completed for: ' . $preloadFile);
}
