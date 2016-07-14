<?php
include_once 'XMLMaker.php';

/*
 * ApiCollection
 * By Tim Falken
 *
 * Usage: 
 *  Create an instance of ApiCollection(), with a DatabaseInfo as first parameter and a ParamsLayout as second parameter.
 *  DatabaseInfo is pretty self-explanatory, but ParamsLayout's meaning may be a bit vague; Just feed it an array of strings,
 *  representing the keys in your database, omitting the ID column.
 *  (NOTE: ID COLUMN MUST BE THE FIRST COLUMN IN THE ROW)
 *
 *  From here, you can use Create(), Edit() and etc. to manipulate your database.
 *  Use BuildPage() to make a page for an API endpoint, including pagination and links.
 *  The script assumes the item links to be $apiUrl + "/<ID>" if no "links" variable is present in the item. If this is incorrect, iterate over $page->items to manually set this.
 *  Then, use $page->ConvertToJSON() or $page->ConvertToXML() to receive a string to be echoed.
 */

class ApiCollection
{
	var $database;
	var $paramsLayout;
	var $idLabel;
	
	var $autoCastNumbers = true;
	
	function __construct($database, $paramsLayout, $idLabel = "id") //arg 1: DatabaseInfo object. Arg2: ParamsLayout obj. Arg 3: Optional arg to indicate what the id in the table is called.
	{
		$this->database = $database;
		$this->paramsLayout = array_reverse($paramsLayout->names);
		array_push($this->paramsLayout, $idLabel);
		
		$this->paramsLayout = array_reverse($this->paramsLayout);
		
		$this->idLabel = $idLabel;
	}
	
	function SetAutoCastForNumbers($newVal)
	{
		$this->autoCastNumbers = $newVal == true;
	}
	
	function Connect()
	{
		$link = mysqli_connect($this->database->serverName, $this->database->userName, $this->database->password, $this->database->databaseName); 
	
		if(!$link)
			die( "Database connection for db " . $this->database->databaseName . ", " . $this->database->tableName . " failed" . mysql_error($link) ); //Er ging iets mis, dump de database-error op het scherm
			
		return $link;
	}
	
	function Disconnect($toDisconnect)
	{
		mysqli_close($toDisconnect); //sluit de link
	}
	
	function Create($params)
	{
		$success = false;
	
		$link  = $this->Connect();
		
		if(count($params) != count($this->paramsLayout) - 1)
		{
			die("Table " . $this->database->tableName . " reports: Create failed. Reason: Parameter count doesn't match table layout. (do not include id here)");
		}
		
		$paramStr = implode(", ", $this->paramsLayout);
		$paramStr = str_replace($this->idLabel . ", ", "", $paramStr);
		
		$paramInput = "";
		
		for($i = 0; $i < count($params); $i++)
		{
			$paramInput .= "'" . $params[$i] . "'";
			
			if($i < count($params) - 1)
				$paramInput .= ", ";
		}
		
		$sql = "INSERT INTO `" . $this->database->tableName . "` ($paramStr) VALUES ($paramInput)";

		mysqli_query($link, $sql);
		
		$success = true;
		
		$this->Disconnect($link);
		
		return $success;
	}
	
	function Delete($id)
	{
		$link = $this->Connect();
		
		$result = mysqli_query($link, "DELETE FROM `" . $this->database->tableName . "` WHERE `" . $this->idLabel . "`=$id"); 
		
		$this->Disconnect($link);
	}
	
	function Edit($id, $params)
	{
		$success = false;
	
		if(count($params) != count($this->paramsLayout) - 1)
		{
			die("Table " . $this->database->tableName . " reports: Edit failed. Reason: Parameter count doesn't match table layout.  (do not include id here)");
		}
	
		$link = $this->Connect();
		
		$paramInput = "";
		
		for($i = 0; $i < count($params); $i++)
		{
			$paramInput .= "`" . $this->paramsLayout[$i + 1] . "`=" . "'" . $params[$i] . "'";
			
			if($i < count($params) - 1)
				$paramInput .= ", ";
		}
		
		$sql = "UPDATE `" . $this->database->tableName . "` SET $paramInput WHERE `" . $this->idLabel . "` = $id";
		
		mysqli_query($link, $sql);
		
		$success = true;
		
		$this->Disconnect($link);
		
		return $success;
	}
	
	function GetRow($id)
	{
		$link = $this->Connect();
		
		$result = mysqli_query($link, "SELECT * FROM `" . $this->database->tableName . "` WHERE `" . $this->idLabel . "`='$id'"); 
		
		$results = new stdClass();
		
		if (false === $result)  //als de query misgaat;
		{
			die ('Error: ' . mysqli_error($link)); //Er ging iets mis, dump de database-error op het scherm
		}
		else
		{
			$resultsRaw = mysqli_fetch_row($result); //haal alle rijen uit het query-result object
			
			for($i = 0; $i < count($this->paramsLayout); $i++)
			{
				$key = $this->paramsLayout[$i];
				$results->$key = $resultsRaw[$i];
				
				if(is_numeric($results->$key))
					$results->$key = (float)$results->$key;
			}
		}
		
		$this->Disconnect($link);
			
		return $results;
	}
	
	function Join($otherTable, $myParam, $otherParam, $where = "") //experimental
	{
		$link = $this->Connect();
		
		$sql = "SELECT * FROM `" . $this->database->tableName . "` "
			."INNER JOIN `" . $otherTable->database->tableName . "` "
			."ON " . $this->database->tableName . "." . $myParam . " = " . $otherTable->database->tableName . "." . $otherParam . " "
			.($where <> ""? "WHERE " . $where : "");
		
		$result = mysqli_query($link, $sql); 
		
		if (false === $result)  //als de query misgaat;
		{
			die ('Error: ' . mysqli_error($link)); //Er ging iets mis, dump de database-error op het scherm
		}
		else
		{
			$results = mysqli_fetch_all($result); //haal alle rijen uit het query-result object
		}
		
		Disconnect($link);
		
		return $results;
	}
	
	function Where($whereStr)
	{
		$link = $this->Connect();
		
		$result = mysqli_query($link, "SELECT * FROM `" . $this->database->tableName . "` WHERE $whereStr"); 
		
		if (false === $result)  //als de query misgaat;
		{
			die ('Error: ' . mysqli_error($link)); //Er ging iets mis, dump de database-error op het scherm
		}
		else
		{
			$resultsRaw = mysqli_fetch_all($result); //haal alle rijen uit het query-result object
		}
		
		$results = [];
		
		for($o = 0; $o < count($resultsRaw); $o++)
		{
			$results[$o] = new stdClass();
			
			for($i = 0; $i < count($this->paramsLayout); $i++)
			{
				$key = $this->paramsLayout[$i];
				$results[$o]->$key = $resultsRaw[$o][$i];
				
				if(is_numeric($results[$o]->$key))
					$results[$o]->$key = (float)$results[$o]->$key;
			}
		}
		
		$this->Disconnect($link);
			
		return $results;
	}
	
	function All()
	{
		$link = $this->Connect();
		
		$result = mysqli_query($link, "SELECT * FROM `" . $this->database->tableName . "`"); 
		
		if (false === $result)  //als de query misgaat;
		{
			die ('Error: ' . mysqli_error($link)); //Er ging iets mis, dump de database-error op het scherm
		}
		else
		{
			$resultsRaw = mysqli_fetch_all($result); //haal alle rijen uit het query-result object
		}
		
		$results = [];
		
		for($o = 0; $o < count($resultsRaw); $o++)
		{
			$results[$o] = new stdClass();
			
			for($i = 0; $i < count($this->paramsLayout); $i++)
			{
				$key = $this->paramsLayout[$i];
				$results[$o]->$key = $resultsRaw[$o][$i];
				
				if(is_numeric($results[$o]->$key))
					$results[$o]->$key = (float)$results[$o]->$key;
			}
		}
		
		$this->Disconnect($link);
			
		return $results;
	}
	
	function BuildPage($baseUrl, $start, $limit, $links = [])
	{
		$collectionObj = new CollectionObject($baseUrl, $this->All(), $links, $this->idLabel);
		$collectionObj->setStart($start);
		$collectionObj->setLimit($limit);
		
		return $collectionObj;
	}
}

class ParamsLayout
{
	var $names = [];
	
	function __construct($paramNameArray)
	{
		$this->names = $paramNameArray;
	}
}

class DatabaseInfo
{
	var $serverName = "";
	var $databaseName = "";
	var $tableName = "";
	var $userName = "";
	var $password = "";
	
	function __construct($server, $databaseName, $tableName, $userName, $password)
	{
		$this->serverName = $server;
		$this->databaseName = $databaseName;
		$this->tableName = $tableName;
		$this->userName = $userName;
		$this->password = $password;
	}
}

class CollectionLink
{
	var $rel;
	var $href;
	
	function __construct($rel, $href)
	{
		$this->rel = $rel;
		$this->href = $href;
	}
}

class CollectionObject
{	
	var $apiUrl = "";
	var $items = array();
	var $links = array();
	
	var $start = 0;
	var $limit = 10000;
	
	function __construct($url, $collectionArray, $linksArray, $idLabel)
	{
		$this->apiUrl = $url;
		$this->items = $collectionArray;
		
		for($i = 0; $i < count($this->items); $i++)
		{
			if(!isset($this->items[$i]->links))
			{
				$linksObj = [];
				
				array_push($linksObj, new CollectionLink("self", $url . "/" . $this->items[$i]->$idLabel));
				array_push($linksObj, new CollectionLink("collection", $url));
				
				$this->items[$i]->links = $linksObj;
			}
		}
		
		$this->links = array_reverse($linksArray);
		
		array_push($this->links, new CollectionLink("self", $url));
		$this->links = array_reverse($this->links);
	}
	
	function setStart($newStart)
	{
		$this->start = max($newStart, 1);
	}	
	
	function setLimit($newLimit)
	{
		$this->limit = max($newLimit, 1);
	}
	
	function getTotalItems()
	{
		return count($this->items);
	}
	
	function getTotalPages()
	{
		return ceil((float)$this->getTotalItems() / (float)$this->limit);
	}
	
	function getCurrentItems()
	{
		return min($this->getTotalItems() - ($this->start - 1), $this->limit);
	}
	
	function getCurrentPage()
	{
		$currentPage = 0;
		$start = $this->start;
		
		while($start > 0)
		{
			$start -= $this->limit;
			$currentPage++;
		}
		
		return $currentPage;
	}
	
	function getLastPageStartValue()
	{
		$lastPage = $this->start - 1;
	
		while($lastPage < $this->getTotalItems() - $this->limit)
			$lastPage += $this->limit;
			
		return $lastPage + 1;
	}
	
	function generatePaginationObject()
	{
		$paginationObj = new stdClass();
		
		$paginationObj->totalItems = $this->getTotalItems();
		$paginationObj->totalPages = $this->getTotalPages();
		$paginationObj->currentPage = $this->getCurrentPage();
		$paginationObj->currentItems = (int)$this->getCurrentItems();
	
		$paginationObj->links = array();
		
		$newLink = new stdClass();
		$newLink->rel = "first";
		$newLink->page = 1;
		$newLink->href = $this->apiUrl . "?start=1&amp;limit=" . $this->limit;
		array_push($paginationObj->links, $newLink);
		
		$newLink = new stdClass();
		$newLink->rel = "previous";
		$newLink->page = max((int)$this->getCurrentPage() - 1, 1);
		$newLink->href = $this->apiUrl . "?start=" . max(1, $this->start - $this->limit) . "&amp;limit=" . $this->limit;
		array_push($paginationObj->links, $newLink);
		
		$newLink = new stdClass();
		$newLink->rel = "next";
		$newLink->page = min($this->getCurrentPage() + 1, $this->getTotalPages());
		$newLink->href = $this->apiUrl . "?start=" . min(max(((int)$this->getTotalItems() - (int)$this->limit) + 1, 1), ($this->start + $this->limit)) . "&amp;limit=" . $this->limit;
		array_push($paginationObj->links, $newLink);
		
		$newLink = new stdClass();
		$newLink->rel = "last";
		$newLink->page = $this->getTotalPages();
		$newLink->href = $this->apiUrl . "?start=" . $this->getLastPageStartValue() . "&amp;limit=" . $this->limit;
		array_push($paginationObj->links, $newLink);
		
		return $paginationObj;
	}
	
	function convertToJSON()
	{
		$output = new stdClass();
		$output->items = array_slice($this->items, $this->start - 1, $this->limit);
		$output->links = $this->links;
		$output->pagination = $this->generatePaginationObject();
		
		return json_encode($output);
	}
	
	function convertToXML()
	{
		require_once "XMLMaker.php";
		
		$output = new stdClass();
		$output->items = array_slice($this->items, $this->start - 1, $this->limit);
		$output->links = $this->links;
		$output->pagination = $this->generatePaginationObject();
		
		return ToXMLObject("heroes", $output);
	}
}