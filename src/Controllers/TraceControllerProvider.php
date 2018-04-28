<?php
namespace Controllers ;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

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
                }
            }
            return $app->json( 'SUCCESS' , 201);
        })
        ->bind('undoTrace');

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
     * entities : ArrayCollection($party , $statesman , $family) 
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
                }
                // Put the family back in the forum
                if ($parameters['familyLocation'] && $parameters['familyLocation']=='game' && $parameters['familyLocationName']=='forum')
                {
                    $statesman->getCardsControlled()->getFirstCardByProperty('senatorID', $family->getSenatorID() , $game->getDeck('forum')) ;

                }
            }
            // Put the statesman back in the hand
            $party->getSenators()->getFirstCardByProperty('senatorID', $statesman->getSenatorID() , $party->getHand()) ;
            // If the statesman has controlled cards, they belonged to the family. Put them back
            while($statesman->getCardsControlled()->getNumberOfCards()>0)
            {
                $family->getCardsControlled()->putCardOnTop($statesman->getCardsControlled()->drawFirstCard()) ;
            }
            $statesman->resetSenator();
            $this->entityManager->remove($trace) ;
            $this->entityManager->remove($message) ;
            $this->entityManager->flush();
        } catch (Exception $ex) {
            throw new \Exception($ex);
        }
    }

    /**
     * 'PlayConcession' 
     * parameters : NULL
     * entities : ArrayCollection($recipient , $concession)
     * 
     * @param \Entities\Game $game
     * @param \Entities\Message $message
     * @return string
     * @throws \Exception
     */
    private function undoPlayConcession($game , $message)
    {
        try {
            $trace = $message->getTrace() ;
            $entities = $trace->getEntities() ;
            $party = $entities->first() ;
            $recipient= $entities->next() ;
            $concession = $entities->next() ;
            $recipient->getCardsControlled()->getFirstCardByProperty('cardId' , $concession->getCardId() , $party->getHand()) ;
            $this->entityManager->remove($trace) ;
            $this->entityManager->remove($message) ;
            $this->entityManager->flush();
        } catch (Exception $ex) {
            throw new \Exception($ex);
        }
    }
    
    /**
     * 'DonePlayingCards'
     * parameters : NULL
     * entities : ArrayCollection($party)
     * 
     * @return string
     * @throws \Exception
     */
    private function undoDonePlayingCards($game , $message)
    {
        try {
            $trace = $message->getTrace() ;
            $entities = $trace->getEntities() ;
            $party = $entities->first() ;
            $party->setIsDone(FALSE) ;
            if ($game->getPhase()=='Mortality')
            {
                $game->setPhase('Setup') ;
                $game->setSubPhase('PlayCards') ;
            }
        } catch (Exception $ex) {
            throw new \Exception($ex);
        }
    }

}
