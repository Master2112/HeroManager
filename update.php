<?php 

include_once "database.php";

function Idle($hero, $location)
{
	$canDrink = false;
	$ate = false;
	$needMoneyForFood = false;
	
	for($l = 0; $l < count($location->allowedActions); $l++)
	{
		if($location->allowedActions[$l] == "Buying")
			$canDrink = true;
	}
	
	if($hero->locationId != 7)
	{
		for($f = 0; $f < count($hero->inventory); $f++)
		{
			if($hero->inventory[$f]->category == "Food")
			{
				$ate = true;
			}
		}
		
		if(!$ate)
		{
			$canEat = false;

			for($l = 0; $l < count($location->allowedActions); $l++)
			{
				if($location->allowedActions[$l] == "Buying")
					$canEat = true;
			}
			
			if($hero->money >= 5 && $canEat)
			{	
				$ate = true;
				$needMoneyForFood = true;
			}
		}
	}
	
	$drinkAmt = 3;
	if($needMoneyForFood)
		$drinkAmt = 8;
	
	if($hero->money >= $drinkAmt && $canDrink && mt_rand(0, 100) > 40)
	{
		$hero->money -= 3;
		$hero = WriteHeroLog($hero, "I bought a drink for 5 coins.");
	}

	if($ate)
		$hero->currentHealth = min($hero->currentHealth + floor($hero->maxHealth / 4), $hero->maxHealth);

	return $hero;
}

function DoAction($hero)
{
	include "database.php";
	
	$location = $locations->GetRow($hero->locationId);
	$location->items = json_decode($location->items);
	$location->animals = json_decode($location->animals);
	$location->allowedActions = json_decode($location->allowedActions);
	
	if($hero->locationId == 7)
	{
		if(mt_rand(0, 100) > 80)
		{
			$hero->currentAction = "Idle";
			$hero = WriteHeroLog($hero, "I was released from " . $locations->GetRow($hero->locationId)->name . ".");
			$hero->locationId = 1;
		}
	}
	
	$hero->currentHealth = min($hero->currentHealth + floor($hero->maxHealth / 10), $hero->maxHealth);
	
	if(mt_rand(0, 100) < 30 && $location->sleepPrice == 0)
		$hero = Fight($hero, $location, 2);
		
	if($hero->currentHealth <= 0)
		return $hero; //rip
	
	if($hero->currentAction <> "Idle")
		$hero = WriteHeroLog($hero, "I started " . $hero->currentAction . ".");
	
	switch($hero->currentAction)
	{
		case "Idle":
			$hero = Idle($hero, $location);
			break;
		case "Sleeping":
			$locationPrice = $location->sleepPrice;
			
			if($hero->money >= $locationPrice)
			{
				$hero->money -= $locationPrice;
				
				if($hero->currentHealth < $hero->maxHealth)
				{
					if(mt_rand(0, 100) > 70)
					{
						if(!isset($hero->stats->constitution))
							$hero->stats->constitution = 1;
						
						$hero->stats->constitution++;
						$hero = WriteHeroLog($hero, "My overall health seems to have improved.");
					}
				}
				
				$ate = false;
				
				if($hero->locationId != 7)
				{
					for($f = 0; $f < count($hero->inventory); $f++)
					{
						if($hero->inventory[$f]->category == "Food")
						{
							$ate = true;
						}
					}
					
					if(!$ate)
					{
						$canEat = false;

						for($l = 0; $l < count($location->allowedActions); $l++)
						{
							if($location->allowedActions[$l] == "Buying")
								$canEat = true;
						}
						
						if($hero->money >= 5 && $canEat)
						{	
							$ate = true;
						}
					}
				}
			
				if($ate)
				{	
					if($locationPrice > 0)
						$hero->currentHealth = $hero->maxHealth;
					else
						$hero->currentHealth = min($hero->currentHealth + floor($hero->maxHealth / 3), $hero->maxHealth);
						
					$hero = WriteHeroLog($hero, "I relaxed for today, and feel a lot better.");
				}
				else
					$hero = WriteHeroLog($hero, "I was too hungry to sleep.");
			}
			else
			{
				$hero->currentAction = "Idle";
				
				$hero = WriteHeroLog($hero, "I could not afford to sleep here today.");
				
				$hero = Idle($hero, $location);
			}
		break;
		case "Pickpocketing":
			
			if(!isset($hero->stats->pickpocketing))
			{
				$hero->stats->pickpocketing = 1;
			}
					
			if(!isset($hero->stats->stealth))
			{
				$hero->stats->stealth = 1;
			}
			
			if(mt_rand(0, 100) > 70)
			{	
				$hero->stats->pickpocketing++;
				$hero = WriteHeroLog($hero, "I became better at pickpocketing.");
			}
			
			if(mt_rand(0, 100) > 90)
			{
				$hero->stats->stealth++;
					$hero = WriteHeroLog($hero, "I have become better at moving stealthily.");
			}
			
			if(mt_rand(0, 100) < 75 + min(($hero->stats->pickpocketing + $hero->stats->stealth) / 2, 23))
			{
				$amt = mt_rand(0, 50) * mt_rand(0, ceil($hero->stats->pickpocketing / 3));
				$hero->money += $amt;
				
				$hero = WriteHeroLog($hero, "I stole " . $amt . " coins.");
			}
			else
			{
				//if($hero->locationId == 1) //TimCity prison is default, but other cities might have their own prisons.
				
				$fine = $hero->money / 4;
				$hero->money -= $fine;
				$hero->locationId = 7;
				$hero->currentAction = "Idle";
				
				$hero = WriteHeroLog($hero, "I got caught stealing. I had to pay a " . $fine . " fine and have been sent to " . $locations->GetRow($hero->locationId)->name . ".");
			}
		break;
		case "Foraging":
			
			if(!isset($hero->stats->foraging))
				$hero->stats->foraging = 1;
			
			if(mt_rand(0, 100) > 80)
			{	
				$hero->stats->foraging++;
				$hero = WriteHeroLog($hero, "I became better at foraging.");
			}
			
			$amt = mt_rand(0, ceil($hero->stats->foraging / 3));
				
			for($i = 0; $i < $amt; $i++)
			{
				$item = $location->items[mt_rand(0, count($location->items) - 1)];
				$hero = WriteHeroLog($hero, "I found a nice " . $item->name . ".");
				$hero = AddItem($hero, MakeItem($item->name, $item->category, $item->type, $item->damage, $item->value, $item->weight, $item->description));
			}	
		break;
		case "Exercising":
			
			if(mt_rand(0, 100) > 50)
			{
				if(!isset($hero->stats->strength))
					$hero->stats->strength = 1;
				
				$hero->stats->strength++;
				$hero = WriteHeroLog($hero, "My overall strength seems to have improved.");
			}
			
			if(mt_rand(0, 100) > 50)
			{
				if(!isset($hero->stats->dexterity))
					$hero->stats->dexterity = 1;
				
				$hero->stats->dexterity++;
				$hero = WriteHeroLog($hero, "My hand-eye coordination seems to have improved.");
			}
		break;
		case "Woodcutting":
			$canDo = false;
			
			for($w = 0; $w < count($hero->inventory); $w++)
			{
				if($hero->inventory[$w]->category == "Tool" && $hero->inventory[$w]->type == "Woodcutting")
					$canDo = true;
			}
			if($canDo)
			{
				if(mt_rand(0, 100) > 80)
				{
					if(!isset($hero->stats->strength))
						$hero->stats->strength = 1;
					
					$hero->stats->strength++;
					$hero = WriteHeroLog($hero, "My overall strength seems to have improved.");
				}
				
				if(!isset($hero->stats->woodcutting))
					$hero->stats->woodcutting = 1;
				
				if(mt_rand(0, 100) > 70)
				{					
					$hero->stats->woodcutting++;
					$hero = WriteHeroLog($hero, "I became better at woodcutting.");
				}
				
				$amt = mt_rand(0, ceil($hero->stats->woodcutting / 2));
				$hero = WriteHeroLog($hero, "I cut down " . $amt . " trees.");
				
				for($i = 0; $i < $amt; $i++)
					$hero = AddItem($hero, MakeItem("Log", "Wood", "Log", 0, 2, 1.5, "Wooden log, from a tree."));
			}
			else
			{
				$hero->currentAction = "Idle";
				$hero = WriteHeroLog($hero, "I tried to cut down a tree, but this seemed impossible without proper equipment.");
			}
		break;
		case "Fishing":
			$canDo = false;
			
			for($w = 0; $w < count($hero->inventory); $w++)
			{
				if($hero->inventory[$w]->category == "Tool" && $hero->inventory[$w]->type == "Fishing")
					$canDo = true;
			}
			if($canDo)
			{
				if(mt_rand(0, 100) > 80)
				{
					if(!isset($hero->stats->dexterity))
						$hero->stats->dexterity = 1;
					
					$hero->stats->dexterity++;
					$hero = WriteHeroLog($hero, "My hand-eye coordination seems to have improved.");
				}
				
				if(!isset($hero->stats->fishing))
					$hero->stats->fishing = 1;
				
				if(mt_rand(0, 100) > 70)
				{					
					$hero->stats->fishing++;
					$hero = WriteHeroLog($hero, "I became better at fishing.");
				}
				
				$amt = mt_rand(0, ceil($hero->stats->fishing / 2));
				
				$hero = WriteHeroLog($hero, "I caught " . $amt . " fish.");
				
				for($i = 0; $i < $amt; $i++)
				{	
					$hero = AddItem($hero, MakeItem("Fish", "Food", "Fish", 0, round(mt_rand(1, ceil($hero->stats->fishing / 2)), 1), 0.5, "Fish!"));
				}
			}
			else
			{
				$hero->currentAction = "Idle";
				$hero = WriteHeroLog($hero, "I tried to fish today, but I didn't have the tools required.");
			}
		break;
		case "Moving North":
			$dest = $location->northLocationId;
			
			if($dest != 0)
				$hero->locationId = $dest;
				$hero = WriteHeroLog($hero, "I have arrived at " . $locations->GetRow($hero->locationId)->name . ".");
				
			$hero->currentAction = "Idle";
		break;
		case "Moving East":
			$dest = $location->eastLocationId;
			
			if($dest != 0)
				$hero->locationId = $dest;
				$hero = WriteHeroLog($hero, "I have arrived at " . $locations->GetRow($hero->locationId)->name . ".");
			
			$hero->currentAction = "Idle";
		break;
		case "Moving South":
			$dest = $location->southLocationId;
			
			if($dest != 0)
				$hero->locationId = $dest;
				$hero = WriteHeroLog($hero, "I have arrived at " . $locations->GetRow($hero->locationId)->name . ".");
			
			$hero->currentAction = "Idle";
		break;
		case "Moving West":
			$dest = $location->westLocationId;
			
			if($dest != 0)
				$hero->locationId = $dest;
				$hero = WriteHeroLog($hero, "I have arrived at " . $locations->GetRow($hero->locationId)->name . ".");
			
			$hero->currentAction = "Idle";
		break;
		case "Hunting":
			$hero = Fight($hero, $location, 5);
		break;
		case "Raiding":
			$hero = Fight($hero, $location, 5);
			if($hero->currentHealth <= 0)
				return $hero;
			
			if(!isset($hero->stats->raiding))
				$hero->stats->raiding = 1;
			
			if(mt_rand(0, 100) > 80)
			{	
				$hero->stats->raiding++;
				$hero = WriteHeroLog($hero, "I became better at raiding.");
			}
			
			$amt = mt_rand(0, ceil($hero->stats->raiding / 4));
				
			for($i = 0; $i < $amt; $i++)
			{
				$item = $location->items[mt_rand(0, count($location->items) - 1)];
				$hero = WriteHeroLog($hero, "I found a nice " . $item->name . ".");
				$hero = AddItem($hero, MakeItem($item->name, $item->category, $item->type, $item->damage, $item->value, $item->weight, $item->description));
				
				if(mt_rand(0, 100) > 25) 
					$hero = Fight($hero, $location, 2);
					
				if($hero->currentHealth <= 0)
					return $hero;
			}	
			
			$amt = mt_rand(0, 50) * mt_rand(1, ceil($hero->stats->raiding / 3));
				$hero->money += $amt;
				
			$hero = WriteHeroLog($hero, "The raid ended. I found " . $amt . " coins total.");
		break;
	}
	
	return $hero;
}

function Fight($hero, $location, $maxEnemies)
{
	$heroWeapon = GetBestWeaponForHero($hero);
	
	if(count($location->animals) == 0)
		return $hero;
	
	$didFight = false;
	
	for($i = 0; $i < $maxEnemies && $hero->currentHealth > 0; $i++)
	{
		if(mt_rand(0, 100) > 30)
		{
			$didFight = true;
			$animal = $location->animals[mt_rand(0, count($location->animals) - 1)];
			
			$hero = WriteHeroLog($hero, "I encountered a wild " . $animal->name . ".");
			
			$lostHp = 0;
			
			while($animal->health > 0 && $hero->currentHealth > 0)
			{
				if(50 + $hero->stats->fighting > mt_rand(0, 100))
					$animal->health -= GetWeaponDamage($hero, $heroWeapon);
				
				if($animal->health > 0)
				{
					$hero->currentHealth -= $animal->damage;
					$lostHp += $animal.damage;
				}				
				
				if($hero->currentHealth <= 0)
					return $hero; //rip, return to other stuff.
				
				if($hero->currentHealth < $hero->maxHealth / 2 && mt_rand(0, 100) < 50)
				{
					for($h = 0; $h < count($hero->inventory); $h++)
					{
						if($hero->inventory[$h]->category == "Potion" && $hero->inventory[$h]->type == "Healing")
						{
							$hero->currentHealth += $hero->maxHealth / 2;
							array_splice($hero->inventory, $h, 1);
							$hero = WriteHeroLog($hero, "I used a Health Potion in my fight against the " . $animal->name . ".");
						}
					}
				}
			}
			
			if($animal->health <= 0)
			{
				$hero = WriteHeroLog($hero, "I defeated the " . $animal->name . " using my " . $heroWeapon->name . ($lostHp > 0? ", but took " . $lostHp . " damage in the process." : "."));
				$hero = AddItem($hero, MakeItem($animal->drops->name, $animal->drops->category, $animal->drops->type, $animal->drops->damage, $animal->drops->value, $animal->drops->weight, $animal->drops->description));
			}
		}
	}
	
	if($hero->currentHealth > 0 && $didFight)
	{
		if($hero->currentAction == "Sleeping")
		{
			$hero->currentAction = "Idle";
			$hero = WriteHeroLog($hero, "I was not able to sleep.");
		}
	
		if(mt_rand(0, 100) > 80)
		{
			switch($heroWeapon->type)
			{
			case "Melee" :
				$hero->stats->fighting++;
				$hero = WriteHeroLog($hero, "I became better at fighting.");
				break;
			case "Ranged" : 
				$hero->stats->ranged++;
				$hero = WriteHeroLog($hero, "I became better at ranged combat.");
				break;
			case "Magic" : 
				$hero->stats->magic++;
				$hero = WriteHeroLog($hero, "I became better at magic.");
				break;
			}
		}
		
		if(mt_rand(0, 100) > 90)
		{
			if(mt_rand(0, 100) > 80)
			{
				switch($heroWeapon->type)
				{
				case "Melee" :
					if(!isset($hero->stats->strength))
						$hero->stats->strength = 1;
					
					$hero->stats->strength++;
					$hero = WriteHeroLog($hero, "My overall strength seems to have improved.");
					break;
				case "Ranged" : 
					if(!isset($hero->stats->dexterity))
						$hero->stats->dexterity = 1;
					
					$hero->stats->dexterity++;
				$hero = WriteHeroLog($hero, "My hand-eye coordination seems to have improved.");
					break;
				case "Magic" : 
					if(!isset($hero->stats->intelligence))
						$hero->stats->intelligence = 1;
					
					$hero->stats->intelligence++;
					$hero = WriteHeroLog($hero, "My overall intelligence seems to have improved.");
					break;
				}
			}
		}
	}
	
	if($hero->currentHealth < $hero->maxHealth / 3 && $hero->currentAction == "Hunting")
	{
		$hero = WriteHeroLog($hero, "I have taken too much damage, and stopped hunting for today.");
		$hero->currentAction = "Idle";
	}
	
	if($hero->currentHealth < $hero->maxHealth / 2 && $hero->currentAction == "Raiding")
	{
		$hero = WriteHeroLog($hero, "I have taken too much damage, and stopped raiding for today.");
		$hero->currentAction = "Idle";
	}
	
	return $hero;
}

function WriteHeroLog($hero, $message)
{
	include "database.php";
	$entry = new stdClass;
	
	$entry->date = $hero->todayDate;
	$entry->message = $message;
	$entry->location = $locations->GetRow($hero->locationId)->name;
	array_push($hero->log, $entry);
	
	if(count($hero->log) > 100)
	{
		array_splice($hero->log, 0, count($hero->log) - 100);
	}
	
	return $hero;
}

function GetBestWeaponForHero($hero)
{
	$heroWeapon = new stdClass();
	$heroWeapon->damage = 1;
	$heroWeapon->type = "Melee";
	
	for($w = 0; $w < count($hero->inventory); $w++)
	{
		if($hero->inventory[$w]->category == "Weapon" && GetWeaponDamage($hero, $hero->inventory[$w]) >= GetWeaponDamage($hero, $heroWeapon))
			$heroWeapon = $hero->inventory[$w];
	}
	
	return $heroWeapon;
}

function GetWeaponDamage($hero, $weapon)
{
	$extra = 0;
	
	switch($weapon->type)
	{
	case "Melee" : $extra = ceil($hero->stats->fighting / 3); break;
	case "Ranged" : $extra = ceil($hero->stats->ranged / 3); break;
	case "Magic" : $extra = ceil($hero->stats->arcana / 3); break;
	}
	
	return $weapon->damage + $extra;
}

function FixHeroInventoryIDs($hero)
{
	for($i2 = 0; $i2 < count($hero->inventory); $i2++)
	{
		$hero->inventory[$i2]->id = $i2;
	}
	
	return $hero;
}

function AddItem($hero, $item)
{
	$totalWeight = 0;
	
	$item->id = 0;
	
	$ok = false;
	
	while(!$ok)
	{
		$ok = true;
		$item->id++;
		
		for($i = 0; $i < count($hero->inventory); $i++)
		{
			if(isset($hero->inventory[$i]->id))
			{
				if($hero->inventory[$i]->id == $item->id && $hero->inventory[$i] !== $item)
					$ok = false;
			}
			else
			{
				$hero = FixHeroInventoryIDs($hero);
				$ok = false;
			}
		}
	}
	
	for($i = 0; $i < count($hero->inventory); $i++)
		$totalWeight += $hero->inventory[$i]->weight;
	
	if(GetMaxWeight($hero) >= $totalWeight + $item->weight)
		array_push($hero->inventory, $item);
	else
		$hero = WriteHeroLog($hero, "I could not carry the " . $item->name . " because I am already carrying too much, so I left it here.");
		
	return $hero;
}

$allHeroes = $heroes->All();

for($i = 0; $i < count($allHeroes); $i++)
{	
	if($allHeroes[$i]->alive)
	{
		$datetime1 = new DateTime();
		
		if(!isset($allHeroes[$i]->lastUpdate))
			$allHeroes[$i]->lastUpdate = $datetime1->format("Y-m-d");
		
		$today = new DateTime($allHeroes[$i]->lastUpdate);
		$datetime2 = new DateTime($allHeroes[$i]->lastUpdate);
		
		$allHeroes[$i]->stats = json_decode($allHeroes[$i]->stats);
		$allHeroes[$i]->inventory = json_decode($allHeroes[$i]->inventory);
		$allHeroes[$i]->log = json_decode($allHeroes[$i]->log);
		
		/*$ok = true;
	
		for($it = 0; $it < count($allHeroes[$i]->inventory); $it++)
		{
			if(!isset($allHeroes[$i]->inventory[$it]->id))
			{
				$ok = false;
			}
		}
		
		if(!$ok)*/
		
		$allHeroes[$i] = FixHeroInventoryIDs($allHeroes[$i]);
		
		$allHeroes[$i]->lastUpdate = $datetime1->format("Y-m-d");
		
		if($allHeroes[$i]->currentAction == "")
			$allHeroes[$i]->currentAction = "Idle";
		
		if(!isset($allHeroes[$i]->stats->constitution))
			$allHeroes[$i]->stats->constitution = 1;
		if(!isset($allHeroes[$i]->stats->fighting))
			$allHeroes[$i]->stats->fighting = 1;					
		if(!isset($allHeroes[$i]->stats->ranged))
			$allHeroes[$i]->stats->ranged = 1;
		if(!isset($allHeroes[$i]->stats->arcana))
			$allHeroes[$i]->stats->arcana = 1;
			
		$location = $locations->GetRow($allHeroes[$i]->locationId);
		$location->allowedActions = json_decode($location->allowedActions);
		
		$allHeroes[$i]->maxHealth = 18 + ($allHeroes[$i]->stats->constitution * 2);
		
		$difference = $datetime1->diff($datetime2)->days;
		
		for($d = 0; $d < $difference; $d++)
		{
			if($allHeroes[$i]->alive)
			{
				$today = $today->add(new DateInterval('P1D'));
				
				if(!isset($allHeroes[$i]->stats->age))
					$allHeroes[$i]->stats->age = 0;
					
				$allHeroes[$i]->stats->age++;
				
				$allHeroes[$i]->todayDate = $today->format("Y-m-d"); 
				$allHeroes[$i] = DoAction($allHeroes[$i]);
			
				$ate = false;
				if($allHeroes[$i]->currentHealth > 0)
				{
					if($allHeroes[$i]->locationId != 7)
					{
						for($f = 0; $f < count($allHeroes[$i]->inventory); $f++)
						{
							if($allHeroes[$i]->inventory[$f]->category == "Food" && !$ate)
							{
								$allHeroes[$i] = WriteHeroLog($allHeroes[$i], "I ate my " . $allHeroes[$i]->inventory[$f]->name);
								array_splice($allHeroes[$i]->inventory, $f, 1);
								$ate = true;
							}
						}
						
						if(!$ate)
						{
							$canEat = false;
				
							for($l = 0; $l < count($location->allowedActions); $l++)
							{
								if($location->allowedActions[$l] == "Buying")
								{
									$canEat = true;
								}
							}
							
							if($allHeroes[$i]->money >= 5 && $canEat)
							{
								$allHeroes[$i]->money -= 5;
								$allHeroes[$i] = WriteHeroLog($allHeroes[$i], "I bought some food for 5 coins.");
								$ate = true;
							}
						}
						
						if(!$ate)
						{
							$dmg = ceil($allHeroes[$i]->maxHealth / 6);
							$allHeroes[$i]->currentHealth -= $dmg;
							
							$allHeroes[$i] = WriteHeroLog($allHeroes[$i], "I am hungry, but I do not have food.");
						}
					}
					else
					{
						$allHeroes[$i] = WriteHeroLog($allHeroes[$i], "I ate prison food.");
					}
				}
				
				if($allHeroes[$i]->currentHealth <= 0)
				{
					WriteHeroLog($allHeroes[$i], "I have died.");
					$allHeroes[$i]->alive = false;
					$allHeroes[$i]->currentHealth = 0;
				}
			}
		}
		
		$newHist = [];
		
		for($l = 0; $l < count($allHeroes[$i]->log); $l++)
		{
			$logDate = new DateTime($allHeroes[$i]->log[$l]->date);
			
			$age = $logDate->diff($datetime1)->days;
			
			if($age <= 10)
				array_push($newHist, $allHeroes[$i]->log[$l]);
		}
		
		$allHeroes[$i]->log = $newHist;
		
		$allHeroes[$i]->stats = json_encode($allHeroes[$i]->stats);
		$allHeroes[$i]->inventory = json_encode($allHeroes[$i]->inventory);
		$allHeroes[$i]->log = json_encode($allHeroes[$i]->log);
		SaveHero($allHeroes[$i], $heroes);
	}
}

function SaveHero($hero, $db)
{
	$db->Edit($hero->id, [$hero->ownerId, $hero->name, $hero->stats, $hero->inventory, $hero->currentHealth, $hero->maxHealth, $hero->locationId, $hero->lastUpdate, $hero->alive, $hero->currentAction, $hero->money, $hero->log]);
}

function GetMaxWeight($hero)
{
	if(!isset($hero->stats->strength))
		$hero->stats->strength = 1;
		
	return min(10 + floor($hero->stats->strength * 0.2), 30);
}

function MakeItem($name, $category, $type, $damage, $value, $weight, $description)
{
	$result = new stdClass();
	$result->name = $name;
	$result->category = $category;
	$result->type = $type;
	$result->damage = $damage;
	$result->value = $value;
	$result->weight = $weight;
	$result->description = $description;
	
	return $result;
}