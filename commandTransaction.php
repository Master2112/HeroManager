<?php
header("Access-Control-Allow-Origin: *");
include "database.php";
include "update.php";

$data = file_get_contents("php://input");
$data = json_decode($data);

if(isset($data->id))
{
	$hero = $heroes->GetRow($data->id);
	
	if($hero != null)
	{
		$location = $locations->GetRow($hero->locationId);
		$user = $users->GetRow($hero->ownerId);
		
		if(isset($data->action))
		{
			$legal = false;
			$allowedActions = json_decode($location->allowedActions);
			
			for($i = 0; $i < count($allowedActions); $i++)
			{
				if($data->action == $allowedActions[$i])
				{
					$legal = true;
				}
			}
			
			if($data->action <> "Buying" && $data->action <> "Selling")
			{
				$legal = false;
			}	
			
			if($legal)
			{
				if(isset($data->itemId))
				{
					if(isset($data->key))
					{
						if($data->key == $user->password)
						{
							$hero->inventory = json_decode($hero->inventory);//open
							$hero->log = json_decode($hero->log);
							$hero->stats = json_decode($hero->stats);
							
							$today = new DateTime();
							$hero->todayDate = $today->format("Y-m-d");
							
							$success = false;
							
							if($data->action == "Buying")
							{
								if(CanBuy($hero, $data->itemId))
								{
									$success = true;
									$hero = BuyItem($hero, $data->itemId);
								}
								else
								{
									echo '{"error":"Could not buy item."}';
								}
							}
							
							
							if($data->action == "Selling")
							{
								if(CanSell($hero, $data->itemId))
								{
									$success = true;
									$hero = SellItem($hero, $data->itemId);
								}
								else
								{
									echo '{"error":"Could not sell item."}';
								}
							}
							
							if($success)
							{
								$hero->inventory = json_encode($hero->inventory); //close
								$hero->log = json_encode($hero->log);
								$hero->stats = json_encode($hero->stats);
								
								$heroes->Edit($hero->id, [$hero->ownerId, $hero->name, $hero->stats, $hero->inventory, $hero->currentHealth, $hero->maxHealth, $hero->locationId, $hero->lastUpdate, $hero->alive, $hero->currentAction, $hero->money, $hero->log, $hero->level, $hero->levelProgress]);
								echo '{"success":"Transaction applied successfully."}';
							}
						}
						else
						{
							echo '{"error":"Invalid key given for this hero\'s user."}';
						}
					}
					else
					{
						echo '{"error":"No key given"}';
					}
				}
				else
				{
					echo '{"error":"No item ID given."}';
				}
			}
			else
			{
				echo '{"error":"Invalid action for hero\'s location."}';
			}
		}
		else
		{
			echo '{"error":"No action given."}';
		}
	}
	else
	{
		echo '{"error":"No hero found with this id."}';
	}
	
}
else
{
	echo '{"error":"No hero id given."}';
}

function CanBuy($hero, $itemId)
{
	include "database.php";
	$shop = json_decode($locations->GetRow($hero->locationId)->shop);
	
	for($h = 0; $h < count($shop); $h++)
	{
		if($shop[$h]->id == $itemId)
		{
			$price = $shop[$h]->value;
			$totalWeight = 0;
			
			for($i = 0; $i < count($hero->inventory); $i++)
				$totalWeight += $hero->inventory[$i]->weight;
			
			if($hero->money >= $price && GetMaxWeight($hero) >= $totalWeight + $shop[$h]->weight)
			{
				return true;
			}
		}
	}
	
	return false;
}

function CanSell($hero, $itemId)
{
	for($h = 0; $h < count($hero->inventory); $h++)
	{
		if($hero->inventory[$h]->id == $itemId)
		{
			return true;
		}
	}
	
	return false;
}

function BuyItem($hero, $itemId)
{
	include "database.php";

	$shop = json_decode($locations->GetRow($hero->locationId)->shop);
	
	for($h = 0; $h < count($shop); $h++)
	{
		if($shop[$h]->id == $itemId)
		{
			$price = $shop[$h]->value;
			
			$totalWeight = 0;

			for($i = 0; $i < count($hero->inventory); $i++)
				$totalWeight += $hero->inventory[$i]->weight;
			
			if($hero->money >= $price && GetMaxWeight($hero) >= $totalWeight + $shop[$h]->weight)
			{
				$hero->money -= $price;
				array_push($hero->inventory, $shop[$h]);
				$hero = WriteHeroLog($hero, "I bought a new " . $shop[$h]->name . " for " . $price . ".");
			}
		}
	}
	
	return $hero;
}

function SellItem($hero, $itemId)
{
	for($h = 0; $h < count($hero->inventory); $h++)
	{
		if($hero->inventory[$h]->id == $itemId)
		{
			$price = $hero->inventory[$h]->value * 0.9;
			$hero->money += $price;
			$hero = WriteHeroLog($hero, "I sold my " . $hero->inventory[$h]->name . " for " . $price . ".");
			array_splice($hero->inventory, $h, 1);
		}
	}
	
	return $hero;
}