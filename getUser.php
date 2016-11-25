<?php 
header("Access-Control-Allow-Origin: *");

include_once "database.php";
include_once "update.php";
include_once "XMLMaker.php";

$type = "json";

if(isset($_SERVER['HTTP_ACCEPT']))
{
	if($_SERVER['HTTP_ACCEPT'] == "application/xml")
		$type="xml";
	else if($_SERVER['HTTP_ACCEPT'] == "application/json")
		$type="json";
	else if($_SERVER['HTTP_ACCEPT'] == "xml")
		$type = "xml";
	else if($_SERVER['HTTP_ACCEPT'] == "json")
		$type = "json";
	else
	{	
		if(!isset($_GET["type"]))
		{
			$type = "json";
			/*header("http/1.1 406");
			die();*/
		}
		else
		{
			$type = $_GET["type"];
		}
	}
}

if(isset($_GET["id"]) && $_GET["id"] <> '0')
{
	$user = FixUserForPublic($users->GetRow($_GET["id"]));
	
	if($type == "xml")
	{
		header("Content-Type:application/xml");
		echo '<?xml version="1.0" encoding="utf-8"?>';
		
		echo ToXMLObject("user", $user);
	}
	else
	{
		header("Content-Type:application/json");
		echo json_encode($user);
	}
}
else
{
	$usersFixed = [];
	
	$all = $users->All();
	
	for($i = 0; $i < count($all); $i++)
	{
		array_push($usersFixed, FixUserForPublic($all[$i]));
	}
	
	$collectionObj = new CollectionObject("http://timfalken.com/heromanager/users", $usersFixed, [], "id");
	$collectionObj->setStart(0);
	$collectionObj->setLimit(100);
	
	if(isset($_GET["start"]))
		$collectionObj->setStart($_GET["start"]);
		
	if(isset($_GET["limit"]))
		$collectionObj->setLimit($_GET["limit"]);
	
	if($type == "xml")
	{
		header("Content-Type:application/xml");
		echo '<?xml version="1.0" encoding="utf-8"?>';
		
		if(!isset($_GET['raw']) || $_GET['raw'] == 'false')
			echo $collectionObj->ConvertToXML();
		else
			echo ToXMLObject('users', $usersFixed);
	}
	else
	{
		header("Content-Type:application/json");
		
		if(!isset($_GET['raw']) || $_GET['raw'] == 'false')
			echo $collectionObj->ConvertToJSON();
		else
			echo json_encode($usersFixed);
	}
}

function FixUserForPublic($userRaw)
{
	include "database.php";
	
	$user = new stdClass();
	
	$user->id = $userRaw->id;
	$user->name = $userRaw->userName;
	$user->email = $userRaw->email;
	$user->level = $userRaw->level;
	
	$userHeroes = $heroes->Where("`ownerId`='" . $user->id . "'");
	$displayHeroes = [];
	$deadHeroes = [];
	
	for($i = 0; $i < count($userHeroes); $i++)
	{
		$location = $locations->GetRow($userHeroes[$i]->locationId);
		$hero = new stdClass();
		$hero->id = $userHeroes[$i]->id;
		$hero->name = $userHeroes[$i]->name;
		
		$hero->health = $userHeroes[$i]->currentHealth;
		
		$hero->currentAction = $userHeroes[$i]->currentAction;
		$hero->location = $location->name;
		$hero->locationId = $location->id;
		$hero->inventory = json_decode($userHeroes[$i]->inventory);
		$hero->possibleActions = json_decode($location->allowedActions);
		
		if($location->northLocationId != 0)
			array_push($hero->possibleActions, "Moving North");
			
		if($location->eastLocationId != 0)
			array_push($hero->possibleActions, "Moving East");
			
		if($location->southLocationId != 0)
			array_push($hero->possibleActions, "Moving South");
			
		if($location->westLocationId != 0)
			array_push($hero->possibleActions, "Moving West");
		
		$hero->directions = new stdClass();
		
		if($location->northLocationId != 0)
			$hero->directions->north = $locations->GetRow($location->northLocationId)->name;
			
		if($location->eastLocationId != 0)
			$hero->directions->east = $locations->GetRow($location->eastLocationId)->name;
			
		if($location->southLocationId != 0)
			$hero->directions->south = $locations->GetRow($location->southLocationId)->name;
			
		if($location->westLocationId != 0)
			$hero->directions->west = $locations->GetRow($location->westLocationId)->name;
		
		$stats = json_decode($userHeroes[$i]->stats);
		
		if (!isset($stats->age))
		{
			$stats->age = 1;
		}

		$hero->age = $stats->age;
		
		$hero->link = "http://timfalken.com/heromanager/heroes/" . $userHeroes[$i]->id;
		
		if($userHeroes[$i]->alive == 1)
			array_push($displayHeroes, $hero);
		else
			array_push($deadHeroes, $hero);
	}
	
	$user->emptyHeroSlots = $user->level - count($displayHeroes);
	
	$user->heroes = $displayHeroes;
	$user->graveyard = $deadHeroes;
	return $user;
}
