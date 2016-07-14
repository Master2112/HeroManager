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
			
			if($location->northLocationId != 0)
				array_push($allowedActions, "Moving North");
				
			if($location->eastLocationId != 0)
				array_push($allowedActions, "Moving East");
				
			if($location->southLocationId != 0)
				array_push($allowedActions, "Moving South");
				
			if($location->westLocationId != 0)
				array_push($allowedActions, "Moving West");
			
			for($i = 0; $i < count($allowedActions); $i++)
			{
				if($data->action == $allowedActions[$i])
					$legal = true;
			}
			
			if($data->action == "Buying" || $data->action == "Selling")
			{
				$legal = false;
			}	
			
			if($data->action == "Idle")
				$legal = true;
			
			if($legal)
			{
				if(isset($data->key))
				{
					if($data->key == $user->password)
					{
						$heroes->Edit($hero->id, [$hero->ownerId, $hero->name, $hero->stats, $hero->inventory, $hero->currentHealth, $hero->maxHealth, $hero->locationId, $hero->lastUpdate, $hero->alive, $data->action, $hero->money, $hero->log]);
						echo '{"success":"Action applied successfully."}';
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