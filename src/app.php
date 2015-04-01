<?php
    use Silex\Provider;
    use Symfony\Component\HttpFoundation\Response;

    $app->register(new Provider\SessionServiceProvider());
    $app->register(new Provider\ServiceControllerServiceProvider());
    $app->register(new Provider\UrlGeneratorServiceProvider());
    $app->register(new Provider\TwigServiceProvider());
    $app->register(new Provider\SwiftmailerServiceProvider());
    $app->register(new Provider\SecurityServiceProvider());
    $app->register(new Provider\DoctrineServiceProvider());

    $app['debug'] = true;

    // JSON middleware
    use Symfony\Component\HttpFoundation\Request;
    $app->before(function (Request $request) {
        if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($request->getContent(), true);
            $request->request->replace(is_array($data) ? $data : array());
        }
    });

    // Twig views path   
    $app['twig.path'] = array(__DIR__.'/../resources/views/');

    // Includes
    require __DIR__.'/../src/appDatabase.php';
    require __DIR__.'/../src/appSimpleUser.php';

    $app->get('/', function () use ($app) {
        return $app['twig']->render('hello.twig', array(
            'layout_template' => 'layout.twig',
        ));
    });

    $app->mount('/Lobby', new Controllers\LobbyControllerProvider($app) );
    $app->mount('/Setup', new Controllers\SetupControllerProvider($app) );
    
    /*
    $app->error(function (\Exception $e, $code) {
        switch ($code) {
            case 404:
                $message = 'The requested page could not be found.';
                break;
            default:
                $message = 'We are sorry, but something went terribly wrong.';
        }

        return new Response($message);
    });
     */    
    

