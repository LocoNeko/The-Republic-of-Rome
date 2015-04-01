<?php
    use Doctrine\ORM\Tools\Setup;
    //use Doctrine\ORM\EntityManager;
    
    // Connection to the database
    $app['db.options'] = array(
        'driver'   => 'pdo_mysql',
        'dbname' => 'silex_test',
        'host' => 'localhost',
        'user' => 'root',
        'password' => '',
    );
    
    // Create a simple "default" Doctrine ORM configuration for Annotations
    $isDevMode = true;
    $config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/../src/Entities"), $isDevMode);

    // obtaining the entity manager
    //$entityManager = EntityManager::create($app['db.options'], $config);
    
    // Doctrine ORM
    $app->register(new Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider , array(
        "orm.proxies_dir" => __DIR__."/../src/Entities/Proxies",
        "orm.em.options" => array(
            "mappings" => array(
                array(
                    "type" => "annotation",
                    "namespace" => "Entities",
                    "path" => __DIR__."/../src/Entities",
                ),
            ),
        ),
    ));

