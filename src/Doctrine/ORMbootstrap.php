<?php
    use Doctrine\ORM\Tools\Setup;
    use Doctrine\ORM\EntityManager;

    require_once "../../vendor/autoload.php";

    $config_php = parse_ini_file(__DIR__.'/../ROR_V2.ini') ;

    // Create a simple "default" Doctrine ORM configuration for Annotations
    $isDevMode = true;
    $config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/../Entities"), $isDevMode);

    // database configuration parameters
    $conn = array(
        'driver'   => 'pdo_mysql',
        'dbname' => $config_php['MYSQL_DB'],
        'host' => $config_php['MYSQL_HOST'],
        'user' => $config_php['MYSQL_USER'],
        'password' => $config_php['MYSQL_PASSWORD'],
    );

    // obtaining the entity manager
    $entityManager = EntityManager::create($conn, $config);