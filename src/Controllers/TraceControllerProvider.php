<?php
namespace Controllers ;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class TraceControllerProvider implements ControllerProviderInterface
{
    private $entityManager ;
    
    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];
        $this->entityManager = $app['orm.em'] ;
        
        $controllers->post('/{game_id}/undoTrace', function($game_id) use ($app)
        {
            /** @var $game \Entities\Game  */
            $game = $app['getGame']((int)$game_id) ;
            $messages = $game->getMessages() ;
            /* @var $message \Entities\Message */
            while ($message = $messages->last())
            {
                if ($message->getTraceDescription()===FALSE)
                {
                    $messages->remove($messages->key());
                }
                else
                {
                    break ;
                }
            }
            $operation = $message->getTraceOperation() ;
            $methodName = 'undo'.$operation ;
            if (method_exists($this , $methodName))
            {
                try {
                    $this->$methodName($game , $message);
                    $this->entityManager->persist($game);
                    $this->entityManager->flush();
                } catch (Exception $ex) {
                    do { $app['session']->getFlashBag()->add('danger', sprintf("%s:%d %s [%s]", $ex->getFile(), $ex->getLine(), $ex->getMessage(), get_class($ex))); } while($ex = $ex->getPrevious());
                    return $app->redirect('/') ;
                }
            }
            return $app->redirect('/') ;
        })
        ->bind('trace');

        return $controllers ;
    }
    
    /**
     * Undoers, all called from $controllers->post('/{game_id}/undoTrace'...)
     */
    
    /**
     * 'PickLeader'
     * parameters : array(finished -> boolean)
     * entities : ArrayCollection($party , $leader)
     * 
     * @param \Entities\Game $game
     * @param \Entities\Message $message
     * @return string
     * @throws \Exception
     */
    private function undoPickLeader($game , $message)
    {
        try {
            $trace = $message->getTrace() ;
            $entities = $trace->getEntities() ;
            $parameters = $trace->getParameters() ;
            $party = $entities->first() ;
            $leader = $entities->next() ;
            $finished = $parameters['finished'] ;
            $leader->setLeaderOf(NULL) ;
            $party->resetLeader() ;
            if ($finished)
            {
                $game->setSubPhase('PickLeaders') ;
            }
            $this->entityManager->remove($trace) ;
            $this->entityManager->remove($message) ;
            $this->entityManager->flush();
        } catch (Exception $ex) {
            throw new \Exception($ex);
        }
    }
    
    /**
     * 'PlayStatesman' 
     * parameters : array ('familyLocation' , 'familyLocationName' , 'wasInTheParty' , 'priorConsul' , 'INF' , 'statesmanINF' , 'POP' , 'statesmanPOP' , 'Treasury' , 'Knights' , 'Office' , 'isLeader' )
     * entities : ArrayCollection($party , $statesman , $family , $family->getCardsControlled() , $statesman->getCardsControlled()) 
     * 
     * @param \Entities\Game $game
     * @param \Entities\Message $message
     * @return string
     * @throws \Exception
     */
    private function undoPlayStatesman($game , $message)
    {
        try {
            $trace = $message->getTrace() ;
            $entities = $trace->getEntities() ;
            $parameters = $trace->getParameters() ;
            $party = $entities->first() ;
            /* @var $statesman \Entities\Senator */
            $statesman = $entities->next() ;
            /* @var $family \Entities\Senator */
            $family = $entities->next() ;
            $familyCardControlled = $entities->next() ;
            $statesmanCardControlled = $entities->next() ;
            // The family was in the party
            if (array_key_exists('familyLocation' , $parameters))
            {
                if ($parameters['familyLocation'] && $parameters['familyLocation']=='party' && $parameters['wasInTheParty'])
                {
                    $family->setPriorConsul($parameters['priorConsul']) ;
                    $family->setINF($parameters['INF']);
                    $family->setPOP($parameters['POP']);
                    $family->setTreasury($parameters['Treasury']);
                    $family->setKnights($parameters['Knights']);
                    $family->setOffice($parameters['Office']);
                    if ($parameters['isLeader'])
                    {
                        $party->setLeader($family) ;
                    }
                    // Put the family back in the party
                    $statesman->getCardsControlled()->getFirstCardByProperty('senatorID', $family->getSenatorID() , $party->getSenators()) ;
                    $statesman->resetSenator();
                }
                if ($parameters['familyLocation'] && $parameters['familyLocation']=='game' && $parameters['familyLocationName']=='forum')
                {

                }
            }
            // Put the statesman back in the hand
            $party->getSenators()->getFirstCardByProperty('senatorID', $statesman->getSenatorID() , $party->getHand()) ;
            $this->entityManager->remove($trace) ;
            $this->entityManager->remove($message) ;
            $this->entityManager->flush();
        } catch (Exception $ex) {
            throw new \Exception($ex);
        }
    }

}
