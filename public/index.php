<?php

declare(strict_types=1);

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

// Chargement des variables d'environnement
if (file_exists(dirname(__DIR__).'/.env.local.php')) {
    include_once dirname(__DIR__).'/.env.local.php';
} elseif (!class_exists(Dotenv::class)) {
    throw new LogicException('Please run "composer require symfony/dotenv" to load the ".env" files configuring the application.');
} else {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

// Configuration de l'environnement de développement
if ($_SERVER['APP_DEBUG'] ?? false) {
    umask(0000);
    Debug::enable();
}

// Vérification et chargement de l'autoloader runtime
$autoloadRuntime = dirname(__DIR__) . '/vendor/autoload_runtime.php';

if (is_file($autoloadRuntime)) {
    require_once $autoloadRuntime;
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
    }
    
    $exception = new MissingAutoloadRuntimeException();
    
    // Log de l'erreur
    error_log('Critical Error: ' . $exception->getMessage());
    error_log('Recommended Action: ' . $exception->getRecommendedAction());
    
    throw $exception;
}

// Configuration du timezone par défaut
if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

// Fonction de factory pour créer le kernel
return function (array $context): Kernel {
    // Validation du contexte
    $requiredKeys = ['APP_ENV', 'APP_DEBUG'];
    foreach ($requiredKeys as $key) {
        if (!array_key_exists($key, $context)) {
            throw new InvalidArgumentException(sprintf('Missing required context key: %s', $key));
        }
    }
    
    // Création et configuration du kernel
    $kernel = new Kernel(
        $context['APP_ENV'] ?? 'prod',
        (bool) ($context['APP_DEBUG'] ?? false)
    );
    
    // Configuration additionnelle pour l'environnement de développement
    if ($kernel->isDebug()) {
        // Activation des outils de debug
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
        error_reporting(E_ALL);
    }
    
    return $kernel;
};
