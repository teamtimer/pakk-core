<?php

namespace TeamTimer\Pakk;

use Symfony\Component\Console\Application;

class App
{
    /**
     * @var \Cycle\ORM\ORM
     */
    public static $orm = null;

    /**
     * @var \Cycle\Database\DatabaseManager
     */
    public static $dbal = null;

    /**
     * @var \Cycle\ORM\SchemaInterface
     */
    public static $schema = null;

    /**
     * @var \Cycle\Schema\Registry
     */
    public static $registry = null;

    /**
     * App constructor.
     */
    public function __construct()
    {
        // register autoloader
        spl_autoload_register([$this, 'autoload']);

        DEFINE('MVC_KERNEL_HOOKED', true);
    }

    public function autoload($class)
    {
        // everything under App is in the src folder
        $prefix = 'App\\';
        $base_dir = ABSPATH . "src/";

        $len = strlen($prefix);

        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relative_class = substr($class, $len);

        $file = $base_dir . '/' . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    }

    public function init()
    {
        // init the database
        $this->initDatabase();

        if (php_sapi_name() == 'cli') {
            // we are running from the command line
            $application = new \Symfony\Component\Console\Application();
            $this->initConsoleCommands($application);
            $application->run();
        } else {
            // we are running from the web

            // init admin menu
            $this->initAdminMenu();

            $requestURI = $_SERVER['REQUEST_URI'];

            //remove query string
            $requestURI = strtok($requestURI, '?');

            //remove trailing slash
            $requestURI = rtrim($requestURI, '/');

            // check if we need to handle the request
            if (array_key_exists($requestURI, $this->getUrls()['urls'])) {
                $config = $this->getUrls()['urls'][$requestURI];
                $this->handleRequest($config);
            }
        }


    }

    public function handleRequest($config)
    {
        $controller = $config['controller'];
        $action = $config['action'];

        // instantiate the controller
        $controller = new $controller();

        // call the action
        $controller->$action();

    }

    public function getUrls()
    {
        // get the urls from the config
        return require ABSPATH . 'src/Config/app.php';
    }

    public function initConsoleCommands(Application &$application)
    {
        // load all the commands
        $commands = glob(ABSPATH . 'src/Console/Commands/*.php');

        foreach ($commands as $commandName) {

            //remove the .php extension
            $commandName = str_replace('.php', '', $commandName);

            $commandName = str_replace(ABSPATH . 'src/Console/Commands/', '', $commandName);
            $command = 'App\\Console\\Commands\\' . $commandName;

            // we just need the constructor to run
            $application->add(new $command());
        }
    }

    public function initAdminMenu()
    {
        // load all the controllers
        $controllers = glob(ABSPATH . 'src/Controllers/*.php');

        foreach ($controllers as $controllerName) {
            $controllerName = str_replace('.php', '', $controllerName);
            $controllerName = str_replace(ABSPATH . 'src/Controllers/', '', $controllerName);
            $controller = 'App\\Controllers\\' . $controllerName;

            // we just need the constructor to run
            (new $controller());
        }
    }

    public function initDatabase(){
        //read config from WordPress
        $host = DB_HOST;
        $database = DB_NAME;
        $username = DB_USER;
        $password = DB_PASSWORD;

        $dbal = new \Cycle\Database\DatabaseManager(
            new \Cycle\Database\Config\DatabaseConfig([
                'default' => 'default',
                'databases' => [
                    'default' => ['connection' => 'mysql']
                ],
                'connections' => [
                    'mysql' => new \Cycle\Database\Config\MySQLDriverConfig(
                        new \Cycle\Database\Config\MySQL\TcpConnectionConfig(
                            $database,
                            $host,
                            3306,
                            'utf8mb4',
                            $username,
                            $password,
                        )
                    )
                ]
            ])
        );

        // create the schema
        $registry = new \Cycle\Schema\Registry($dbal);

        // get all entities and link them, map the directory
        $entities = glob(ABSPATH . 'src/Entities/*.php');

        foreach ($entities as $entityName) {
            $entityName = str_replace('.php', '', $entityName);
            $entityName = str_replace(ABSPATH . 'src/Entities/', '', $entityName);

            /** @var BaseEntity $entity */
            $entity = 'App\\Entities\\' . $entityName;
            $entity::link($registry);
        }

        // compile the schema
        $schema = (new \Cycle\Schema\Compiler())->compile($registry);

        // get the ORM
        App::$orm = new \Cycle\ORM\ORM(new \Cycle\ORM\Factory($dbal), new \Cycle\ORM\Schema($schema));
        App::$dbal = $dbal;
        App::$schema = $schema;
        App::$registry = $registry;

    }
}