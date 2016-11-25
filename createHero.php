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
    $existingUser = $users->Where("`email`='" . $data->email . "' AND `password`='" . $data->key . "'");

    if ($existingUser == null)
    {
        echo '{"error":"Invalid authentication"}';

        $valid = false;
    }

    if ($valid)
    {
        if ($existingUser->emptyHeroSlots <= 0)
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
    echo '{"error":"Invalid parameters"}';
}

function CreateHeroFromData($db, $data)
{
    $classes = file_get_contents("http://www.timfalken.com/heromanager/classes.json");
    $classes = json_decode($classes);

    $class = null;

    for ($i = 0; $i < count($classes); $i++)
    {
        if ($classes[$i]->id == $data->classId)
        {
            $class = $classes[$i];
            break;
        }
    }

    if ($class == null)
    {
        echo '{"error":"Invalid classID"}';
        return false;
    }

    $health = 18 + ($class->stats->constitution * 2);

    $datetime1 = new DateTime();
    $date = $datetime1->format("Y-m-d");

    $db->Create([$data->ownerId, $data->name, $class->stats, $class->inventory, $health, $health, 1, $date, "Idle", $class->money, []]);

    return true;
}