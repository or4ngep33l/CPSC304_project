<!--Test Oracle file for UBC CPSC 304
  Created by Jiemin Zhang, 2011
  Modified by Simona Radu and others
  This file shows the very basics of how to execute PHP commands
  on Oracle.  
  specifically, it will drop a table, create a table, insert values
  update values, and then query for values
 
  IF YOU HAVE A TABLE CALLED "tab1" IT WILL BE DESTROYED

  The script assumes you already have a server set up
  All OCI commands are commands to the Oracle libraries
  To get the file to work, you must place it somewhere where your
  Apache server can run it, and you must rename it to have a ".php"
  extension.  You must also change the username and password on the 
  OCILogon below to be your ORACLE username and password -->

<p>If you wish to reset the table, press the reset button. If this is the first time you're running this page, you MUST use reset</p>
<form method="POST" action="search.php">
   
  <p><input type="submit" value="Reset" name="reset"></p>
</form>

<!----> 
<p>Search for games within a certain genre </p>
<p>
  <font size="2"> Genre</font>
</p>

<form method="POST" action="search.php">
   <p>
      <input type="text" name="genre" size="10">
      <input type="submit" value="Search" name="genreSearch">
    </p>
</form>

<!----> 
<p>Search for games released by certain developers</p>
<p>
  <font size="2"> Developer's name</font>
</p>

<form method="POST" action="search.php">
   <p>
      <input type="text" name="devName" size="10">
      <input type="submit" value="Search" name="devSearch">
    </p>
</form>

<!----> 
<p>Search for games released between a given date range</p>
<p>
  <font size="2"> 
    From date&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    To date
  </font>
</p>

<form method="POST" action="search.php">
   <p>
      <input type="date" name="fromD" size="10">
      <input type="date" name="toD" size="10">
      <input type="submit" value="Search" name="dateSearch">
    </p>
</form>

<!----> 
<p> Update the name by inserting the old and new values below: </p>
<p><font size="2"> Old Name&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 
New Name&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Age</font></p>
<form method="POST" action="search.php">
<!--refresh page when submit-->

   <p><input type="text" name="oldName" size="6"><input type="text" name="newName" 
size="18"><input type="text" name="insAge" size="3">
<!--define two variables to pass the value-->
      
<input type="submit" value="update" name="updatesubmit"></p>
<input type="submit" value="run hardcoded queries" name="dostuff"></p>
</form>

<?php

//this tells the system that it's no longer just parsing 
//html; it's now parsing PHP

$success = True; //keep track of errors so it redirects the page only if there are no errors
$db_conn = OCILogon("ora_w7z0b", "a30166169", "dbhost.ugrad.cs.ubc.ca:1522/ug");

function executePlainSQL($cmdstr) { //takes a plain (no bound variables) SQL command and executes it
	//echo "<br>running ".$cmdstr."<br>";
	global $db_conn, $success;
	$statement = OCIParse($db_conn, $cmdstr); //There is a set of comments at the end of the file that describe some of the OCI specific functions and how they work

	if (!$statement) {
		echo "<br>Cannot parse the following command: " . $cmdstr . "<br>";
		$e = OCI_Error($db_conn); // For OCIParse errors pass the       
		// connection handle
		echo htmlentities($e['message']);
		$success = False;
	}

	$r = OCIExecute($statement, OCI_DEFAULT);
	if (!$r) {
		echo "<br>Cannot execute the following command: " . $cmdstr . "<br>";
		$e = oci_error($statement); // For OCIExecute errors pass the statementhandle
		echo htmlentities($e['message']);
		$success = False;
	} else {

	}
	return $statement;

}

function executeBoundSQL($cmdstr, $list) {
	/* Sometimes the same statement will be executed for several times ... only
	 the value of variables need to be changed.
	 In this case, you don't need to create the statement several times; 
	 using bind variables can make the statement be shared and just parsed once.
	 This is also very useful in protecting against SQL injection.  
      See the sample code below for how this functions is used */

	global $db_conn, $success;
	$statement = OCIParse($db_conn, $cmdstr);

	if (!$statement) {
		echo "<br>Cannot parse the following command: " . $cmdstr . "<br>";
		$e = OCI_Error($db_conn);
		echo htmlentities($e['message']);
		$success = False;
	}

	foreach ($list as $tuple) {
		foreach ($tuple as $bind => $val) {
			//echo $val;
			//echo "<br>".$bind."<br>";
			OCIBindByName($statement, $bind, $val);
			unset ($val); //make sure you do not remove this. Otherwise $val will remain in an array object wrapper which will not be recognized by Oracle as a proper datatype

		}
		$r = OCIExecute($statement, OCI_DEFAULT);
		if (!$r) {
			echo "<br>Cannot execute the following command: " . $cmdstr . "<br>";
			$e = OCI_Error($statement); // For OCIExecute errors pass the statement handle
			echo htmlentities($e['message']);
			echo "<br>";
			$success = False;
		}
	}
  return $statement;
}

function printResult($result) { //prints results from a select statement
	echo "<br>Found games that match your search<br>";
	echo "<table>";
	echo "<tr><th>Game ID</th><th>Name</th><th>Genre</th><th>Price</th><th>ReleaseDate</th><th>DevName</th></th><th>SalePrice</th></tr>";

	while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
		echo "<tr><td>" . $row[2] . "</td><td>" . $row[1] . "</td><td>" . $row[0] . "</td><td>" . $row[3] . "</td><td>" . $row[4] . "</td><td>" . $row[5] . "</td><td>" . $row[6] . "</td></tr>"; 
	}
	echo "</table>";

}

// Connect Oracle...
if ($db_conn) {

	if (array_key_exists('reset', $_POST)) {
		// Drop old table...
		echo "<br> dropping table <br>";
		executePlainSQL("Drop table tab1");

		// Create new table...
		echo "<br> creating new table <br>";
		executePlainSQL("create table tab1 (nid number, name varchar2(30), age number, primary key (nid))");
		OCICommit($db_conn);

	} else if (array_key_exists('genreSearch', $_POST)) {
		// Search the game table for specific genre 
		$tuple = array (":bind1" => $_POST['genre']);
		$alltuples = array ($tuple);
    $result = executeBoundSQL("select * from Games where genre=:bind1", $alltuples);
    printResult($result);
    OCICommit($db_conn);
    
	} else if (array_key_exists('devSearch', $_POST)) {
		// Search the game table for games that are published by the given developer
		$tuple = array (":bind1" => $_POST['devName']);
		$alltuples = array ($tuple);
    $result = executeBoundSQL("select * from Games where devName=:bind1", $alltuples);
    printResult($result);
    OCICommit($db_conn);
    
  } else if (array_key_exists('dateSearch', $_POST)) {
    // Search the game table for games that are published by whithin the given dates
    $tuple = array (
      ":bind1" => $_POST['fromD'],
      ":bind2" => $_POST['toD']
    );
    $alltuples = array ($tuple);
    $result = executeBoundSQL("select * from Games where SalePrice>=:bind1 and SalePrice<=:bind1", $alltuples);
    printResult($result);
    OCICommit($db_conn);

  } else if (array_key_exists('priceSearch', $_POST)) {
    // Search the game table for games that are published by whithin the given dates
    $tuple = array (
      ":bind1" => $_POST['fromP'],
      ":bind2" => $_POST['toP']
    );
    $alltuples = array ($tuple);
    $result = executeBoundSQL("select * from Games where ReleaseDate>=:bind1 and ReleaseDate<=:bind1", $alltuples);
    printResult($result);
    OCICommit($db_conn);

  } else if (array_key_exists('onSaleSearch', $_POST)) {
    // Search the game table for games that are on sale
    $result = executePlainSQL("select * from Games where SalePrice < Price");
    printResult($result);
    OCICommit($db_conn);
    
  }

	if ($_POST && $success) {
		//POST-REDIRECT-GET -- See http://en.wikipedia.org/wiki/Post/Redirect/Get
		header("location: search.php");
	} else {
		// Select data...
		$result = executePlainSQL("select * from games");
		printResult($result);
	}

	//Commit to save changes...
	OCILogoff($db_conn);
} else {
	echo "cannot connect";
	$e = OCI_Error(); // For OCILogon errors pass no handle
	echo htmlentities($e['message']);
}

/* OCILogon() allows you to log onto the Oracle database
     The three arguments are the username, password, and database.
     You will need to replace "username" and "password" for this to
     to work. 
     all strings that start with "$" are variables; they are created
     implicitly by appearing on the left hand side of an assignment 
     statement */
/* OCIParse() Prepares Oracle statement for execution
      The two arguments are the connection and SQL query. */
/* OCIExecute() executes a previously parsed statement
      The two arguments are the statement which is a valid OCI
      statement identifier, and the mode. 
      default mode is OCI_COMMIT_ON_SUCCESS. Statement is
      automatically committed after OCIExecute() call when using this
      mode.
      Here we use OCI_DEFAULT. Statement is not committed
      automatically when using this mode. */
/* OCI_Fetch_Array() Returns the next row from the result data as an  
     associative or numeric array, or both.
     The two arguments are a valid OCI statement identifier, and an 
     optinal second parameter which can be any combination of the 
     following constants:

     OCI_BOTH - return an array with both associative and numeric 
     indices (the same as OCI_ASSOC + OCI_NUM). This is the default 
     behavior.  
     OCI_ASSOC - return an associative array (as OCI_Fetch_Assoc() 
     works).  
     OCI_NUM - return a numeric array, (as OCI_Fetch_Row() works).  
     OCI_RETURN_NULLS - create empty elements for the NULL fields.  
     OCI_RETURN_LOBS - return the value of a LOB of the descriptor.  
     Default mode is OCI_BOTH.  */
?>

