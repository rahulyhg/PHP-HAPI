<?php
namespace HAPI;

/**
 * Represents a planet that belongs to an alliance (used for the "getallianceplanets" HAPI method).
 * @package HAPI
 * @author Mike Angstadt [github.com/mangstadt]
 */
class AlliancePlanet{
	private $name;
	private $owner;
	private $x;
	private $y;
	
	/**
	 * The planet's production type (see HAPI::PROD_TYPE_*).
	 * @var integer
	 */
	private $prodType;
	
	/**
	 * The planet's race (see HAPI::RACE_*).
	 * @var integer
	 */
	private $race;
	
	private $activity;
	
	/**
	 * The planet's public tag or null if the planet does not have one.
	 * @var string
	 */
	private $publicTag;
	
	/**
	 * The planet's public tag ID or null if the planet does not have one.
	 * @var integer
	 */
	private $publicTagId;
	
	public function getName(){
		return $this->name;
	}

	public function setName($name){
		$this->name = $name;
	}

	public function getOwner(){
		return $this->owner;
	}

	public function setOwner($owner){
		$this->owner = $owner;
	}

	public function getX(){
		return $this->x;
	}

	public function setX($x){
		$this->x = $x;
	}

	public function getY(){
		return $this->y;
	}

	public function setY($y){
		$this->y = $y;
	}

	/**
	 * Gets the planet's production type.
	 * @return integer the planet's production type (see HAPI::PROD_TYPE_*)
	 */
	public function getProdType(){
		return $this->prodType;
	}

	/**
	 * Sets the planet's production type.
	 * @param integer $prodType the planet's production type (see HAPI::PROD_TYPE_*)
	 */
	public function setProdType($prodType){
		$this->prodType = $prodType;
	}

	/**
	 * Gets the planet's race.
	 * @return integer the planet's race (see HAPI::RACE_*)
	 */
	public function getRace(){
		return $this->race;
	}

	/**
	 * Sets the planet's race.
	 * @param integer $race the planet's race (see HAPI::RACE_*)
	 */
	public function setRace($race){
		$this->race = $race;
	}

	public function getActivity(){
		return $this->activity;
	}

	public function setActivity($activity){
		$this->activity = $activity;
	}

	/**
	 * Gets the planet's public tag.
	 * @return string the planet's public tag or null if the planet does not have one
	 */
	public function getPublicTag(){
		return $this->publicTag;
	}

	/**
	 * Sets the planet's public tag.
	 * @param string $publicTag the planet's public tag or null if the planet does not have one
	 */
	public function setPublicTag($publicTag){
		$this->publicTag = $publicTag;
	}

	/**
	 * Gets the planet's public tag ID.
	 * @return integer the planet's public tag ID or null if the planet does not have a public tag
	 */
	public function getPublicTagId(){
		return $this->publicTagId;
	}

	/**
	 * Sets the planet's public tag ID.
	 * @param integer $publicTagId the planet's public tag ID or null if the planet does not have a public tag
	 */
	public function setPublicTagId($publicTagId){
		$this->publicTagId = $publicTagId;
	}
}