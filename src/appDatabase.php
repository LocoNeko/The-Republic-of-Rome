<?php
    use Doctrine\ORM\Tools\Setup;
    //use Doctrine\ORM\EntityManager;
    
    // Connection to the database
    $app['db.options'] = array(
        'driver'   => 'pdo_mysql',
        'dbname' => $config_php['MYSQL_DB'],
        'host' => $config_php['MYSQL_HOST'],
        'user' => $config_php['MYSQL_USER'],
        'password' => $config_php['MYSQL_PASSWORD'],
    );
      
    // Create a simple "default" Doctrine ORM configuration for Annotations
    $isDevMode = true;
    
    // obtaining the entity manager
    //$config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/../src/Entities"), $isDevMode);
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

