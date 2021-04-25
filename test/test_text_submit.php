<?php

////////////////////////////////////////////////////////////////////////
//                                                                    //
//                        test_text_submit.php                        //
//                                                                    //
//                                                                    //
//                                                                    //
// Test script for Unicode text functions defined in jcqtypes.php.    //
//                                                                    //
// This script receives its input from test_text_form.html which must //
// be in the same directory as this script to work correctly.         //
//                                                                    //
////////////////////////////////////////////////////////////////////////

// Import Jacques-Types module
//
require_once __DIR__ . '/jcqtypes.php';

// If we received something other than a POST, redirect to the
// submission form
//
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  
  // Determine if HTTPS was used for this request
  $uses_https = false;
  if (isset($_SERVER['HTTPS'])) {
    $uses_https = true;
  }
  
  // Determine protocol
  $prot = 'http://';
  if ($uses_https) {
    $prot = 'https://';
  }
  
  // Get the server name and script name
  $server_name = $_SERVER['SERVER_NAME'];
  $script_name = $_SERVER['SCRIPT_NAME'];
  
  // Get the index of the last / in the script name
  $last_sep = strrpos($script_name, '/');
  
  // If there is a / in the script name, the parent directory is
  // everything up to and including it; else, the parent directory is /
  $parent_dir = NULL;
  if ($last_sep !== false) {
    // Parent directory is everything up to and including last separator
    $parent_dir = substr($script_name, 0, $last_sep + 1);
    
  } else {
    // Didn't find any / so parent directory is root
    $parent_dir = '/';
  }
  
  // Form the path to the form
  $form_path = $prot . $server_name . $parent_dir .
                'test_text_form.html';
  
  // Redirect to form
  http_response_code(302);
  header("Location: $form_path");
  exit;
}


// Attempt to read the string from the POSTed value
//
$str = '';
if (array_key_exists('txt', $_POST)) {
  $str = $_POST['txt'];
}

// Convert to Unicode string
//
$str = JCQTypes::makeUniString($str, true);

// Do XML escaping
//
$str = JCQTypes::xmlUniString($str, JCQTypes::CDATA);

// Decode backslash codes
//
$str = JCQTypes::decodeUniString($str, true);

// Convert formatting codes
//
$str = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;", $str);
$str = str_replace("\n", "<br/>", $str);

?><!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8"/>
    <title>Text testing result - Jacques-Types</title>
    <meta name="viewport" 
        content="width=device-width, initial-scale=1.0"/>
    <style>

th {
  text-align: left;
  font-weight: bold;
}

td {
  padding-left: 1em;
}

.tsec {
  text-align: left;
  text-decoration: underline;
  padding-bottom: 0.5em;
  padding-top: 1.0em;
  padding-left: 0;
}

hr {
  margin-top: 2em;
}

    </style>
  </head>
  <body>
    <h1>Text testing result</h1>
    <p><?php echo $str; ?></p>
    <hr/>
    <p>
      <i>return to
        <a href="test_text_form.html">testing form</a></i>
    </p>
  </body>
</html>
