<?php
header("Access-Control-Allow-Origin: *");
include "database.php";

$data = file_get_contents("php://input");
$data = json_decode($data);

$valid = true;

if (!isset($data->ownerId))
    $valid = false;

if (!isset($data->name))
    $valid = false;

if (!isset($data->classId))
    $valid = false;

if (!isset($data->key))
    $valid = false;

if ($valid)
{
    $existingUser = $users->Where("`id`='" . $data->ownerId . "' AND `password`='" . $data->key . "'")[0];

    if ($existingUser == null)
    {
        echo '{"error":"Invalid authentication"}';

        die;
    }

    if ($valid)
    {
        $userHeroes = $heroes->Where("`ownerId`='" . $existingUser->id . "'");

        $emptyHeroSlots = $existingUser->level - count($userHeroes);

        if ($emptyHeroSlots <= 0)
        {
            echo '{"error":"No empty slots for new heroes!"}';

            $valid = false;
        }
        else
        {
            if (CreateHeroFromData($heroes, $data))
                echo '{"success":"Hero successfully created!"}';
        }
    }
    else
    {
        echo '{"error":"Invalid parameters"}';
    }
}
else
{
    echo '{"error":"Invalid parameters!"}';
}

function CreateHeroFromData($db, $data)
{
    $classes = file_get_contents("http://www.timfalken.com/heromanager/classes.json");
    $classes = json_decode($classes);
    
    $heroClass = null;

    for ($i = 0; $i < count($classes); $i++)
    {
        if ($classes[$i]->id == $data->classId)
        {
            $heroClass = $classes[$i];
            break;
        }
    }
    
    if ($heroClass == null)
    {
        echo '{"error":"Invalid classID"}';
        return false;
    }

    $heroClass->stats->age = 1;
    
    $health = 18 + ($heroClass->stats->constitution * 2);

    $datetime1 = new DateTime();
    $date = $datetime1->format("Y-m-d");
    
    $db->Create([$data->ownerId, $data->name, json_encode($heroClass->stats), json_encode($heroClass->inventory), $health, $health, 1, $date, true, "Idle", $heroClass->money, "[]"]);

    return true;
}