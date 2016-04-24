<?php
namespace Entities ;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

/**
 * @Entity @Table(name="proposals")
 **/
class Proposal
{
    /** @Id @Column(type="integer") @GeneratedValue @var int */
    protected $id ;

    /** @ManyToOne(targetEntity="Game", inversedBy="proposals") **/
    private $game ;

}