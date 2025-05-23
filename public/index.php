<?php

declare(strict_types=1);

// CORRIGÉ: Utilisation du système d'import avec "use" au lieu de require/include_once
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Runtime\SymfonyRuntime;

// Chargement des variables d'environnement avec gestion d'erreur
if (file_exists(dirname(__DIR__).'/.env.local.php')) {
    $envVars = include dirname(__DIR__).'/.env.local.php';
} elseif (!class_exists(Dotenv::class)) {
    throw new LogicException('Please run "composer require symfony/dotenv" to load the ".env" files configuring the application.');
} else {
    $dotenv = new Dotenv();
    $dotenv->bootEnv(dirname(__DIR__).'/.env');
}

// Configuration de l'environnement de développement
if ($_SERVER['APP_DEBUG'] ?? false) {
    umask(0000);
    Debug::enable();
}

// CORRIGÉ: Utilisation de require_once au lieu de require pour éviter les inclusions multiples
$autoloadRuntime = dirname(__DIR__) . '/vendor/autoload_runtime.php';

if (is_file($autoloadRuntime)) {
    $runtime = require_once $autoloadRuntime;
} else {
    /**
     * Exception personnalisée pour l'autoloader manquant
     */
    class MissingAutoloadRuntimeException extends \RuntimeException
    {
        public function __construct(
            string $message = 'autoload_runtime.php is missing. Run `composer install` to install dependencies.',
            int $code = 500,
            ?\Throwable $previous = null
        ) {
            parent::__construct($message, $code, $previous);
        }
        
        public function getRecommendedAction(): string
        {
            return 'Run the following command: composer install --no-dev --optimize-autoloader';
        }
        
        public function getDebugInfo(): array
        {
            return [
                'expected_file' => dirname(__DIR__) . '/vendor/autoload_runtime.php',
                'current_directory' => getcwd(),
                'php_version' => PHP_VERSION,
                'composer_installed' => file_exists(dirname(__DIR__) . '/vendor/composer/installed.json'),
            ];
        }
    }
    
    $exception = new MissingAutoloadRuntimeException();
    
    // Log détaillé de l'erreur
    error_log('Critical Error: ' . $exception->getMessage());
    error_log('Debug Info: ' . json_encode($exception->getDebugInfo()));
    error_log('Recommended Action: ' . $exception->getRecommendedAction());
    
    throw $exception;
}

// Configuration du timezone par défaut
if (!ini_get('date.timezone')) {
    date_default_timezone_set($_ENV['DEFAULT_TIMEZONE'] ?? 'UTC');
}

// Gestion des headers de sécurité
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// Fonction de factory pour créer le kernel avec validation complète
return function (array $context): Kernel {
    // Validation stricte du contexte
    $requiredKeys = ['APP_ENV', 'APP_DEBUG'];
    $missingKeys = [];
    
    foreach ($requiredKeys as $key) {
        if (!array_key_exists($key, $context)) {
            $missingKeys[] = $key;
        }
    }
    
    if (!empty($missingKeys)) {
        throw new InvalidArgumentException(
            sprintf('Missing required context keys: %s', implode(', ', $missingKeys))
        );
    }
    
    // Validation des valeurs du contexte
    $allowedEnvironments = ['dev', 'test', 'prod'];
    $environment = $context['APP_ENV'] ?? 'prod';
    
    if (!in_array($environment, $allowedEnvironments, true)) {
        throw new InvalidArgumentException(
            sprintf('Invalid environment "%s". Allowed values: %s', $environment, implode(', ', $allowedEnvironments))
        );
    }
    
    // Création et configuration du kernel
    $kernel = new Kernel(
        $environment,
        (bool) ($context['APP_DEBUG'] ?? false)
    );
    
    // Configuration spécifique par environnement
    switch ($environment) {
        case 'dev':
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
            break;
            
        case 'test':
            ini_set('memory_limit', '512M');
            break;
            
        case 'prod':
            ini_set('display_errors', '0');
            ini_set('log_errors', '1');
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
            break;
    }
    
    return $kernel;
};
