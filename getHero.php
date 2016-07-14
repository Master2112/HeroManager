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
	$hero = FixHeroForPublic($heroes->GetRow($_GET["id"]));
	
	if($type == "xml")
	{
		header("Content-Type:application/xml");
		echo '<?xml version="1.0" encoding="utf-8"?>';
		
		echo ToXMLObject("hero", $hero);
	}
	else
	{
		header("Content-Type:application/json");
		echo json_encode($hero);
	}
}
else
{
	$heroesFixed = [];
	
	$all = $heroes->All();
	
	for($i = 0; $i < count($all); $i++)
	{
		array_push($heroesFixed, FixHeroForPublic($all[$i]));
	}
	
	$collectionObj = new CollectionObject("http://timfalken.com/heromanager/heroes", $heroesFixed, [], "id");
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
			echo ToXMLObject("heroes", $heroesFixed);
	}
	else
	{
		header("Content-Type:application/json");
		
		if(!isset($_GET['raw']) || $_GET['raw'] == 'false')
			echo $collectionObj->ConvertToJSON();
		else
			echo json_encode($heroesFixed);
	}
}

function GetWeightString($hero)
{
	include_once "update.php";
	$totalWeight = 0;
	
	for($i = 0; $i < count($hero->inventory); $i++)
		$totalWeight += $hero->inventory[$i]->weight;

	if(!isset($hero->stats->strength))
		$hero->stats->strength = 1;
		
	return $totalWeight . "/" . GetMaxWeight($hero);
}

function GetItemValue($hero)
{
	$totalVal = 0;
	
	for($i = 0; $i < count($hero->inventory); $i++)
		$totalVal += $hero->inventory[$i]->value;

		
	return $totalVal;
}

function FixHeroForPublic($hero)
{
	include "database.php";
	
	$hero->inventory = json_decode($hero->inventory);
	$hero->stats = json_decode($hero->stats);
	
	$hero->weightCarried = GetWeightString($hero);
	$hero->inventoryValue = GetItemValue($hero);
	
	$location = $locations->GetRow($hero->locationId);
	
	$hero->possibleActions = json_decode($location->allowedActions);
	$destinations = new stdClass();
	
	if($location->northLocationId != 0)
	{
		array_push($hero->possibleActions, "Moving North");
		$loc = $locations->GetRow($location->northLocationId);
		$destinations->north = new stdClass();
		$destinations->north->id = $location->northLocationId;
		$destinations->north->name = $loc->name;
		$destinations->north->link = "http://timfalken.com/heromanager/locations/" . $loc->id;
	}
	if($location->eastLocationId != 0)
	{
		array_push($hero->possibleActions, "Moving East");
		$loc = $locations->GetRow($location->eastLocationId);
		$destinations->east = new stdClass();
		$destinations->east->id = $location->eastLocationId;
		$destinations->east->name = $loc->name;
		$destinations->east->link = "http://timfalken.com/heromanager/locations/" . $loc->id;
	}	
	if($location->southLocationId != 0)
	{
		array_push($hero->possibleActions, "Moving South");
		$loc = $locations->GetRow($location->southLocationId);
		$destinations->south = new stdClass();
		$destinations->south->id = $location->southLocationId;
		$destinations->south->name = $loc->name;
		$destinations->south->link = "http://timfalken.com/heromanager/locations/" . $loc->id;
	}	
	if($location->westLocationId != 0)
	{
		array_push($hero->possibleActions, "Moving West");
		$loc = $locations->GetRow($location->westLocationId);
		$destinations->west = new stdClass();
		$destinations->west->id = $location->westLocationId;
		$destinations->west->name = $loc->name;
		$destinations->west->link = "http://timfalken.com/heromanager/locations/" . $loc->id;
	}
	$hero->locationId = new stdClass();
	$hero->locationId->id = $location->id;
	$hero->locationId->name = $location->name;
	$hero->locationId->link = "http://timfalken.com/heromanager/locations/" . $location->id;
	$hero->possibleDirections = $destinations;
	
	$hero->alive = $hero->alive == 1;
	
	$hero->log = json_decode($hero->log);
	
	return $hero;
}
