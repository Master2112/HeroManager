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
	$location = FixLocationForPublic($locations->GetRow($_GET["id"]));
	
	if($type == "xml")
	{
		header("Content-Type:application/xml");
		echo '<?xml version="1.0" encoding="utf-8"?>';
		
		echo ToXMLObject("location", $location);
	}
	else
	{
		header("Content-Type:application/json");
		echo json_encode($location);
	}
}
else
{
	$locationsFixed = [];
	
	$all = $locations->All();
	
	for($i = 0; $i < count($all); $i++)
	{
		array_push($locationsFixed, FixLocationForPublic($all[$i]));
	}
	
	$collectionObj = new CollectionObject("http://timfalken.com/heromanager/locations", $locationsFixed, [], "id");
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
			echo ToXMLObject('locations', $locationsFixed);
	}
	else
	{
		header("Content-Type:application/json");
		
		if(!isset($_GET['raw']) || $_GET['raw'] == 'false')
			echo $collectionObj->ConvertToJSON();
		else
			echo json_encode($locationsFixed);
	}
}

function FixLocationForPublic($locationRaw)
{
	include "database.php";
	
	$location = new stdClass();
	
	$location->id = $locationRaw->id;
	$location->name = $locationRaw->name;
	$location->description = $locationRaw->description;
	$location->possibleActions = json_decode($locationRaw->allowedActions);
	
	if($locationRaw->northLocationId != 0)
		array_push($location->possibleActions, "Moving North");
	
	if($locationRaw->eastLocationId != 0)
		array_push($location->possibleActions, "Moving East");
	
	if($locationRaw->southLocationId != 0)
		array_push($location->possibleActions, "Moving South");
	
	if($locationRaw->westLocationId != 0)
		array_push($location->possibleActions, "Moving West");
	
	for($i = 0; $i < count($location->possibleActions); $i++)
	{
		if($location->possibleActions[$i] == "Buying")
		{
			$location->shop = json_decode($locationRaw->shop);
		}
	}
	
	$userHeroes = $heroes->Where("`locationId`='" . $location->id . "'");
	$displayHeroes = [];
	
	for($i = 0; $i < count($userHeroes); $i++)
	{
		$hero = new stdClass();
		$hero->id = $userHeroes[$i]->id;
		
		$hero->name = $userHeroes[$i]->name;
		
		$hero->health = $userHeroes[$i]->currentHealth;
		
		$hero->currentAction = $userHeroes[$i]->currentAction;
		
		$stats = json_decode($userHeroes[$i]->stats);
		
		$hero->age = $stats->age;
		
		$hero->link = "http://timfalken.com/heromanager/heroes/" . $userHeroes[$i]->id;
		
		if($userHeroes[$i]->alive == 1)
			array_push($displayHeroes, $hero);
	}
	
	$location->heroes = $displayHeroes;
	
	$location->creatures = [];
	$animals = json_decode($locationRaw->animals);
	
	for($i = 0; $i < count($animals); $i++)
	{
		$creature = new stdClass();
		$creature->name = $animals[$i]->name;
		$creature->danger = ceil(($animals[$i]->health + $animals[$i]->damage) / 2);
		array_push($location->creatures, $creature);
	}
	
	$destinations = new stdClass();
	
	if($locationRaw->northLocationId != 0)
	{
		$loc = $locations->GetRow($locationRaw->northLocationId);
		$destinations->north = new stdClass();
		$destinations->north->id = $locationRaw->northLocationId;
		$destinations->north->name = $loc->name;
		$destinations->north->link = "http://timfalken.com/heromanager/locations/" . $loc->id;
	}
	if($locationRaw->eastLocationId != 0)
	{
		$loc = $locations->GetRow($locationRaw->eastLocationId);
		$destinations->east = new stdClass();
		$destinations->east->id = $locationRaw->eastLocationId;
		$destinations->east->name = $loc->name;
		$destinations->east->link = "http://timfalken.com/heromanager/locations/" . $loc->id;
	}	
	if($locationRaw->southLocationId != 0)
	{
		$loc = $locations->GetRow($locationRaw->southLocationId);
		$destinations->south = new stdClass();
		$destinations->south->id = $locationRaw->southLocationId;
		$destinations->south->name = $loc->name;
		$destinations->south->link = "http://timfalken.com/heromanager/locations/" . $loc->id;
	}	
	if($locationRaw->westLocationId != 0)
	{
		$loc = $locations->GetRow($locationRaw->westLocationId);
		$destinations->west = new stdClass();
		$destinations->west->id = $locationRaw->westLocationId;
		$destinations->west->name = $loc->name;
		$destinations->west->link = "http://timfalken.com/heromanager/locations/" . $loc->id;
	}
	
	$location->destinations = $destinations;
	
	return $location;
}
