<?php
    use Silex\Provider;
    use Symfony\Component\HttpFoundation\Response;

    $app->register(new Provider\ServiceControllerServiceProvider());
    $app->register(new Provider\SessionServiceProvider());
    $app->register(new Provider\UrlGeneratorServiceProvider());
    $app->register(new Provider\TwigServiceProvider());
    $app->register(new Provider\SwiftmailerServiceProvider());
    $app->register(new Provider\SecurityServiceProvider());
    $app->register(new Provider\DoctrineServiceProvider());

    $app['debug'] = true;

    // JSON middleware
    use Symfony\Component\HttpFoundation\Request;
    $app->before(function (Request $request) use ($app) {
        if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($request->getContent() , TRUE);
            $request->request->replace(is_array($data) ? $data : array());
        }
        $request->getSession()->start();
        $messages = getNewMessages($app['user']->getId() , $app['session']->get('game_id') , $app['orm.em'] ) ;
        if (count($messages)>0) {
            foreach($messages as $key=>$message) {
                $app['session']->getFlashBag()->add($message->getFlashType(), $message->show());
            }
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
    
    function getNewMessages($user_id , $game_id , $entityManager) {
        $query = $entityManager->createQuery('SELECT g FROM Entities\Game g WHERE g.id = '.(int)$game_id);
        $result = $query->getResult() ;
        if (count($result)==1) {
            return $result[0]->getNewMessages($user_id) ;
        } else {
            return FALSE ;
        }
    }

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
