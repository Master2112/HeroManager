<?php
/*
Returns:
	<object>
		<keyOne>appels</keyOne>
		<keyTwo>peren</keyTwo>
	</object>
*/
function ToXMLObject($name, $objectToWrite)
{
	$xml = '<' . $name . ">\n";
	
	$xml .= ToXML($objectToWrite);
	
	$xml .= '</' . $name . '>';
	
	return $xml;
}

/*
Returns: 
	<keyOne>appels</keyOne>
	<keyTwo>peren</keyTwo>
*/
function ToXML($obj, $keyOverride = "")
{
	$_xml = '';
	foreach($obj as $key => $val)
	{
		if($keyOverride <> "")
			$key = $keyOverride;
	
		if(is_object($val) || is_array($val))
		{
			if(is_object($val))
			{
				$childXML = ToXML($val);
				$_xml .= '<' . $key . ">\n" . $childXML . '</' . $key . ">\n";
			}
			else if(is_array($val))
			{
				$childXML = ToXML($val, $key);
				$_xml .= $childXML;
			}
		}
		else
		{
			$_xml .= '<' . $key . '>' . $val . '</' . $key . ">\n";
		}
	}
	
	return $_xml;
}