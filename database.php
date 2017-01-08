<?php 

include_once "ApiCollection.php";

$dbHeroes = new DatabaseInfo("REDACTED", "REDACTED", "REDACTED", "REDACTED", "REDACTED");
$dbLocations = new DatabaseInfo("REDACTED", "REDACTED", "REDACTED", "REDACTED", "REDACTED");
$dbUsers = new DatabaseInfo("REDACTED", "REDACTED", "REDACTED", "REDACTED", "REDACTED");

$heroes = new ApiCollection($dbHeroes, new ParamsLayout(["ownerId", "name", "stats", "inventory", "currentHealth", "maxHealth", "locationId", "lastUpdate", "alive", "currentAction", "money", "log", "level", "levelProgress"]));
$locations = new ApiCollection($dbLocations, new ParamsLayout(["name", "northLocationId", "eastLocationId", "southLocationId", "westLocationId", "allowedActions", "description", "animals", "items", "sleepPrice", "shop"]));
$users = new ApiCollection($dbUsers, new ParamsLayout(["userName", "email", "password", "level"]));
