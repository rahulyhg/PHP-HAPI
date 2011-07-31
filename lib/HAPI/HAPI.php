<?php
namespace HAPI;

/**
 * The interface for interacting with the Hyperiums API (HAPI).&nbsp;
 * Compatable with HAPI v0.1.8.
 * @package HAPI
 * @author Mike Angstadt [github.com/mangstadt]
 * @version 0.3.3
 */
class HAPI{
	/**
	 * The URL to the HAPI web service.
	 * @var string
	 */
	const URL = 'http://www.hyperiums.com/servlet/HAPI';
	
	/**
	 * One request can be sent every 2 seconds without breaking HAPI query limits.&nbsp;
	 * HAPI allows a max of 3 requests per second and 30 requests per minute.
	 * @var integer
	 */
	const SECONDS_PER_REQUEST = 2;
	
	const RACE_HUMAN = 0;
	const RACE_AZTERK = 1;
	const RACE_XILLOR = 2;
	
	const PROD_TYPE_ARGO = 0;
	const PROD_TYPE_MINERO = 1;
	const PROD_TYPE_TECHNO = 2;
	
	const GOV_DICT = 0;
	const GOV_AUTH = 1;
	const GOV_DEMO = 2;
	const GOV_HYP = 3;
	
	const RANK_ENSIGN = 0;
	const RANK_LIEUTENANT = 1;
	const RANK_LIEUTENANT_COMMANDER = 2;
	const RANK_COMMANDER = 3;
	const RANK_CAPTAIN = 4;
	const RANK_FLEET_CAPTAIN = 5;
	const RANK_COMMODORE = 6;
	const RANK_REAR_ADMIRAL = 7;
	const RANK_VICE_ADMIRAL = 8;
	const RANK_ADMIRAL = 9;
	const RANK_FLEET_ADMIRAL = 10;
	
	
	/**
	 * True to log all requests/responses, false not to.
	 * @var boolean
	 */
	private static $logFile;
	
	/**
	 * The absolute path to the lock directory that is used for flood protection or null to disable flood protection.&nbsp;
	 * This directory's permissions must allow PHP to write to it.
	 * @var string
	 */
	private $floodLockDir;
	
	/**
	 * True to check to see if each response comes from a cache or not, false not to.
	 * @var boolean
	 */
	private static $cacheDetection = true;

	/**
	 * The HAPI session.
	 * @var HAPISession
	 */
	private $session;
	
	/**
	 * Creates a new HAPI connection.
	 * @param string $gameName the game to connect to
	 * @param string $username the username
	 * @param string $hapiKey the external authentication key (login to Hyperiums and go to Preferences &gt; Authentication to generate one)
	 * @param string $floodLockDir (optional) the *absolute* path to the directory where the lock files will be stored (one file per user) or null to disable flood protection (defaults to null).  The directory must be writable by the web server process.
	 * @throws Exception if there was a problem authenticating or the authentication failed
	 */
	public function __construct($gameName, $username, $hapiKey, $floodLockDir = null){
		$this->setFloodProtection($floodLockDir);
		$this->session = $this->authenticate($gameName, $username, $hapiKey);
	}
	
	/**
	 * Authenticates the user so HAPI requests can be made.
	 * @param string $gameName the game to connect to
	 * @param string $username the player's username
	 * @param string $hapiKey the external authentication key (to generate one, login to Hyperiums and go to "Preferences &gt; Authentication")
	 * @throws Exception if there was a problem authenticating or the authentication failed
	 * @return HAPISession the HAPI session info
	 */
	protected function authenticate($gameName, $username, $hapiKey){
		$params = array(
			"game"=>$gameName,
			"player"=>$username,
			"hapikey"=>$hapiKey
		);
		$respParams = self::sendRequest(null, $params);

		$session = new HAPISession($respParams["gameid"], $respParams["playerid"], $respParams["playername"], $respParams["authkey"], strtotime($respParams["servertime"]));
		return $session;
	}
	
	/**
	 * Gets a list of all games.
	 * @throws Exception if there was a problem making the request
	 * @return array(Game) all games
	 */
	public static function getAllGames(){
		$respParams = self::sendRequest("games");
		
		//parse the game information from the response
		$games = array();
		for ($i = 0; isset($respParams["game$i"]); $i++){
			$game = new Game();
			$game->setName($respParams["game$i"]);
			$game->setState($respParams["state$i"]);
			$game->setDescription($respParams["descr$i"]);
			$game->setLength($respParams["length$i"]);
			$game->setMaxEndDate($respParams["maxenddate$i"]); //this isn't a date
			$game->setPeec($respParams["ispeec$i"]);
			$game->setMaxPlanets($respParams["maxplanets$i"]);
			$game->setInitCash($respParams["initcash$i"]);
			$game->setMaxOfferedPlanets($respParams["maxofferedplanets$i"]);
			$game->setNextPlanetDelay($respParams["nextplanetdelay$i"]);
			$games[] = $game;
		}
		return $games;
	}
	
	/**
	 * Downloads the daily-generated list of alliances.&nbsp;
	 * The list is gzipped and can only be downloaded once per day.
	 * @param string $username the account username
	 * @param string $password the account password
	 * @param string $gameName the game name
	 * @param string $file the *absolute* path to save the file to.  The file name should end with ".txt.gz".
	 * @throws Exception if you have already downloaded the file today or there was a problem saving the file to disk
	 */
	public static function downloadAlliances($username, $password, $gameName, $file){
		self::download("alliances", $username, $password, $gameName, $file);
	}
	
	/**
	 * Downloads the daily-generated list of events.&nbsp;
	 * The list is gzipped and can only be downloaded once per day.
	 * @param string $username the account username
	 * @param string $password the account password
	 * @param string $gameName the game name
	 * @param string $file the *absolute* path to save the file to.  The file name should end with ".txt.gz".
	 * @throws Exception if you have already downloaded the file today or there was a problem saving the file to disk
	 */
	public static function downloadEvents($username, $password, $gameName, $file){
		self::download("events", $username, $password, $gameName, $file);
	}
	
	/**
	 * Downloads the daily-generated list of players.&nbsp;
	 * The list is gzipped and can only be downloaded once per day.
	 * @param string $username the account username
	 * @param string $password the account password
	 * @param string $gameName the game name
	 * @param string $file the *absolute* path to save the file to.  The file name should end with ".txt.gz".
	 * @throws Exception if you have already downloaded the file today or there was a problem saving the file to disk
	 */
	public static function downloadPlayers($username, $password, $gameName, $file){
		self::download("players", $username, $password, $gameName, $file);
	}
	
	/**
	 * Downloads the daily-generated list of planets.&nbsp;
	 * The list is gzipped and can only be downloaded once per day.
	 * @param string $username the account username
	 * @param string $password the account password
	 * @param string $gameName the game name
	 * @param string $file the *absolute* path to save the file to.  The file name should end with ".txt.gz".
	 * @throws Exception if you have already downloaded the file today or there was a problem saving the file to disk
	 */
	public static function downloadPlanets($username, $password, $gameName, $file){
		self::download("planets", $username, $password, $gameName, $file);
	}
	
	/**
	 * Downloads one of the daily-generated lists.&nbsp;
	 * Each list is gzipped and can only be downloaded once per day.
	 * @param string $type the file type
	 * @param string $username the account username
	 * @param string $password the account password
	 * @param string $gameName the game name
	 * @param string $file the *absolute* path to save the file to.  The file name should end with ".txt.gz".  If a file already exists with this name, it will be overwritten.
	 * @throws Exception if you have already downloaded the file today or there was a problem saving the file to disk
	 */
	private static function download($type, $username, $password, $gameName, $file){
		//create non-existant directories
		$dir = dirname($file);
		if ($dir != "." && !file_exists($dir)){
			$result = mkdir($dir, 0774, true);
			if ($result === false){
				throw new \Exception("Could not create non-existant directories: $dir");
			}
		}
		
		//send request
		$params = array(
			"game"=>$gameName,
			"player"=>$username,
			"passwd"=>$password,
			"filetype"=>$type
		);
		$response = self::sendRequest("download", $params, null, true);
		
		//save response to file
		$result = file_put_contents($file, $response);
		if ($result === false){
			throw new \Exception("Could not save the file.");
		}
	}
	
	/**
	 * Gets the HAPI session information.
	 * @return HAPISession the HAPI session information
	 */
	public function getSession(){
		return $this->session;
	}
	
	/**
	 * Gets information on all moving fleets.
	 * @throws Exception if there was a problem making the request
	 * @return array(MovingFleet)
	 */
	public function getMovingFleets(){
		$resp = $this->sendAuthRequest("getmovingfleets");
		
		$movingFleets = array();
		for ($i = 0; isset($resp["fleetid$i"]); $i++){
			$movingFleet = new MovingFleet();
			$movingFleet->setId($resp["fleetid$i"]);
			$movingFleet->setName($resp["fname$i"]);
			$movingFleet->setFrom($resp["from$i"]);
			$movingFleet->setTo($resp["to$i"]);
			$movingFleet->setDistance($resp["dist$i"]);
			$movingFleet->setDelay($resp["delay$i"]);
			$movingFleet->setDefending($resp["defend$i"]);
			$movingFleet->setAutoDropping($resp["autodrop$i"]);
			$movingFleet->setCamouflaged($resp["camouf$i"]);
			$movingFleet->setBombing($resp["bombing$i"]);
			$movingFleet->setRace($resp["race$i"]);
			$movingFleet->setBombers($resp["nbbomb$i"]);
			$movingFleet->setDestroyers($resp["nbdest$i"]);
			$movingFleet->setCruisers($resp["nbcrui$i"]);
			$movingFleet->setScouts($resp["nbscou$i"]);
			$movingFleet->setArmies($resp["nbarm$i"]);
			$movingFleets[] = $movingFleet;
		}
		return $movingFleets;
	}
	
	/**
	 * Gets exploitation information from all your planets.
	 * @throws Exception if there was a problem making the request
	 * @return array(Exploitation)
	 */
	public function getExploitations(){
		$respParams = $this->sendAuthRequest("getexploitations");
		
		$exploitations = array();
		for ($i = 0; isset($respParams["planet$i"]); $i++){
			$exploitation = new Exploitation();
			$exploitation->setPlanetName($respParams["planet$i"]);
			$exploitation->setPlanetId($respParams["planetid$i"]);
			$exploitation->setNumExploits($respParams["nbexp$i"]);
			$exploitation->setNumInPipe($respParams["inpipe$i"]);
			$exploitation->setNumToBeDemolished($respParams["tobedem$i"]);
			$exploitation->setNumOnSale($respParams["nbonsale$i"]);
			$exploitation->setSellPrice($respParams["sellprice$i"]);
			$exploitation->setRentability($respParams["rentability$i"]);
			$exploitations[] = $exploitation;
		}
		return $exploitations;
	}
	
	/**
	 * Gets info on a specific planet or all of your planets.&nbsp;
	 * Includes general, trading, and infiltration info.
	 * @param $planetName (optional) the name of a specific planet to retrieve info on. This can be a planet you own or a planet that you have fleets/armies stationed on. If this is left out, it will return info on all of your planets.
	 * @throws Exception if a planet with the given name does not exist or it is not under the player's control or there was a problem sending the request
	 * @return PlanetInfo|array(PlanetInfo) a single object if a planet name was specified, an array if not
	 */
	public function getPlanetInfo($planetName = null){
		$planetInfos = array();

		$params = array();
		$params["planet"] = ($planetName == null) ? "*" : $planetName;
		
		//get general info
		$params["data"] = "general";
		$respParams = $this->sendAuthRequest("getplanetinfo", $params);
		
		//if the planet is foreign, then indexes aren't appended to the parameters and different info is returned
		if (isset($respParams["planet"])){
			$planetInfo = new PlanetInfo();
			$planetInfo->setForeign(true);
			$planetInfo->setName($respParams["planet"]);
			$planetInfo->setStasis($respParams["stasis"]);
			$planetInfo->setBattle($respParams["battle"]);
			$planetInfo->setBlockaded($respParams["blockade"]);
			$planetInfo->setVacation($respParams["vacation"]);
			$planetInfo->setHypergate($respParams["hypergate"]);
			$planetInfo->setNeutral(@$respParams["isneutral"]); //only appears if the planet is neutral?
			$planetInfo->setDefBonus(@$respParams["defbonus"]); //only appears if there's a battle?
			return $planetInfo;
		}
		
		for ($i = 0; isset($respParams["planet$i"]); $i++){
			$planetInfo = new PlanetInfo();
			$planetInfo->setForeign(false);
			$planetInfo->setName($respParams["planet$i"]);
			$planetInfo->setX($respParams["x$i"]);
			$planetInfo->setY($respParams["y$i"]);
			$planetInfo->setSize($respParams["size$i"]);
			$planetInfo->setOrbit($respParams["orbit$i"]);
			$planetInfo->setGovernment($respParams["gov$i"]);
			$planetInfo->setGovernmentCooldown($respParams["govd$i"]);
			$planetInfo->setProdType($respParams["ptype$i"]);
			$planetInfo->setTax($respParams["tax$i"]);
			$planetInfo->setNumExploits($respParams["exploits$i"]);
			$planetInfo->setNumExploitsInPipe($respParams["expinpipe$i"]);
			$planetInfo->setActivity($respParams["activity$i"]);
			$planetInfo->setPopulation($respParams["pop$i"]);
			$planetInfo->setRace($respParams["race$i"]);
			$planetInfo->setNrj($respParams["nrj$i"]);
			$planetInfo->setNrjMax($respParams["nrjmax$i"]);
			$planetInfo->setPurifying($respParams["purif$i"]);
			$planetInfo->setParanoidMode($respParams["parano$i"]);
			$planetInfo->setBlockaded($respParams["block$i"]);
			$planetInfo->setBlackHole($respParams["bhole$i"]);
			$planetInfo->setStasis($respParams["stasis$i"]);
			$planetInfo->setNexusType($respParams["nexus$i"]);
			$planetInfo->setNexusBuildTimeLeft(@$respParams["nxbuild$i"]); //left out the planet does not have a nexus
			$planetInfo->setNexusBuildTimeTotal(@$respParams["nxbtot$i"]); //left out the planet does not have a nexus
			$planetInfo->setEcomark($respParams["ecomark$i"]);
			$planetInfo->setId($respParams["planetid$i"]);
			$planetInfo->setPublicTag($respParams["publictag$i"]);
			$planetInfo->setNumFactories($respParams["factories$i"]);
			$planetInfo->setCivLevel($respParams["civlevel$i"]);
			$planetInfo->setDefBonus($respParams["defbonus$i"]);
			$planetInfos[] = $planetInfo;
		}

		//get trading info
		$params["data"] = "trading";
		$respParams = $this->sendAuthRequest("getplanetinfo", $params);
		for ($i = 0; isset($respParams["planet$i"]) && $i < count($planetInfos); $i++){
			$planetInfo = $planetInfos[$i];
			
			$trades = array();
			//note: parse_str() replaces dots in parameter names with underscores (example: "tid0.0" becomes "tid0_0")
			for ($j = 0; isset($respParams["tid{$i}_$j"]); $j++){
				$trade = new Trade();
				$trade->setId($respParams["tid{$i}_$j"]);
				$trade->setPlanetName($respParams["toplanet{$i}_$j"]);
				$trade->setPlanetTag($respParams["tag{$i}_$j"]); //blank if the planet does not have a public tag
				$trade->setPlanetDistance($respParams["dist{$i}_$j"]);
				$trade->setPlanetX($respParams["x{$i}_$j"]);
				$trade->setPlanetY($respParams["y{$i}_$j"]);
				$trade->setPlanetRace($respParams["race{$i}_$j"]);
				$trade->setPlanetActivity($respParams["activity{$i}_$j"]);
				$trade->setIncome($respParams["incomeBT{$i}_$j"]);
				$trade->setCapacity($respParams["capacity{$i}_$j"]);
				$trade->setTransportType($respParams["transtype{$i}_$j"]);
				$trade->setPending($respParams["ispending{$i}_$j"]);
				$trade->setAccepted($respParams["isaccepted{$i}_$j"]);
				$trade->setRequestor($respParams["isrequestor{$i}_$j"]);
				$trade->setUpkeep($respParams["upkeep{$i}_$j"]);
				$trade->setProdType($respParams["prodtype{$i}_$j"]);
				$trade->setPlanetBlockaded($respParams["isblockade{$i}_$j"]);
				$trades[] = $trade;
			}
			$planetInfo->setTrades($trades);
		}
		
		//get infiltration info
		$params["data"] = "infiltr";
		$respParams = $this->sendAuthRequest("getplanetinfo", $params);
		for ($i = 0; isset($respParams["planet$i"]) && $i < count($planetInfos); $i++){
			$planetInfo = $planetInfos[$i];
			
			$infiltrations = array();
			//note: parse_str() replaces dots in parameter names with underscores (example: "tid0.0" becomes "tid0_0")
			for ($j = 0; isset($respParams["infid{$i}_$j"]); $j++){
				$infil = new Infiltration();
				$infil->setId($respParams["infid{$i}_$j"]);
				$infil->setPlanetName($respParams["planetname{$i}_$j"]);
				$infil->setPlanetTag(@$respParams["planettag{$i}_$j"]); //not included if planet does not have a public tag
				$infil->setPlanetX($respParams["x{$i}_$j"]);
				$infil->setPlanetY($respParams["y{$i}_$j"]);
				$infil->setLevel($respParams["level{$i}_$j"]);
				$infil->setSecurity($respParams["security{$i}_$j"]);
				$infil->setGrowing($respParams["growing{$i}_$j"]);
				$infil->setCaptive($respParams["captive{$i}_$j"]);
				$infiltrations[] = $infil;
			}
			$planetInfo->setInfiltrations($infiltrations);
		}
		
		if ($planetName != null){
			return $planetInfos[0];
		}
		return $planetInfos;
	}
	
	/**
	 * Gets info on your fleets and armies that are stationed on a planet.&nbsp;
	 * Does not include fleets that are in transit (see getMovingFleets()).
	 * @param $planetName (optional) the name of a specific planet to retrieve fleet info on.
	 * This can be a planet you own or a planet that you have fleets/armies stationed on.
	 * If this is left out, it will return info on all of your planets and all planets that you have fleets/armies on.
	 * @throws Exception if there was a problem making the request
	 * @return array(FleetsInfo) an array of objects where each object represents planet that has 0 or more fleets
	 */
	public function getFleetsInfo($planetName = null){
		$fleetsInfos = array();
		
		$params = array();
		$params["planet"] = ($planetName == null) ? "*" : $planetName;
		
		//own planets
		$params["data"] = "own_planets";
		$resp = $this->sendAuthRequest("getfleetsinfo", $params);
		for ($i = 0; isset($resp["planet$i"]); $i++){
			$fleetsInfo = new FleetsInfo();
			$fleetsInfo->setOwnPlanet(true);
			$fleetsInfo->setPlanetName($resp["planet$i"]);
			$fleetsInfo->setStasis($resp["stasis$i"]);
			$fleetsInfo->setVacation($resp["vacation$i"]);
			$fleetsInfo->setNrj($resp["nrj$i"]);
			$fleetsInfo->setNrjMax($resp["nrjmax$i"]);
			$fleets = array();
			for ($j = 0; isset($resp["fleetid{$i}_$j"]); $j++){
				$fleet = new Fleet();
				$fleet->setId($resp["fleetid{$i}_$j"]);
				
				//if a fleet is never named, "null" will be returned
				//if a fleet is named, but its name is later removed, an empty string will be returned
				//this is true despite the fact that "[No name]" is displayed on the website in both cases 
				$name = $resp["fname{$i}_$j"];
				if ($name == "null"){
					$name = "";
				}
				
				$fleet->setName($name);
				$fleet->setSellPrice($resp["sellprice{$i}_$j"]);
				$fleet->setRace($resp["frace{$i}_$j"]);
				$fleet->setOwner($resp["owner{$i}_$j"]);
				$fleet->setDefending($resp["defend{$i}_$j"]);
				$fleet->setCamouflaged($resp["camouf{$i}_$j"]);
				$fleet->setBombing($resp["bombing{$i}_$j"]);
				$fleet->setAutoDropping($resp["autodrop{$i}_$j"]);
				$fleet->setDelay($resp["delay{$i}_$j"]);
				
				//note: army groups and fleet groups are separate, so if there are any ground armies in a fleet, there won't be any ships, and vice versa.
				
				$fleet->setGroundArmies(@$resp["garmies{$i}_$j"]);
				
				$fleet->setScouts(@$resp["scou{$i}_$j"]);
				$fleet->setCruisers(@$resp["crui{$i}_$j"]);
				$fleet->setBombers(@$resp["bomb{$i}_$j"]);
				$fleet->setDestroyers(@$resp["dest{$i}_$j"]);
				$fleet->setCarriedArmies(@$resp["carmies{$i}_$j"]);
				
				$fleets[] = $fleet;
			}
			$fleetsInfo->setFleets($fleets);
			$fleetsInfos[] = $fleetsInfo;
		}
		
		//foreign planets
		$params["data"] = "foreign_planets";
		$resp = $this->sendAuthRequest("getfleetsinfo", $params);
		for ($i = 0; isset($resp["planet$i"]); $i++){
			$fleetsInfo = new FleetsInfo();
			$fleetsInfo->setOwnPlanet(false);
			$fleetsInfo->setPlanetName($resp["planet$i"]);
			$fleetsInfo->setStasis($resp["stasis$i"]);
			$fleetsInfo->setVacation($resp["vacation$i"]);
			$fleets = array();
			for ($j = 0; isset($resp["fleetid{$i}_$j"]); $j++){
				$fleet = new Fleet();
				$fleet->setId($resp["fleetid{$i}_$j"]);
				$fleet->setName(@$resp["fname{$i}_$j"]);
				$fleet->setSellPrice($resp["sellprice{$i}_$j"]);
				$fleet->setRace($resp["frace{$i}_$j"]);
				$fleet->setOwner($resp["owner{$i}_$j"]);
				$fleet->setDefending($resp["defend{$i}_$j"]);
				$fleet->setCamouflaged($resp["camouf{$i}_$j"]);
				$fleet->setBombing(@$resp["bombing{$i}_$j"]);
				$fleet->setAutoDropping(@$resp["autodrop{$i}_$j"]);
				$fleet->setDelay(@$resp["delay{$i}_$j"]);
				
				//note: army groups and fleet groups are separate, so if there are any ground armies in a fleet, there won't be any ships, and vice versa.
				
				$fleet->setGroundArmies(@$resp["garmies{$i}_$j"]);
				
				$fleet->setScouts(@$resp["scou{$i}_$j"]);
				$fleet->setCruisers(@$resp["crui{$i}_$j"]);
				$fleet->setBombers(@$resp["bomb{$i}_$j"]);
				$fleet->setDestroyers(@$resp["dest{$i}_$j"]);
				$fleet->setCarriedArmies(@$resp["carmies{$i}_$j"]);
				
				$fleets[] = $fleet;
			}
			$fleetsInfo->setFleets($fleets);
			$fleetsInfos[] = $fleetsInfo;
		}
		
		return $fleetsInfos;
	}
	/**
	 * Gets a list of all planets that belong to an alliance.&nbsp;
	 * <br>A max of 50 planets are returned in each response.&nbsp; Use the $start parameter to specify what row it should start on.
	 * @param string $tag the alliance tag (without brackets, case-insensitive)
	 * @param integer $start (optional) the row in the list it should start on (defaults to the beginning of the list, first row is "0")
	 * @throws Exception if there was a problem making the request
	 * @return array(AlliancePlanet) the alliance planets
	 */
	public function getAlliancePlanets($tag, $start = 0){
		$alliancePlanets = array();
		
		$params = array(
			"tag"=>$tag,
			"start"=>$start
		);
		$resp = $this->sendAuthRequest("getallianceplanets", $params);
		$num = $resp["nb"];
		for ($i = 0; $i < $num; $i++){
			$alliancePlanet = new AlliancePlanet();
			$alliancePlanet->setName($resp["planet$i"]);
			$alliancePlanet->setOwner($resp["owner$i"]);
			$alliancePlanet->setX($resp["x$i"]);
			$alliancePlanet->setY($resp["y$i"]);
			$alliancePlanet->setProdType($resp["prodtype$i"]);
			$alliancePlanet->setRace($resp["race$i"]);
			$alliancePlanet->setActivity($resp["activity$i"]);
			$alliancePlanet->setPublicTag(@$resp["publictag$i"]); //not included if planet does not have a public tag
			$alliancePlanet->setPublicTagId(@$resp["ptagid$i"]); //not included if planet does not have a public tag
			$alliancePlanets[] = $alliancePlanet;
		}
		return $alliancePlanets;
	}
	
	/**
	 * Determines whether the player has new messages or not.
	 * @throws Exception if there was a problem making the request
	 * @return IsMsg the response
	 */
	public function isMsg(){
		$resp = $this->sendAuthRequest("ismsg");
		
		$isMsg = new IsMsg();
		$isMsg->setMsg($resp["ismsg"]);
		$isMsg->setReport($resp["isreport"]);
		return $isMsg;
	}
	
	/**
	 * Determines whether the player has new messages or not.
	 * @throws Exception if there was a problem making the request
	 * @return IsMsgInfo the response
	 */
	public function isMsgInfo(){
		$resp = $this->sendAuthRequest("ismsginfo");
		
		$isMsgInfo = new IsMsgInfo();
		$isMsgInfo->setMsg($resp["ismsg"]);
		$isMsgInfo->setPlanet($resp["isplanet"]);
		$isMsgInfo->setReport($resp["isreport"]);
		$isMsgInfo->setMilitary($resp["ismilit"]);
		$isMsgInfo->setTrading($resp["istrading"]);
		$isMsgInfo->setInfiltration($resp["isinfiltr"]);
		$isMsgInfo->setControl($resp["iscontrol"]);
		return $isMsgInfo;
	}
	
	/**
	 * Gets all new player and planet messages.&nbsp;
	 * Note that these messages will be marked as "read" after this method is called.
	 * @throws Exception if there was a problem making the request
	 * @return array(Message) all new messages
	 */
	public function getNewMessages(){
		$messages = array();
		
		//this is confusing, see docs/example-responses.txt
		$resp = $this->sendAuthRequest("getnewmsg");
		$num = $resp["nbmsg"];
		if ($num > 0){
			$cur = 0;
			$curRecipient = null;
			$nextIndex = @$resp["planetstart$cur"];
			if ($nextIndex === null){
				$nextIndex = -1;
			}
			for ($i = 0; $i < $num; $i++){
				if ($i == $nextIndex){
					$curRecipient = $resp["planet$cur"];
					$cur++;
					$nextIndex = @$resp["planetstart$cur"];
				}
				
				$message = new Message();
				$message->setDate(strtotime($resp["date$i"]));
				$message->setType($resp["type$i"]);
				$message->setMessage($resp["msg$i"]);
				$message->setSubject($resp["subj$i"]);
				$sender = $resp["sender$i"];
				if ($sender == "null"){
					$sender = null;
				}
				$message->setSender($sender);
				$message->setRecipient($curRecipient);
				$messages[] = $message;
			}
		}
		return $messages;
	}
	
	/**
	 * Gets old player messages sorted by date descending (newest messgaes first).
	 * @param integer $start the message to start on ("0" for the most recent message)
	 * @param integer $max the max number of messages to return
	 * @throws Exception if there was a problem making the request
	 * @return array(Message) the messages
	 */
	public function getOldPlayerMessages($start, $max){
		$messages = array();
		
		$params = array(
			"startmsg"=>$start,
			"maxmsg"=>$max
		);
		$resp = $this->sendAuthRequest("getoldpersomsg", $params);
		$num = $resp["nbmsg"];
		for ($i = 0; $i < $num; $i++){
			$message = new Message();
			$message->setDate(strtotime($resp["date$i"]));
			$message->setType(Message::TYPE_PERSONAL);
			$message->setMessage($resp["msg$i"]);
			$message->setSubject($resp["subj$i"]);
			$sender = $resp["sender$i"];
			if ($sender == "null"){
				$sender = null;
			}
			$message->setSender($sender);
			$messages[] = $message;
		}
		return $messages;
	}
	
	/**
	 * Gets old planet messages.
	 * @param integer $start the message to start on ("0" for the most recent message)
	 * @param integer $max the max number of messages to return
	 * @param string $planetName (optional) the planet you want to retrieve the messages of or null to get messages from all planets
	 * @throws Exception if there was a problem making the request
	 * @return array(Message) the messages
	 */
	public function getOldPlanetMessages($start, $max, $planetName = null){
		$messages = array();

		$params = array(
			"startmsg"=>$start,
			"maxmsg"=>$max,
			"planet"=>($planetName == null) ? "*" : $planetName
		);
		$resp = $this->sendAuthRequest("getoldplanetmsg", $params);
		$num = $resp["nbmsg"];
		for ($i = 0; $i < $num; $i++){
			$message = new Message();
			$message->setDate(strtotime($resp["date$i"]));
			$message->setType($resp["type$i"]);
			$message->setMessage($resp["msg$i"]);
			$message->setSubject($resp["subj$i"]);
			$sender = $resp["sender$i"];
			if ($sender == "null"){
				$sender = null;
			}
			$message->setSender($sender);
			$messages[] = $message;
		}
		return $messages;
	}
	
	/**
	 * Gets the version of HAPI.
	 * @throws Exception if there was a problem making the request
	 * @return string the version of HAPI
	 */
	public function getVersion(){
		$resp = $this->sendAuthRequest("version");
		return $resp["version"];
	}
	
	/**
	 * Logs you out of the current HAPI session.
	 * @throws Exception if there was a problem making the request
	 */
	public function logout(){
		$resp = $this->sendAuthRequest("logout");
		$status = $resp["status"];
		if ($status != "ok"){
			throw new \Exception("Logout failure.  Status code: $status");
		}
	}
	
	/**
	 * Gets info on a particular player.
	 * @param string $playerName (optional) the name of the player. If this is left out, then it will retrieve info on the authenticated player.
	 * @throws Exception if there was a problem making the request
	 * @return PlayerInfo the info on the player
	 */
	public function getPlayerInfo($playerName = null){
		$params = array();
		if ($playerName != null){
			$params["targetplayer"] = $playerName;
		}
		$resp = $this->sendAuthRequest("getplayerinfo", $params);
		
		$playerInfo = new PlayerInfo();
		$playerInfo->setName($resp["name"]);
		$playerInfo->setHypRank($resp["hyprank"]);
		$playerInfo->setRankinf($resp["rankinf"]);
		$playerInfo->setScoreinf($resp["scoreinf"]);
		if ($playerName == null){
			//these parameters only appear if you are asking for information about yourself
			$playerInfo->setCash($resp["cash"]);
			$playerInfo->setRankfin($resp["rankfin"]);
			$playerInfo->setScorefin($resp["scorefin"]);
			$playerInfo->setRankpow($resp["rankpow"]);
			$playerInfo->setScorepow($resp["scorepow"]);
			$playerInfo->setPlanets($resp["nbplanets"]);
			$playerInfo->setLastIncome($resp["lastincome"]);
		}
		return $playerInfo;
	}
	
	/**
	 * Sends a HAPI request.
	 * @param string $method the method to call
	 * @param array(string=>string) $params (optional) additional parameters to add to the request
	 * @param string $floodLockFile (optional) the *absolute* path to the lock file or null to not use flood protection.  The lock file is an empty file that must be writable by the web server process.
	 * @param boolean $rawResponse (optional) true to return the raw response, false to parse the response as a query string and return an assoc array (default is false)
	 * @throws Exception if there was a problem sending the request or an error response was returned
	 * @return array(string=>string)|string the response
	 */
	protected static function sendRequest($method, array $params = array(), $floodLockFile = null, $rawResponse = false){
		//build request URL
		$params["request"] = $method;
		if (self::$cacheDetection){
			$reqFailsafe = time();
			$params["failsafe"] = $reqFailsafe;
		}
		$url = self::URL . "?" . http_build_query($params);

		if ($floodLockFile != null){
			$fp = fopen($floodLockFile, "r");
			flock($fp, LOCK_EX);
			clearstatcache();
			$t = fileatime($floodLockFile);
			$diff = time() - $t;
			if ($diff >= 0 && $diff < self::SECONDS_PER_REQUEST){
				//pause if a request was made recently
				sleep(self::SECONDS_PER_REQUEST-$diff);
			}
		}
		
		//make the request
		$response = file_get_contents($url);
		
		if ($floodLockFile != null){
			//update the last-modified time and unlock
			touch($floodLockFile);
			flock($fp, LOCK_UN);
		}
		
		$failed = $response === false; //problem sending the request?
		
		if (self::$logFile != null){
			//log the request and response
			
			$m = ($method == null) ? "<no method name>" : $method;
			
			if ($failed){
				$r = "<request failed>";
			} else if ($rawResponse && strlen($response) > 200){
				$r = substr($response, 0, 200) . "...snipped";
			} else {
				$r = $response;
			}
			
			$msg = "HAPI request: $m\n  url: $url\n  response: $r\n";
			if (self::$logFile == 'php_error_log'){
				error_log($msg);
			} else {
				$now = date('Y-m-d H:i:s');
				$fp = fopen(self::$logFile, 'a');
				flock($fp, LOCK_EX);
				fwrite($fp, "$now: $msg");
				flock($fp, LOCK_UN);
				fclose($fp);
			}
		}
		
		//problem sending request?
		if ($failed){
			throw new \Exception("Problem sending the request.");
		}
		
		if ($rawResponse){
			//only return the raw response if it is not an error response
			$sub = substr($response, 0, 5);
			if ($sub != "error"){
				return $response;
			}
		}

		//URL-encode ampersands for parse_str()
		$response = str_replace("[:&:]", urlencode("&"), $response);
		
		//parse the query string into an assoc array
		parse_str($response, $respParams);
		
		//throw an exception if the response is from a cache
		if (self::$cacheDetection){
			$respFailsafe = @$respParams["failsafe"];
			if ($respFailsafe != $reqFailsafe){
				throw new \Exception("A different failsafe value was returned in the response.  Response has come from a cache and does not contain up-to-date information.");
			}
		}
		
		//check for errors in the response
		$error = @$respParams["error"];
		if ($error !== null){
			throw new \Exception($error);
		}
		
		return $respParams;
	}
	
	/**
	 * Sends a HAPI request including auth info.
	 * @param string $method the method to call
	 * @param array(string=>string) $params (optional) additional parameters to add to the request
	 * @throws Exception if there was a problem sending the request or an error response was returned
	 * @return array(string=>string) the response
	 */
	protected function sendAuthRequest($method, array $params = array()){
		//add auth parameters
		$params["gameid"] = $this->session->getGameId();
		$params["playerid"] = $this->session->getPlayerId();
		$params["authkey"] = $this->session->getAuthKey();
		
		//get the path to the lock file
		$lockFile = null;
		if ($this->floodLockDir != null){
			$lockFile = $this->floodLockDir . "/" . $this->session->getPlayerId();
			if (!file_exists($lockFile)){
				//create the lock file if it doesn't exist
				$success = touch($lockFile, time()-self::SECONDS_PER_REQUEST, time()-self::SECONDS_PER_REQUEST);
				if (!$success){
					throw new \Exception("Could not create lock file \"$lockFile\" for flood protection. Make sure it's parent directory is writable by PHP.");
				}
			}  
		}
		
		return self::sendRequest($method, $params, $lockFile);
	}
	
	/**
	 * Sets the file where all requests/responses will be logged (logging is disabled by default).
	 * @param string $logFile the *absolute* path to the log file, "php_error_log" to write to the PHP error log, or null to disable logging
	 */
	public static function setLogFile($logFile){
		self::$logFile = $logFile;
	}
	
	/**
	 * Enables or disables flood protection (disabled by default).&nbsp;
	 * This is to prevent the library from sending too many requests and breaking HAPI usage rules (max of 3 requests/second, 30 requests/minute).
	 * @param string $lockDir the *absolute* path to the directory where the lock files will be stored (one file per user) or null to disable flood protection.  The directory must be writable by the web server process.
	 * @throws Exception if the lock directory isn't a directory, isn't writable, or can't be created
	 */
	public function setFloodProtection($lockDir){
		if ($lockDir != null){
			if (file_exists($lockDir)){
				//make sure it's a directory
				if (!is_dir($lockDir)){
					throw new \Exception("Cannot enable flood protection.  The lock directory is not a directory: \"$lockDir\"");
				}
				
				//make sure the lock direcotry is writable
				if (!is_writable($lockDir)){
					throw new \Exception("Cannot enable flood protection. The file permissions of the lock directory do not allow PHP to write to it: \"$lockDir\"");
				}
			} else {
				//create the lock directory if it doesn't exist
				$success = mkdir($lockDir, 0774, true);
				if (!$success){
					throw new \Exception("Could not create lock directory \"$lockDir\". Check to make sure that it's an absolute path and that it's writable by PHP.");
				}
			}
		}
		$this->floodLockDir = $lockDir;
	}
	
	/**
	 * Enables or disables cache detection (enabled by default).&nbsp;
	 * Use this to check if the responses are cached (and do not contain up-to-date information).&nbsp;
	 * An exception will be thrown if it is found that a response comes from a cache.
	 * @param boolean $enable true to enable, false to disable
	 */
	public static function setCacheDetection($enable){
		self::$cacheDetection = $enable;
	}
}