<?php

// Include the database connectivity functions
require_once('../utils/db_utils.php');

// Include the LDAB connectivity functions
require_once('../utils/ldap_utils.php');

// Include the abstract API class
require_once('../api/abstract_api.php');

/*
*	Concrete API implementation for WebDrink
*/
class DrinkAPI extends API
{
	private $data = array();
	private $admin = false;
	private $uid = false;

	// Constructor
	public function __construct($request) {
		parent::__construct($request);
		// Grab the user's uid from Webauth
		if (array_key_exists("WEBAUTH_USER", $_SERVER)) {
			$this->uid = $_SERVER["WEBAUTH_USER"];
		} 
		else {
			$this->uid = "bencentra";
		}
		// If the request is a POST method, verify the user is an admin
		if ($this->method == "POST") {
			/*
			// Make sure the user's uid was provided in the URL (Ex - api/v1/<method>/uid/<uid>)
			// AND make sure they are who they say they are (same as Webauth token)
			if (array_key_exists("uid", $this->args) && "".$this->args["uid"] === $this->uid) {
				$this->admin = $this->isAdmin($this->uid);
			}
			// Check if the user is an admin
			else {
				$this->admin = false;
			}
			*/
			// Check if the user is an admin
			$this->admin = $this->isAdmin($this->uid);
		}
	}

	// Determine if the user is a Drink Admin or not
	protected function isAdmin($uid) {
		$fields = array('drinkAdmin');
		$result = ldap_lookup($uid, $fields);
		if (isset($result[0]['drinkadmin'][0])) {
			return $result[0]['drinkadmin'][0];
		}
		else {
			return false;
		}
	}

	// Test endpoint - make sure you can contact the API
	protected function test() {
		return array("result" => true, "message" => "Test Success!");
	}

	// Users enpoint - call the various user-related API methods
	protected function users() {
		// Create an array to store response data
		$result = array();
		// Create an array to store parameters for SQL queries
		$params = array();
		// Determine the specific method to call
		switch($this->verb) {
			/*case "getInfo":
				if ($this->method == "GET") {
					if (!array_key_exists("uid", $this->args)) {
						$result["result"] = false;
						$result["message"] = "Error: uid not supplied (users.getInfo)";
						$result["data"] = false;
					}
					$fields = array('drinkBalance', 'drinkAdmin', 'ibutton');
					$data = ldap_lookup($this->args["uid"], $fields);
					if ($data) {
						$tmp = array();
						$tmp["uid"] = $this->args["uid"];
						$tmp["credits"] = $data[0]["drinkbalance"][0];
						$tmp["admin"] = $data[0]["drinkadmin"][0];
						$tmp["ibutton"] = $data[0]["ibutton"][0];
						$result["result"] = true;
						$result["message"] = "Success (users.getInfo)";
						$result["data"] = $tmp;
					}
					else {
						$result["result"] = false;
						$result["message"] = "Error: failed to query LDAP (users.Info)";
						$result["data"] = $false;
					}
				}
				else {
					$result["result"] = false;
					$result["message"] = "Error: only accepts GET requests (users.getInfo)";
					$result["data"] = false;
				}
				break;*/
			/* 
			*	users.getCredits (GET) - Get the amount of drink credits for a user
			*
			*	Example URL: api/v1/users/getCredits/uid/bencentra
			*	
			*	Expected Parameters: 
			*	- uid: The username to look up
			*
			*	Return Data: 
			*	- Number of drink credits the user has
			*	
			*	{
			*		"result": true,
			*		"message": "Success (users.getCredits)",
			*		"data": "2000"
			*	}
			*/
			case "getCredits":
				if ($this->method == "GET") {
					if (!array_key_exists("uid", $this->args)) {
						$result["result"] = false;
						$result["message"] = "Error: uid not supplied (users.getCredits)";
						$result["data"] = false;
						break;
					}
					$fields = array('drinkBalance');
					$data = ldap_lookup($this->args["uid"], $fields);
					if ($data) {
						$result["result"] = true;
						$result["message"] = "Success (users.getCredits)";
						$result["data"] = $data[0]['drinkbalance'][0];
					}
					else {
						$result["result"] = false;
						$result["message"] = "Error: failed to query LDAP (users.getCredits)";
						$result["data"] = false;
					}
				}
				else {
					$result["result"] = false;
					$result["message"] = "Error: only accepts GET requests (users.getCredits)";
					$result["data"] = false;
				}
				break;
			/*
			*	users.updateCredits (POST) - Update the drink credit balance for a user
			*
			*	Example URL: api/v1/users/updateCredits/uid/bencentra/credits/2000
			*
			*	Expected Parameters: 
			*	- uid: The username to look up
			*	- credits: The new credits value to set
			*
			*	Return Data: 
			*	- True for success
			*	
			*	{
			*		"result": true,
			*		"message": "Success (users.updateCredits)",
			*		"data": true
			*	}
			*/
			case "updateCredits":
				if ($this->method == "POST") {
					// Only run if you're an admin
					if ($this->admin) {
						// Make sure the necessary parameters were provided
						if (!array_key_exists("uid", $this->args)) {
							$result["result"] = false;
							$result["message"] = "Error: uid not supplied (users.updateCredits)";
							$result["data"] = false;
							break;
						}
						else if (!array_key_exists("credits", $this->args)) {
							$result["result"] = false;
							$result["message"] = "Error: credits not supplied (users.updateCredits)";
							$result["data"] = false;
							break;
						}
						// Update the user's drink credit value
						$replace = array('drinkBalance' => $this->args["credits"]);
						$data = ldap_update($this->args["uid"], $replace);
						if ($data) {
							$result["result"] = true;
							$result["message"] = "Success (users.updateCredits)";
							$result["data"] = true;
						}
						else {
							$result["result"] = false;
							$result["message"] = "Error: failed to query LDAP (users.updateCredits)";
							$result["data"] = false;
						}
					}
					else {
						$result["result"] = false;
						$result["message"] = "Error: must be an admin to update drink credits (users.updateCredits)";
						$result["data"] = false;
					}
				}
				else {
					$result["result"] = false;
					$result["message"] = "Error: only accepts POST requests (users.updateCredits)";
					$result["data"] = false;
				}
				break;
			/* 
			*	users.searchUsers (GET) - Search LDAP for usernames by letter/string
			*
			*	Example URL: api/v1/users/searchUsers/search/ben
			*	
			*	Expected Parameters: 
			*	- search: the string to search for 
			*
			*	Return Data: 
			*	- Number of drink credits the user has
			*	
			*	{
			*		"result": true,
			*		"message": "Success (users.searchUsers)",
			*		"data":[
			*			{
			*				"uid": "ben",
			*				"cn": "Ben Litchfield"
			*			},
			*			...
			*		}
			*	}
			*/
			case "searchUsers":
				if ($this->method == "GET") {
					if (!array_key_exists("search", $this->args)) {
						$result["result"] = false;
						$result["message"] = "Error: search term not supplied (users.searchUsers)";
						$result["data"] = false;
						break;
					}
					$fields = array('uid', 'cn');
					$data = ldap_lookup($this->args["search"]."*", $fields);
					if ($data) {
						$result["result"] = true;
						$result["message"] = "Success (users.searchUsers)";
						$tmp = array();
						$i = 0;
						foreach ($data as $user) {
							$tmp[$i] = array("uid" => $user["uid"][0], "cn" => $user["cn"][0]);
							$i++;
						}
						array_shift($tmp);
						$result["data"] = $tmp;
					}
					else {
						$result["result"] = false;
						$result["message"] = "Error: failed to query LDAP (users.searchUsers)";
						$result["data"] = false;
					}
				}
				else {
					$result["result"] = false;
					$result["message"] = "Error: only accepts GET requests (users.searchUsers)";
					$result["data"] = false;
				}
				break;
			/* 
			*	users.getiButton (GET) - Get the iButton value for a user
			*
			*	Example URL: api/v1/users/getCredits/uid/bencentra
			*	
			*	Expected Parameters: 
			*	- uid: The username to look up (Note: must be your own uid, unless you are an admin)
			*
			*	Return Data: 
			*	- Number iButton of the user
			*	
			*	{
			*		"result": true,
			*		"message": "Success (machines.getStockAll)",
			*		"data": "<ibutton>"
			*	}
			*/
			case "getiButton":
				if ($this->method == "GET") {
					if (!array_key_exists("uid", $this->args)) {
						$result["result"] = false;
						$result["message"] = "Error: uid not supplied (users.getCredits)";
						$result["data"] = false;
						break;
					}
					// Only get your own iButton (unless you're an admin)
					if ($this->args["uid"] == $this->uid || $this->admin) {
						$fields = array('drinkBalance');
						$data = ldap_lookup($this->args["uid"], $fields);
						if ($data) {
							$result["result"] = true;
							$result["message"] = "Success (users.getiButton)";
							$result["data"] = $data[0]['ibutton'][0];
						}
						else {
							$result["result"] = false;
							$result["message"] = "Error: failed to query LDAP (users.getiButton)";
							$result["data"] = false;
						}
					}
					else {
						$result["result"] = false;
						$result["message"] = "Error: must be an admin to retrieve any iButton (users.getiButton)";
						$result["data"] = false;
					}
				}
				else {
					$result["result"] = false;
					$result["message"] = "Error: only accepts GET requests (users.getiButton)";
					$result["data"] = false;
				}
				break;
			/* 
			*	users.getDrops (GET) - Get the drop history for a user
			*
			*	Example URL: api/v1/users/getCredits/uid/bencentra/limit/10/offset/0
			*	
			*	Expected Parameters: 
			*	- uid: The username to look up (Note: must be your own uid, unless you are an admin)
			*	- limit: The amount of drops to load
			*	- offset: The drop entry to start loading from 
			*
			*	Return Data: 
			*	- JSON-encoded array of a user's drop history
			*	
			*	{
			*		"result": true,
			*		"message": "Success (users.getDrops)",
			*		"data": [ 
			*			{
			*				"drop_log_id": "10447",
			*				"machine_id": "1",
			*				"display_name": "Little Drink",
			*				"slot": "4",
			*				"username":"bencentra", 
			*				"time": "2013-11-11 19:52:23",
			*				"status": "ok",
			*				"item_id": "84",
			*				"item_name": "Ginger Ale",
			*				"current_item_price": "50"
			*			},
			*			...
			*		]
			*	}
			*/
			case "getDrops":
				if ($this->method == "GET") {
					// Make sure a username was provided
					/*if (!array_key_exists("uid", $this->args)) {
						$result["result"] = false;
						$result["message"] = "Error: uid not supplied (users.getCredits)";
						$result["data"] = false;
						break;
					}
					$params["username"] = $this->args["uid"];*/

					// Form the SQL query
					$sql = "SELECT l.drop_log_id, l.machine_id, m.display_name, l.slot, l.username, l.time, l.status, l.item_id, i.item_name, l.current_item_price 
							FROM drop_log as l, machines as m, drink_items as i WHERE";
					if (array_key_exists("uid", $this->args)) {
						$sql .= " l.username = :username AND";
						$params["username"] = $this->args["uid"];
					}
					$sql .= " m.machine_id = l.machine_id AND i.item_id = l.item_id 
							ORDER BY l.drop_log_id DESC";

					// Add a limit and offset, if provided
					if (array_key_exists("limit", $this->args)) {
						$sql .= " LIMIT :limit";
						$params["limit"] = $this->args["limit"];
					}
					if (array_key_exists("limit", $this->args) && array_key_exists("offset", $this->args)) {
						$sql .= " OFFSET :offset";
						$params["offset"] = $this->args["offset"];
					}

					// Query the database
					$query = db_select($sql, $params);

					// Query success
					if ($query) {
						// Format the data
						$result["result"] = true;
						$result["message"] = "Success (users.getDrops)";
						$result["data"] = $query;
					}
					// Query failure
					else {
						$result["result"] = false;
						$result["message"] = "Error: failed to query database (users.getDrops)";
						$result["data"] = $sql;
					}
				}
				else {
					$result["result"] = false;
					$result["message"] = "Error: only accepts GET requests (users.getDrops)";
					$result["data"] = false;
				}
				break;
			/*
			*	Base case - no specific API method called
			*/
			default:
				$result["result"] = false;
				$result["message"] = "Invalid API method call (users)";
				$result["data"] = false;
				break;
		}
		// Return the response data
		return $result;
	}

	// Machines endpoint - call the various machine-related API methods
	protected function machines() {
		// Create an array to store response data
		$result = array();
		// Create an array to store parameters for SQL queries
		$params = array();
		// Determine the specific method to call
		switch($this->verb) {
			/* 
			*	machines.getStockAll (GET) - Get the combined stock of all the drink machines
			*
			*	Example URL: api/v1/machines/getStockAll
			*	
			*	Expected Parameters: 
			*	- None
			*
			*	Return Data: 
			*	- JSON-encoded array of all items from all machines
			*	
			*	{
			*		"result": true,
			*		"message": "Success (machines.getStockAll)",
			*		"data": {
			*			"2": [
			*				{
			*					"slot_num":"1",
			*					"machine_id":"2",
			*					"display_name":"Big Drink",
			*					"item_id":"9",
			*					"item_name":"Coke",
			*					"item_price":"50",
			*					"available":"1",
			*					"status":"enabled"
			*				},
			*				...	
			*			],
			*			...
			*		}
			*	}
			*/
			case "getStockAll":
				if ($this->method == "GET") {
					// Form the SQL query
					$sql = "SELECT s.slot_num, s.machine_id, m.display_name, i.item_id, i.item_name, i.item_price, s.available, s.status 
							FROM slots as s, machines as m, drink_items as i 
							WHERE i.item_id = s.item_id AND m.machine_id = s.machine_id";

					// Query the database
					$query = db_select($sql, $params);

					// Query success
					if ($query) {
						$data = array();
						foreach($query as $q) {
							$data[$q["machine_id"]][] = $q;
						}
						$result["result"] = true;
						$result["message"] = "Success (machines.getStockAll)";
						$result["data"] = $data;
					}
					// Query failure
					else {
						$result["result"] = false;
						$result["message"] = "Error: failed to query database (machines.getStockAll)";
						$result["data"] = $query;
					}
				}
				else {
					$result["result"] = false;
					$result["message"] = "Error: only accepts GET requests (machines.getStockAll)";
					$result["data"] = false;
				}
				break;
			/*
			*	machines.getStockOne (GET) - Get the stock for one drink machine
			*
			*	Example URL: api/v1/machines/getStockOne/machineId/<machine_id>
			*
			*	Expected Parameters: 
			*	- machineId: ID of the machine (Ex - Big Drink = 2)
			*
			*	Return Data:
			*	- JSON-encoded array of all items from one machine
			*
			*	{
			*		"result": true,
			*		"message": "Success (machines.getStockOne)",
			*		"data": {
			*			"2": [
			*				{
			*					"slot_num":"1",
			*					"machine_id":"2",
			*					"display_name":"Big Drink",
			*					"item_id":"9",
			*					"item_name":"Coke",
			*					"item_price":"50",
			*					"available":"1",
			*					"status":"enabled"
			*				},
			*				...	
			*			]
			*		}
			*	}
			*/
			case "getStockOne":
				if ($this->method == "GET") {
					// Form the SQL query
					$sql = "SELECT s.slot_num, s.machine_id, m.display_name, i.item_id, i.item_name, i.item_price, s.available, s.status 
							FROM slots as s, machines as m, drink_items as i 
							WHERE i.item_id = s.item_id AND m.machine_id = s.machine_id AND s.machine_id = :machineId";

					// Ensure the parameters are properly formatted
					if (!array_key_exists("machineId", $this->args)) {
						$result["result"] = false;
						$result["message"] = "Error: machineId not supplied (machines.getStockOne)";
						$result["data"] = false;
						break;
					}

					// Query the database
					$params["machineId"] = $this->args["machineId"];
					$query = db_select($sql, $params);

					// Query success
					if ($query) {
						$data = array();
						foreach($query as $q) {
							$data[$q["machine_id"]][] = $q;
						}
						$result["result"] = true;
						$result["message"] = "Success (machines.getStockOne)";
						$result["data"] = $data;
					}
					// Query failure
					else {
						$result["result"] = false;
						$result["message"] = "Error: failed to query database (machines.getStockOne)";
						$result["data"] = $query;
					}
				}
				else {
					$result["result"] = false;
					$result["message"] = "Error: only accepts GET requests (machines.getStockOne)";
					$result["data"] = false;
				}
				break;
			/*
			*	machines.getMachineAll (GET) - Get a list of all machines (just info, no stock)
			*
			*	Example URL: api/v1/machines/getMachineAll
			*
			*	Expected Parameters: 
			*	- None
			*
			*	Return Data:
			*	- JSON-encoded array of info about all machines
			*
			*	{
			*		"result": true,
			*		"message": "Success (machines.getMachineAll)",
			*		"data": [
			*			{
			*				"machine_id":"1",
			*				"name":"littledrink",
			*				"display_name":"Little Drink",
			*				"alias_id":"1",
			*				"alias":"ld"
			*			},
			*			...	
			*		]
			*	}
			*/
			case "getMachineAll":
				if ($this->method == "GET") {
					// Form the SQL query
					$sql = "SELECT m.machine_id, m.name, m.display_name, a.alias_id, a.alias 
							FROM machines as m, machine_aliases as a 
							WHERE a.machine_id = m.machine_id";

					// Query the database
					$query = db_select($sql, $params);	

					// Query success
					if ($query) {
						// Format the data
						$result["result"] = true;
						$result["message"] = "Success (machines.getMachineAll)";
						$result["data"] = $query;
					}
					// Query failure
					else {
						$result["result"] = false;
						$result["message"] = "Error: failed to query database (machines.getMachineAll)";
						$result["data"] = $query;
					}		
				}
				else {
					$result["result"] = false;
					$result["message"] = "Error: only accepts GET requests (machines.getMachineAll)";
					$result["data"] = false;
				}
				break;
			/*
			*	machines.getMachineOne (GET) - Get the info for a single drink machine (not stock)
			*
			*	Example URL: api/v1/machines/getMachineOne/machineId/1
			*
			*	Expected Parameters: 
			*	- machineId: ID of the machine (Ex - Big Drink = 2)
			*
			*	Return Data:
			*	- JSON-encoded array of info about one machine
			*
			*	{
			*		"result": true,
			*		"message": "Success (machines.getMachineOne)",
			*		"data": [
			*			{
			*				"machine_id":"1",
			*				"name":"littledrink",
			*				"display_name":"Little Drink",
			*				"alias_id":"1",
			*				"alias":"ld"
			*			}
			*		]
			*	}
			*/
			case "getMachineOne":
				if ($this->method == "GET") {
					// Form the SQL query
					$sql = "SELECT m.machine_id, m.name, m.display_name, a.alias_id, a.alias 
							FROM machines as m, machine_aliases as a 
							WHERE a.machine_id = m.machine_id AND m.machine_id = :machineId";

					// Ensure the parameters are properly formatted
					if (!array_key_exists("machineId", $this->args)) {
						$result["result"] = false;
						$result["message"] = "Error: machineId not supplied (machines.getMachineOne)";
						$result["data"] = false;
						break;
					}

					// Query the database
					$params["machineId"] = $this->args["machineId"];
					$query = db_select($sql, $params);

					// Query success
					if ($query) {
						$result["result"] = true;
						$result["message"] = "Success (machines.getMachineOne)";
						$result["data"] = $query;
					}
					// Query failure
					else {
						$result["result"] = false;
						$result["message"] = "Error: failed to query database (machines.getMachineOne)";
						$result["data"] = $query;
					}
				}
				else {
					$result["result"] = false;
					$result["message"] = "Error: only accepts GET requests (machines.getMachineOne)";
					$result["data"] = false;
				}
				break;
			/*
			*	machines.getItemAll (GET) - Get a list of all drink items
			*
			*	Example URL: api/v1/machines/getItemAll
			*
			*	Expected Parameters: 
			*	- None
			*
			*	Return Data:
			*	- JSON-encoded array of all drink items
			*
			*	{
			*		"result": true,
			*		"message": "Success (machines.getItemAll)",
			*		"data": [
			*			{
			*				"item_id":"8",
			*				"item_name":"Dr. Pepper",
			*				"item_price":"50",
			*				"date_added":"2011-10-22 22:47:53",
			*				"state":"active"
			*			},
			*			...	
			*		]
			*	}
			*/
			case "getItemAll":
				if ($this->method == "GET") {
					// Form the SQL query
					$sql = "SELECT item_id, item_name, item_price, state FROM drink_items WHERE state = 'active'";

					// Query the database
					$query = db_select($sql, $params);

					// Query success
					if ($query) {
						$result["result"] = true;
						$result["message"] = "Success (machines.getItemAll)";
						$result["data"] = $query;
					}
					// Query failure
					else {
						$result["result"] = false;
						$result["message"] = "Error: failed to query database (machines.getItemAll)";
						$result["data"] = $query;
					}
				}
				else {
					$result["result"] = false;
					$result["message"] = "Error: only accepts GET requests (machines.getItemAll)";
					$result["data"] = false;
				}
				break;
			/*
			*	machines.updateSlot (POST) - Get the info for a single drink machine (not stock)
			*
			*	Example URL: api/v1/machines/updateSlot/slotNum/1/machineId/2
			*
			*	Expected Parameters: 
			*	- slotNum: Slot number to update
			*	- machineId: ID of the machine (Ex - Big Drink = 2)
			*	- itemId: ID of the new item for the slot (optional)
			* 	- available: Amount (or boolean) of item available (optional)
			*	- status: "enabled" or "disabled" (optional)
			*
			*	Return Data:
			*	- True for success, false for failure
			*
			*	{
			*		"result": true,
			*		"message": "Success (machines.updateSlot)",
			*		"data": true
			*	}
			*/
			case "updateSlot":
				//$this->admin = $this->isAdmin(($this->uid));
				if ($this->method == "POST") {
					if ($this->admin) {
						// Make sure the required parameters were provided
						if (!array_key_exists("slotNum", $this->args)) {
							$result["result"] = false;
							$result["message"] = "Error: slotNum not supplied (machines.updateSlot)";
							$result["data"] = false;
							break;
						}
						if (!array_key_exists("machineId", $this->args)) {
							$result["result"] = false;
							$result["message"] = "Error: machineId not supplied (machines.updateSlot)";
							$result["data"] = false;
							break;
						}

						// Form the SQL query
						$sql = "UPDATE slots SET";
						$append = "";
						if (array_key_exists("itemId", $this->args)) {
							$append .= " item_id = :itemId,";
							$params["itemId"] = $this->args["itemId"];
						}
						if (array_key_exists("available", $this->args)) {
							$append .= " available = :available,";
							$params["available"] = $this->args["available"];
						}
						if (array_key_exists("status", $this->args)) {
							$append .= " status = :status,";
							$params["status"] = $this->args["status"];
						}
						if ($append == "") {
							$result["result"] = false;
							$result["message"] = "Error: invalid number of parameters (machines.updateSlot)";
							$result["data"] = false;
							break;
						}
						$append = substr($append, 0, -1);
						$sql .= $append . " WHERE slot_num = :slotNum AND machine_id = :machineId";
						//die($sql);
						// Query the database
						$params["slotNum"] = $this->args["slotNum"];
						$params["machineId"] = $this->args["machineId"];
						$query = db_update($sql, $params);

						// Query success
						if ($query) {
							$result["result"] = true;
							$result["message"] = "Success (machines.updateSlot)";
							$result["data"] = true;
						}
						// Query failure
						else {
							$result["result"] = false;
							$result["message"] = "Error: failed to query database (machines.updateSlot)";
							$result["data"] = false;
						}
					}
					else {
						$result["result"] = false;
						$result["message"] = "Error: must be an admin (machines.updateSlot)";
						$result["data"] = false;
					}
				}
				else {
					$result["result"] = false;
					$result["message"] = "Error: only accepts POST requests (machines.updateSlot)";
					$result["data"] = false;
				}
				break;
			/*
			*	machines.updateItem (POST) - Update an item's name, price, or state
			*
			*	Example URL: api/v1/machines/updateItem/itemId/8/name/Cola/price/55
			*
			*	Expected Parameters: 
			*	- itemId: ID of the item to update
			*	- name: New name of the item (optional)
			*	- price: New price of the item (optional)
			*	- state: New state of the item, "active" or "inactive" (optional)
			*
			*	Return Data:
			*	- True for success, false for failure
			*
			*	{
			*		"result": true,
			*		"message": "Success (machines.updateItem)",
			*		"data": true
			*	}
			*/
			case "updateItem":
				// Only accept POST requests
				if ($this->method == "POST") {
					// Only run if the user is an admin
					if ($this->admin) {
						// Make sure an itemId was provided
						if (!array_key_exists("itemId", $this->args)) {
							$result["result"] = false;
							$result["message"] = "Error: itemId not provided (machines.updateItem)";
							$result["data"] = false;
							break;
						}
						// Form the SQL query
						$append = "";
						$sql = "UPDATE drink_items SET";
						if (array_key_exists("name", $this->args)) {
							$append .= " item_name = :name,";
							$params["name"] = $this->args["name"];
						}
						if (array_key_exists("price", $this->args)) {
							$append .= " item_price = :price,";
							$params["price"] = $this->args["price"];
						}
						if (array_key_exists("state", $this->args)) {
							$append .= " state = :state,";
							$params["state"] = $this->args["state"];
						}
						if ($append == "") {
							$result["result"] = false;
							$result["message"] = "Error: invalid number of parameters (machines.updateItem)";
							$result["data"] = false;
							break;
						}
						$append = substr($append, 0, -1);
						$sql .= $append . " WHERE item_id = :itemId";
						$params["itemId"] = $this->args["itemId"];
						// Make the Query
						$query = db_update($sql, $params);
						// Query Success
						if ($query) {
							$result["result"] = true;
							$result["message"] = "Success (machines.updateItem)";
							$result["data"] = true;
						}
						// Query Failure
						else {
							$result["result"] = false;
							$result["message"] = "Error: failed to query database (machines.updateItem)";
							$result["data"] = false;
						}
					}
					else {
						$result["result"] = false;
						$result["message"] = "Error: must be an admin (machines.updateItem)";
						$result["data"] = false;
					}
				}
				else {
					$result["result"] = false;
					$result["message"] = "Error: only accepts POST requests (machines.updateItem)";
					$result["data"] = false;
				}
				break;
			/*
			*	machines.deleteItem (POST) - Delete an item from the database
			*
			*	Example URL: api/v1/machines/deleteItem/itemId/8/
			*
			*	Expected Parameters: 
			*	- itemId: ID of the item to delete
			*
			*	Return Data:
			*	- True for success, false for failure
			*
			*	{
			*		"result": true,
			*		"message": "Success (machines.deleteItem)",
			*		"data": true
			*	}
			*/
			case "deleteItem":
				// Only accept POST requests
				if ($this->method == "POST") {
					// Only run if the user is an admin
					if ($this->admin) {
						// Make sure an itemId was provided
						if (!array_key_exists("itemId", $this->args)) {
							$result["result"] = false;
							$result["message"] = "Error: itemId not provided (machines.deleteItem)";
							$result["data"] = false;
							break;
						}
						// Form the SQL query
						$sql = "DELETE FROM drink_items WHERE item_id = :itemId";
						$params["itemId"] = $this->args["itemId"];
						// Make the Query
						$query = db_delete($sql, $params);
						// Query Success
						if ($query) {
							$result["result"] = true;
							$result["message"] = "Success (machines.deleteItem)";
							$result["data"] = true;
						}
						// Query Failure
						else {
							$result["result"] = false;
							$result["message"] = "Error: failed to query database (machines.deleteItem)";
							$result["data"] = false;
						}
					}
					else {
						$result["result"] = false;
						$result["message"] = "Error: must be an admin (machines.deleteItem)";
						$result["data"] = false;
					}
				}
				else {
					$result["result"] = false;
					$result["message"] = "Error: only accepts POST requests (machines.deleteItem)";
					$result["data"] = false;
				}
				break;
			/*
			*	machines.addItem (POST) - Add a new item to the database
			*
			*	Example URL: api/v1/machines/addItem/name/Sugarfizz/price/50
			*
			*	Expected Parameters: 
			*	- name: Name of the new item
			*	- price: Price of the new item 
			*
			*	Return Data:
			*	- True for success, false for failure
			*
			*	{
			*		"result": true,
			*		"message": "Success (machines.addItem)",
			*		"data": true
			*	}
			*/
			case "addItem":
				// Only accept POST requests
				if ($this->method == "POST") {
					// Only run if the user is an admin
					if ($this->admin) {
						// Make sure the necessary parameters were passed
						if (!array_key_exists("name", $this->args)) {
							$result["result"] = false;
							$result["message"] = "Error: name not provided (machines.addItem)";
							$result["data"] = false;
							break;
						}
						else if (!array_key_exists("price", $this->args)) {
							$result["result"] = false;
							$result["message"] = "Error: price not provided (machines.addItem)";
							$result["data"] = false;
							break;
						}
						// Form the SQL query
						$sql = "INSERT INTO drink_items (item_name, item_price) VALUES (:name, :price)";
						$params["name"] = $this->args["name"];
						$params["price"] = $this->args["price"];
						// Make the query
						$query = db_insert($sql, $params);
						// Query Success
						if ($query) {
							$result["result"] = true;
							$result["message"] = "Success (machines.addItem)";
							$result["data"] = db_last_insert_id();
						}
						// Query Failure
						else {
							$result["result"] = false;
							$result["message"] = "Error: failed to query database (machines.addItem)";
							$result["data"] = false;
						}
					}
					else {
						$result["result"] = false;
						$result["message"] = "Error: must be an admin (machines.addItem)";
						$result["data"] = false;
					}
				}
				else {
					$result["result"] = false;
					$result["message"] = "Error: only accepts POST requests (machines.addItem)";
					$result["data"] = false;
				}
				break;
			/*
			*	Base case - no specific API method called
			*/
			default:
				$result["result"] = false;
				$result["message"] = "Invalid API method call (machines)";
				$result["data"] = false;
				break;
		}
		// Return the response data
		return $result;
	}

	// Temps endpoint, get temperature data for the drink machines
	protected function temps() {
		// Create an array to store response data
		$result = array();
		// Create an array to store parameters for SQL queries
		$params = array();
		// Determine the specific method to call
		switch($this->verb) {
			/*
			*	temps.getDataOne (GET) - Get temperature data for one drink machine
			*
			*	Example URL: api/v1/temps/getDataOne/machineId/2/
			*
			*	Expected Parameters: 
			*	- machineId: ID of the drink machine 
			*	- limit: How many results to return, default 300 (optional)
			*
			*	Return Data:
			*	- JSON object with arrays of temperatures and times
			*
			*	{
			*		"result": true,
			*		"message": "Success (temps.getDataOne)",
			*		"data": {
			*			"temp": [39.200000762939, ...],
			*			"time": ["2013-11-11 21:36:17", ...]
			*		}
			*	}
			*/
			case "getDataOne":
				if ($this->method == "GET") {
					// Check for the required machineId 
					if (!array_key_exists("machineId", $this->args)) {
						$result["result"] = false;
						$result["message"] = "Error: machineId not provided (temps.getDataOne)";
						$result["data"] = false;
						break;
					}
					// Set how many results to grab
					$limit = 300;
					if (array_key_exists("limit", $this->args)) {
						$limit == $this->args["limit"];
					}
					// Form the SQL query
					$sql = "SELECT t.machine_id, t.time, t.temp, m.display_name 
							FROM temperature_log as t, machines as m
							WHERE t.machine_id = m.machine_id AND t.machine_id = :machineId
							ORDER BY t.time DESC LIMIT :limit";
					$params["limit"] = $limit;
					$params["machineId"] = $this->args["machineId"];

					// Query the database
					$query = db_select($sql, $params);	

					// Query success
					if ($query) {
						$data = array();
						for ($i = count($query) - 1; $i >= 0; $i--) {
							//$data["temp"][] = $temp["temp"];
							//$data["time"][] = $temp["time"];
							$data["temp"][] = (float) $query[$i]["temp"];
							$data["time"][] = $query[$i]["time"];
						}
						$result["result"] = true;
						$result["message"] = "Success (temps.getDataOne)";
						$result["data"] = $data;
					}
					// Query failure
					else {
						$result["result"] = false;
						$result["message"] = "Error: failed to query database (temps.getDataOne)";
						$result["data"] = $query;
					}		
				}
				else {
					$result["result"] = false;
					$result["message"] = "Error: only accepts GET requests (temps.getDataOne)";
					$result["data"] = false;
				}
				break;
			default:
				$result["result"] = false;
				$result["message"] = "Invalid API method call (temps)";
				$result["data"] = false;
				break;
		}
		// Return the response data
		return $result;
	}
}

?>