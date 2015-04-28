<?php
    use Silex\Provider;
    use Symfony\Component\HttpFoundation\Response;
    use Doctrine\Common\Collections\ArrayCollection;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\Debug\ErrorHandler;
    use Symfony\Component\Debug\ExceptionHandler;

    $app->register(new Provider\ServiceControllerServiceProvider());
    $app->register(new Provider\SessionServiceProvider());
    $app->register(new Provider\UrlGeneratorServiceProvider());
    $app->register(new Provider\TwigServiceProvider());
    $app->register(new Provider\SwiftmailerServiceProvider());
    $app->register(new Provider\SecurityServiceProvider());
    $app->register(new Provider\DoctrineServiceProvider());
    ErrorHandler::register();
    ExceptionHandler::register();
    
    $config = parse_ini_file(__DIR__.'/../src/ROR_V2.ini') ;
    
    $app['debug'] = true;
    $app['BASE_URL'] = $config['BASE_URL'] ;
    $app['WS_CLIENT'] = $config['WS_CLIENT'] ;

    // If the route starts with the name of a Valid Phase, but the game is in a different phase, replace the phase in the route 
    $app->before(function (Request $request) use ($app)
    {
        $route = explode('/', $request->getRequestUri()) ;
        if (in_array($route[1], \Entities\Game::$VALID_PHASES))
        {
            $query = $app['orm.em']->createQuery('SELECT g FROM Entities\Game g WHERE g.id = '.(int)$route[2]);
            $result = $query->getResult() ;
            if (count($result)==1)
            {
                $game = $result[0] ;
                if ($game->getPhase() != $route[1])
                {
                    error_log('redirecting') ;
                    return $app->redirect( str_replace( $route[1] , $game->getPhase() , $request->getRequestUri() ) ) ;
                }
            }
        }
    });

    // JSON middleware and loading messages as flash bags
    $app->before(function (Request $request) use ($app)
    {
        $request->getSession()->start();
        if (0 === strpos($request->headers->get('Content-Type'), 'application/json'))
        {
            $data = json_decode($request->getContent() , TRUE);
            $request->request->replace(is_array($data) ? $data : array());
        }
        else
        {
            // Getting new messages should never be done for json requests, as they don't trigger display
            if ($app['user'] !== NULL)
            {
                $messages = getNewMessages($app['user']->getId() , $app['session']->get('game_id') , $app['orm.em'] ) ;
                if ($messages!==FALSE && count($messages['messages'])>0)
                {
                    foreach($messages['messages'] as $key=>$message)
                    {
                        $app['session']->getFlashBag()->add(
                            $message->getFlashType(),
                            $message->show($app['user']->getId() , $messages['parties_names'])
                        );
                    }
                }
            }
        }
    });

    // Twig views path   
    $app['twig.path'] = array(__DIR__.'/../resources/views/');

    // Includes
    require __DIR__.'/../src/appDatabase.php';
    require __DIR__.'/../src/appSimpleUser.php';

    $app->get($app['BASE_URL'].'/', function () use ($app) {
        return $app['twig']->render('hello.twig', array(
            'layout_template' => 'layout.twig',
        ));
    });

    $app->mount($app['BASE_URL'].'/Lobby', new Controllers\LobbyControllerProvider($app) );
    $app->mount($app['BASE_URL'].'/Setup', new Controllers\SetupControllerProvider($app) );
    $app->mount($app['BASE_URL'].'/Revenue', new Controllers\RevenueControllerProvider($app) );
    
    function getNewMessages($user_id , $game_id , $entityManager) {
        $query = $entityManager->createQuery('SELECT g FROM Entities\Game g WHERE g.id = '.(int)$game_id);
        $result = $query->getResult() ;
        if (count($result)==1) {
            $messages = $result[0]->getNewMessages($user_id) ;
            $entityManager->persist($result[0]) ;
            $entityManager->flush() ;
            return array('messages' => $messages , 'parties_names' => $result[0]->getPartiesNames()) ;
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
