<?php
header("Access-Control-Allow-Origin: *");
include "database.php";

$data = file_get_contents("php://input");
$data = json_decode($data);

$valid = true;

if (!isset($data->name))
    $valid = false;

if (!isset($data->email))
    $valid = false;

if (!isset($data->password))
    $valid = false;

if ($valid)
{
    $existingUser = $users->Where("`email`='" . $data->email . "'");

    if ($existingUser != null)
    {
        echo '{"error":"User with this email already exists."}';

        $valid = false;
    }

    if ($valid)
    {
        $users->Create([$data->name, $data->email, $data->password, 1]);

        echo '{"success":"Useraccount successfully created!"}';
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