<?php
//---------------------------------------------------------------------------------------
//
// This script fixes the Wordpress Theme Options table when you've been dumb and run a
// search and replace on the table, and now have broken the string encoding in
// the options.
//
// How to use:
//    1. Put this file next to your wp-config.php file
//    2. Run it through the command line, or open it in the broser
//
//---------------------------------------------------------------------------------------

// Connect to the WordPress database
include("wp-config.php");
$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
if (!$link)
	die('Could not connect: ' . mysql_error());
$db_selected = mysql_select_db(DB_NAME, $link);

// Select all theme options
$result = mysql_query("SELECT * FROM ".$table_prefix."options WHERE 1;", $link);

while($row = mysql_fetch_assoc($result))
{
	$val = $row['option_value'];

	// Check if this option is serialized
	$start = substr($val, 0, 10);
	if(preg_match("/a:\d+:\{/i", $start))
	{
		// Find all strings
		preg_match_all("/s:(\d+):\"([^(?<!\\)\")]+)\"/", $val, $matches);
		for($i = 0; $i < sizeof($matches[1]); $i++)
		{
			// Check that the encoded string length is equal to the actual string length
			if(strlen($matches[2][$i]) != intval($matches[1][$i], 10))
			{
				// If it doesn't match, fix it.
				$val = str_replace($matches[0][$i], "s:".strlen($matches[2][$i]).":\"".$matches[2][$i]."\"", $val);
			}
		}

		// Update the option
		$q = "UPDATE ".$table_prefix."options SET option_value = '".mysql_real_escape_string($val, $link)."' WHERE option_id = ".$row['option_id'].";";
		mysql_query($q, $link);
	}
}

mysql_close($link);

?>
