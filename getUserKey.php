<?php
header("Access-Control-Allow-Origin: *");
include "database.php";

$data = file_get_contents("php://input");
$data = json_decode($data);

if(isset($data->email))
{
	$user = $users->Where("`email`='" . strtolower($data->email) . "'")[0];
	
	if($user != null)
	{			
		if(isset($data->password))
		{
			if(md5($data->password) == $user->password)
			{
				echo '{"key":"' . $user->password . '"}';
			}
			else
			{
				echo '{"error":"Invalid password given for this user."}';
			}
		}
		else
		{
			echo '{"error":"No user password given."}';
		}
	}
	else
	{
		echo '{"error":"No user found with this email."}';
	}
	
}
else
{
	echo '{"error":"No user email given."}';
}