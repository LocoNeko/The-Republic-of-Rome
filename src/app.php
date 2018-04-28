<?php
    use Silex\Provider;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Debug\ErrorHandler;
    use Symfony\Component\Debug\ExceptionHandler;
    use Dflydev\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
   
    $app = new Silex\Application() ; 
    $app->register(new Provider\ServiceControllerServiceProvider());
    $app->register(new Provider\SessionServiceProvider());
    $app->register(new Provider\RoutingServiceProvider());
    $app->register(new Provider\DoctrineServiceProvider());
    $app->register(new Provider\SecurityServiceProvider());
    $app->register(new Provider\RememberMeServiceProvider());
    $app->register(new Provider\TwigServiceProvider());
    $app->register(new Provider\SwiftmailerServiceProvider());
    ErrorHandler::register();
    ExceptionHandler::register();
    
    $config_php = parse_ini_file(__DIR__.'/../src/application.ini') ;
    
    $app['debug'] = true;
    $app['BASE_URL'] = $config_php['BASE_URL'] ;
    $app['WS_CLIENT'] = $config_php['WS_CLIENT'] ;

    // If the route starts with the name of a Valid Phase, but the game is in a different phase, replace the phase in the route and redirect
    $app->before(function (Request $request) use ($app)
    {
        // Remove BASE_URL from the URI
        $uri = str_replace($app['BASE_URL'] , '' , $request->getRequestUri()) ;
        $route = explode('/', $uri) ;
        /*
         * The first element (route[0]) in the route is -mysteriously- ''
         * If we are inside a Game, the second element (route[1]) should be a phase.
         */
        if (in_array($route[1], \Entities\Game::$VALID_PHASES))
        {
            $query = $app['orm.em']->createQuery('SELECT g FROM Entities\Game g WHERE g.id = '.(int)$route[2]);
            $result = $query->getResult() ;
            if (count($result)==1)
            {
                $game = $result[0] ;
                // Check if the phase in the route (route[1]) is the same as the phase in the game (game->getPhase()), replace if it's not
                if ($game->getPhase() != $route[1])
                {
                    return $app->redirect( str_replace( $route[1] , $game->getPhase() , $request->getRequestUri() ) ) ;
                }
            }
        }
    });

    // JSON middleware and loading messages as flash bags
    $app->before(function (Request $request) use ($app)
    {
        $request->getSession()->start();
        /*
         * If the header is JSON :
         * - Put JSON data in the request
         * - Don't get new messages as they should never be displayed for json requests, since they are never on screen
         */
        if (0 === strpos($request->headers->get('Content-Type'), 'application/json'))
        {
            $data = json_decode($request->getContent() , TRUE);
            $request->request->replace(is_array($data) ? $data : array());
        }
        else
        {
            if ($app['user'] !== NULL)
            {   
                $messages = getNewMessages($app) ;
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
    
    /**
     * If a game was just created, redirect the creator to Join it immediately
     */
    $app->before(function (Request $request) use ($app)
    {
        if ($app['session']->get('game_just_created')===TRUE)
        {
            $app['session']->set('game_just_created' , FALSE) ;
            return $app->redirect($app['BASE_URL'].'/Lobby/Join/'.$app['session']->get('game_id'));
        }
    });
    
    // Twig views path   
    $app['twig.path'] = array(__DIR__.'/../resources/views/');

    /**
     * Assets
     */
    
    $app->register(new Provider\AssetServiceProvider() , array(
        'assets.version' => 'v2',
        'assets.version_format' => '%s?version=%s',
        'assets.named_packages' => array(
            'css' => array('version' => 'css2' , 'base_path' => $app['BASE_URL'].'/resources/css/') ,
            'js' => array('version' => 'v1' , 'base_path' => $app['BASE_URL'].'/resources/js/') 
        ),
    ));
    
    /**
     * Simple user
     */

    $userServiceProvider = new SimpleUser\UserServiceProvider();
    $app->register($userServiceProvider);

    // Simple user - Firewalls
    $app['security.firewalls'] = array(
        'login' => array(
            'pattern' => '^'.$app['BASE_URL'].'/user/login$',
        ),
        'register' => array(
            'pattern' => '^'.$app['BASE_URL'].'/user/register$',
        ),
        'secured_area' => array(
            'pattern' => '^.*$',
            'anonymous' => false,
            'remember_me' => array(),
            'form' => array(
                'login_path' => $app['BASE_URL'].'/user/login',
                'check_path' => $app['BASE_URL'].'/user/login_check',
            ),
            'logout' => array(
                'logout_path' => $app['BASE_URL'].'/user/logout',
                'target_url' => $app['BASE_URL'],
            ),
            'users' => function($app) { return $app['user.manager']; },
        ),
    );

    // Simple user - options
    $app['user.options'] = array(
        'templates' => array(
            'layout' => '/layout.twig',
            'register' => '/simpleuser/register.twig',
            'register-confirmation-sent' => '/simpleuser/register-confirmation-sent.twig',
            'login' => '/simpleuser/login.twig',
            'login-confirmation-needed' => '/simpleuser/login-confirmation-needed.twig',
            'forgot-password' => '/simpleuser/forgot-password.twig',
            'reset-password' => '/simpleuser/reset-password.twig',
            'view' => '/simpleuser/view.twig',
            'edit' => '/simpleuser/edit.twig',
            'list' => '/simpleuser/list.twig',
        ),
    );

    /**
     * Database
     */
    
    // Connection to the database
    $app['db.options'] = array(
        'driver'   => 'pdo_mysql',
        'dbname' => $config_php['MYSQL_DB'],
        'host' => $config_php['MYSQL_HOST'],
        'user' => $config_php['MYSQL_USER'],
        'password' => $config_php['MYSQL_PASSWORD'],
    );
    
    // Doctrine ORM
    $app->register(new DoctrineOrmServiceProvider , array(
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

    // The Welcome Page
    $app->get($app['BASE_URL'].'/', function () use ($app) {
        return $app['twig']->render('hello.twig', array(
            'layout_template' => 'layout.twig',
        ));
    });

    // Mount SimpleUser routes.
    $app->mount($app['BASE_URL'].'/user', $userServiceProvider);

    // Routes base paths : 'Lobby' or a specific {phase}
    $app->mount($app['BASE_URL'].'/Lobby'      , new Controllers\LobbyControllerProvider($app) );
    $app->mount($app['BASE_URL'].'/Setup'      , new Controllers\SetupControllerProvider($app) );
    $app->mount($app['BASE_URL'].'/Mortality'  , new Controllers\MortalityControllerProvider($app) );
    $app->mount($app['BASE_URL'].'/Revenue'    , new Controllers\RevenueControllerProvider($app) );
    $app->mount($app['BASE_URL'].'/Forum'      , new Controllers\ForumControllerProvider($app) );
    $app->mount($app['BASE_URL'].'/Population' , new Controllers\PopulationControllerProvider($app) );
    $app->mount($app['BASE_URL'].'/Senate'     , new Controllers\SenateControllerProvider($app) );
    $app->mount($app['BASE_URL'].'/Trace'      , new Controllers\TraceControllerProvider($app) );

    /**
     * 
     * @param type $app
     * @return boolean
     */
    function getNewMessages($app) {
        $user_id = $app['user']->getId() ;
        $entityManager = $app['orm.em'] ;
        try 
        {
            /** @var \Entities\Game $game */
            $game = $app['getGame']((int)$app['session']->get('game_id')) ;
        }
        catch (Exception $ex)
        {
            return FALSE ;
        }
        $messages = $game->getNewMessages($user_id) ;
        $entityManager->persist($game) ;
        $entityManager->flush() ;
        return array('messages' => $messages , 'parties_names' => $game->getPartiesNames()) ;
    }

    /**
     * A Service that returns a Game entity corresponding to this $game_id
     * @param int $game_id
     * @param boolean $checkStarted Check whther or not the game has started
     * @param array $checkSubPhases Fail if the game's subPhase is not in this array, 
     * @return \Entities\Game|boolean
     * @throws \Exception not found , not started , wrong sub phase
     */
    $app['getGame'] = $app->protect(function ($game_id , $checkStarted = TRUE , $checkSubPhases = NULL) use ($app) {
        $query = $app['orm.em']->createQuery('SELECT g FROM Entities\Game g WHERE g.id = '.(int)$game_id);
        /** @var \Entities\Game[] $result */
        $result = $query->getResult() ;
        if (count($result)!==1)
        {
            throw new \Exception(sprintf(_('ERROR - No game with unique id %1$d') , (int)$game_id)) ;
        }
        elseif($checkStarted && !$result[0]->gameStarted())
        {
            throw new \Exception(sprintf(_('ERROR - Game %1$s not started.') , (int)$game_id )) ;
        }
        elseif ( ($checkSubPhases != NULL) && !in_array($result[0]->getSubPhase(), $checkSubPhases))
        {
            throw new \Exception(sprintf(_('ERROR - Sub phase not recognised.') , (int)$game_id )) ;
        }
        $app['session']->set('game_id', (int)$game_id);
        return $result[0];
    });

    // Persist & Flush the Game entity, as it might have been updated during rendering (e.g. when updating the LastUpdate of each Party)
    $app->after(function (Request $request) use ($app)
    {
        try 
        {
            /** @var \Entities\Game $game */
            $game = $app['getGame']((int)$app['session']->get('game_id')) ;
            $app['orm.em']->persist($game) ;
            $app['orm.em']->flush() ;
        }
        catch (Exception $ex)
        {
            do { $app['session']->getFlashBag()->add('danger', sprintf("%s:%d %s [%s]", $ex->getFile(), $ex->getLine(), $ex->getMessage(), get_class($ex))); } while($ex = $ex->getPrevious());
        }
    });
    
    $app->error(function (\Exception $exception, $code) {
        $message = 'Exception : '.sprintf("%s:%d\n %s\n [%s]\n%s", $exception->getFile(), $exception->getLine(), $exception->getMessage(), get_class($exception) , $exception->getTraceAsString());
        return new Response(nl2br($message));
    });

    $app->run() ;
