<?php

namespace DoctrineProxies\__CG__\Entities;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class Leader extends \Entities\Leader implements \Doctrine\ORM\Proxy\Proxy
{
    /**
     * @var \Closure the callback responsible for loading properties in the proxy object. This callback is called with
     *      three parameters, being respectively the proxy object to be initialized, the method that triggered the
     *      initialization process and an array of ordered parameters that were passed to that method.
     *
     * @see \Doctrine\Common\Persistence\Proxy::__setInitializer
     */
    public $__initializer__;

    /**
     * @var \Closure the callback responsible of loading properties that need to be copied in the cloned object
     *
     * @see \Doctrine\Common\Persistence\Proxy::__setCloner
     */
    public $__cloner__;

    /**
     * @var boolean flag indicating if this object was already initialized
     *
     * @see \Doctrine\Common\Persistence\Proxy::__isInitialized
     */
    public $__isInitialized__ = false;

    /**
     * @var array properties to be lazy loaded, with keys being the property
     *            names and values being their default values
     *
     * @see \Doctrine\Common\Persistence\Proxy::__getLazyProperties
     */
    public static $lazyPropertiesDefaults = array();



    /**
     * @param \Closure $initializer
     * @param \Closure $cloner
     */
    public function __construct($initializer = null, $cloner = null)
    {

        $this->__initializer__ = $initializer;
        $this->__cloner__      = $cloner;
    }







    /**
     * 
     * @return array
     */
    public function __sleep()
    {
        if ($this->__isInitialized__) {
            return array('__isInitialized__', 'matches', 'description', 'strength', 'disaster', 'standoff', 'ability', 'causes', 'internalId', 'id', 'name');
        }

        return array('__isInitialized__', 'matches', 'description', 'strength', 'disaster', 'standoff', 'ability', 'causes', 'internalId', 'id', 'name');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (Leader $proxy) {
                $proxy->__setInitializer(null);
                $proxy->__setCloner(null);

                $existingProperties = get_object_vars($proxy);

                foreach ($proxy->__getLazyProperties() as $property => $defaultValue) {
                    if ( ! array_key_exists($property, $existingProperties)) {
                        $proxy->$property = $defaultValue;
                    }
                }
            };

        }
    }

    /**
     * 
     */
    public function __clone()
    {
        $this->__cloner__ && $this->__cloner__->__invoke($this, '__clone', array());
    }

    /**
     * Forces initialization of the proxy
     */
    public function __load()
    {
        $this->__initializer__ && $this->__initializer__->__invoke($this, '__load', array());
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __isInitialized()
    {
        return $this->__isInitialized__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitialized($initialized)
    {
        $this->__isInitialized__ = $initialized;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitializer(\Closure $initializer = null)
    {
        $this->__initializer__ = $initializer;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __getInitializer()
    {
        return $this->__initializer__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setCloner(\Closure $cloner = null)
    {
        $this->__cloner__ = $cloner;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific cloning logic
     */
    public function __getCloner()
    {
        return $this->__cloner__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     * @static
     */
    public function __getLazyProperties()
    {
        return self::$lazyPropertiesDefaults;
    }

    
    /**
     * {@inheritDoc}
     */
    public function setMatches($matches)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setMatches', array($matches));

        return parent::setMatches($matches);
    }

    /**
     * {@inheritDoc}
     */
    public function setDescription($description)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setDescription', array($description));

        return parent::setDescription($description);
    }

    /**
     * {@inheritDoc}
     */
    public function setStrength($strength)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setStrength', array($strength));

        return parent::setStrength($strength);
    }

    /**
     * {@inheritDoc}
     */
    public function setDisaster($disaster)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setDisaster', array($disaster));

        return parent::setDisaster($disaster);
    }

    /**
     * {@inheritDoc}
     */
    public function setStandoff($standoff)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setStandoff', array($standoff));

        return parent::setStandoff($standoff);
    }

    /**
     * {@inheritDoc}
     */
    public function setAbility($ability)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setAbility', array($ability));

        return parent::setAbility($ability);
    }

    /**
     * {@inheritDoc}
     */
    public function setCauses($causes)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCauses', array($causes));

        return parent::setCauses($causes);
    }

    /**
     * {@inheritDoc}
     */
    public function getMatches()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getMatches', array());

        return parent::getMatches();
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDescription', array());

        return parent::getDescription();
    }

    /**
     * {@inheritDoc}
     */
    public function getStrength()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getStrength', array());

        return parent::getStrength();
    }

    /**
     * {@inheritDoc}
     */
    public function getDisaster()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDisaster', array());

        return parent::getDisaster();
    }

    /**
     * {@inheritDoc}
     */
    public function getStandoff()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getStandoff', array());

        return parent::getStandoff();
    }

    /**
     * {@inheritDoc}
     */
    public function getAbility()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getAbility', array());

        return parent::getAbility();
    }

    /**
     * {@inheritDoc}
     */
    public function getCauses()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCauses', array());

        return parent::getCauses();
    }

    /**
     * {@inheritDoc}
     */
    public function saveData()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'saveData', array());

        return parent::saveData();
    }

    /**
     * {@inheritDoc}
     */
    public function setId($id)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setId', array($id));

        return parent::setId($id);
    }

    /**
     * {@inheritDoc}
     */
    public function setName($name)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setName', array($name));

        return parent::setName($name);
    }

    /**
     * {@inheritDoc}
     */
    public function setDeck($deck)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setDeck', array($deck));

        return parent::setDeck($deck);
    }

    /**
     * {@inheritDoc}
     */
    public function setPreciseType($preciseType)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setPreciseType', array($preciseType));

        return parent::setPreciseType($preciseType);
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getId', array());

        return parent::getId();
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getName', array());

        return parent::getName();
    }

    /**
     * {@inheritDoc}
     */
    public function getDeck()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDeck', array());

        return parent::getDeck();
    }

    /**
     * {@inheritDoc}
     */
    public function getPreciseType()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPreciseType', array());

        return parent::getPreciseType();
    }

    /**
     * {@inheritDoc}
     */
    public function getCardsControlled()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCardsControlled', array());

        return parent::getCardsControlled();
    }

    /**
     * {@inheritDoc}
     */
    public function getValue($property)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getValue', array($property));

        return parent::getValue($property);
    }

    /**
     * {@inheritDoc}
     */
    public function getLocation()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLocation', array());

        return parent::getLocation();
    }

}