<?php
namespace Controllers ;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class SetupControllerProvider implements ControllerProviderInterface
{
    private $entityManager ;
    
    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];
        $this->entityManager = $app['orm.em'] ;

        /*
         * Setup
         */
        $controllers->get('/{game_id}', function($game_id) use ($app)
        {
            $game_id = (int)$game_id ;
            $app['session']->set('game_id', $game_id);
            $query = $this->entityManager->createQuery('SELECT g FROM Entities\Game g WHERE g.id = '.(int)$game_id);
            $result = $query->getResult() ;
            if (count($result)!=1) {
                $app['session']->getFlashBag()->add('alert', sprintf(_('Error - Game %1$s not found.') , $game_id ));
                return $app->redirect('/') ;
            } elseif(!$result[0]->gameStarted()) {
                $app['session']->getFlashBag()->add('alert', sprintf(_('Error - Game %1$s not started.') , $game_id ));
                return $app->redirect('/') ;
            } else {
                return $app['twig']->render('Setup/Main.twig', array(
                    'layout_template' => 'layout.twig' ,
                    'game' => $result[0]
                ));
            }
        })
        ->bind('Setup');
        
         /*
        * POST target
        * Verb : PickLeader
        * JSON data : "card_id"
        */
        $controllers->post('/{game_id}/PickLeader', function($game_id , Request $request) use ($app)
        {
            $game_id = (int)$game_id ;
            $query = $this->entityManager->createQuery('SELECT g FROM Entities\Game g WHERE g.id = '.(int)$game_id);
            $result = $query->getResult() ;
           
            if (count($result)==1) {
                try {
                    $game = $result[0] ;
                    $leader = FALSE ;
                    foreach ($game->getAllSenators() as $senator) {
                        if ($senator->getId()==$request->request->get('card_id')) {
                            $leader = $senator ;
                            break ;
                        }
                    }
                    if ($leader!=FALSE) {
                        $user_id = (int)$app['user']->getId() ;
                        $location = $leader->getLocation() ;
                        if ($location['type'] == 'party' && $user_id == $location['value']->getUser_id()) {
                            $party = $location['value'] ;
                            $party->setLeader($leader) ;
                            $game->log(_('%1$s is appointed leader of %2$s').' ([['.$user_id.']])','log',array($leader->getName() , $party->getUser_id()));
                            // If veryone has picked a leader, move to next phase
                            $finished = TRUE ;
                            foreach ($game->getParties() as $aParty) {
                                if ($aParty->getLeader() == NULL) {
                                    $finished = FALSE ;
                                }
                            }
                            if ($finished) {
                                $game->setSubPhase('Play cards') ;
                            }
                            $this->entityManager->persist($game);
                            $this->entityManager->flush();
                            return $app->json( 'SUCCESS' , 201);
                        } else {
                            $app['session']->getFlashBag()->add('error', _('ERROR - rong user or Senator not in a party'));
                            return $app->json( _('ERROR - Wrong user or Senator not in a party') , 201);
                        }
                    } else {
                        $app['session']->getFlashBag()->add('error', _('ERROR - Senator not found'));
                        return $app->json( _('ERROR - Senator not found') , 201);
                    }
                } catch (\Exception $e) {
                    $app['session']->getFlashBag()->add('error', $e->getMessage());
                    return $app->json( $e->getMessage() , 201);
                }
            } else {
                $app['session']->getFlashBag()->add('danger', sprintf(_('Error - Game %1$s not found.') , $game_id ));
                return $app->json( _('Error - Game %1$s not found.') , 201);
            }
        })
        ->bind('PickLeaderAction');
        return $controllers ;
    }
    
}