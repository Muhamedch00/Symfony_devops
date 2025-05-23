<?php

declare(strict_types=1);

// CORRIGÉ: Utilisation du système d'import avec "use" au lieu de require_once
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\DependencyInjection\ContainerBuilder;

// Configuration de l'autoloader
if (file_exists($autoloadFile = dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once $autoloadFile;
}

// Gestion du fichier de preload avec namespaces
$preloadFile = dirname(__DIR__) . '/var/cache/prod/App_KernelProdContainer.preload.php';

if (is_file($preloadFile)) {
    // Optimisation opcache si disponible
    if (function_exists('opcache_compile_file')) {
        opcache_compile_file($preloadFile);
    }
    
    // Inclusion sécurisée du fichier de preload
    include_once $preloadFile;
}

// Configuration opcache pour la production
if (extension_loaded('opcache') && function_exists('opcache_get_status')) {
    $opcacheStatus = opcache_get_status();
    if ($opcacheStatus && $opcacheStatus['opcache_enabled']) {
        // Classes critiques à preloader
        $criticalClasses = [
            'App\\Kernel',
            'App\\Entity\\User',
            'App\\Entity\\Client',
            'App\\Repository\\UserRepository',
            'App\\Repository\\ClientRepository',
        ];
        
        foreach ($criticalClasses as $className) {
            if (class_exists($className)) {
                // La classe est déjà chargée par l'autoloader
                continue;
            }
        }
    }
}

// Initialisation des services critiques
try {
    $container = new ContainerBuilder();
    $cacheAdapter = new PhpFilesAdapter('app.cache', 0, dirname(__DIR__) . '/var/cache');
} catch (Exception $e) {
    // Gestion silencieuse des erreurs de preload
    error_log('Preload initialization error: ' . $e->getMessage());
}
