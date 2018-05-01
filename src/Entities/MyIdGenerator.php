<?php
namespace Entities;
use Doctrine\ORM\Id\AbstractIdGenerator;

class MyIdGenerator extends AbstractIdGenerator
{
    public function generate(\Doctrine\ORM\EntityManager $em, $entity)
    {
        $query = $em->createQuery('SELECT t.id FROM Entities\TraceableEntity t ORDER BY t.id DESC')->setMaxResults(1);
        $result = $query->getResult() ;
        if ($result)
        {
            $id = $result[0]+1;
        }
        else
        {
            $id = 1 ;
        }
        return $id ;
    }
}