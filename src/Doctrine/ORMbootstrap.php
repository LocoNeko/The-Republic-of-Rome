<?php
    use Doctrine\ORM\Tools\Setup;
    use Doctrine\ORM\EntityManager;

    require_once "../../vendor/autoload.php";

    $config = parse_ini_file(__DIR__.'/../ROR_V2.ini') ;

    // Create a simple "default" Doctrine ORM configuration for Annotations
    $isDevMode = true;
    $config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/../Entities"), $isDevMode);

    // database configuration parameters
    $conn = array(
        'driver'   => 'pdo_mysql',
        'dbname' => $config['MYSQL_DB'],
        'host' => $config['MYSQL_HOST'],
        'user' => $config['MYSQL_USER'],
        'password' => $config['MYSQL_PASSWORD'],
    );

    // obtaining the entity manager
    $entityManager = EntityManager::create($conn, $config);