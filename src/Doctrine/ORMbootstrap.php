<?php
    use Doctrine\ORM\Tools\Setup;
    use Doctrine\ORM\EntityManager;

    require_once "../../vendor/autoload.php";

    // Create a simple "default" Doctrine ORM configuration for Annotations
    $isDevMode = true;
    $config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/../Entities"), $isDevMode);

    // database configuration parameters
    $conn = array(
        'driver'   => 'pdo_mysql',
        'dbname' => 'silex_test',
        'host' => 'localhost',
        'user' => 'root',
        'password' => '',
    );

    // obtaining the entity manager
    $entityManager = EntityManager::create($conn, $config);