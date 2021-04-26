<?php

////////////////////////////////////////////////////////////////////////
//                                                                    //
//                       test_types_submit.php                        //
//                                                                    //
//                                                                    //
//                                                                    //
// Test script for validating and normalizing Jacques-Types data      //
// types using the JCQTypes class static functions.                   //
//                                                                    //
// This script receives its input from test_types_form.html which     //
// must be in the same directory as this script to work correctly.    //
//                                                                    //
////////////////////////////////////////////////////////////////////////

// Import Jacques-Types data types module
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
                'test_types_form.html';
  
  // Redirect to form
  http_response_code(302);
  header("Location: $form_path");
  exit;
}

// Establish the output variables and set each to null to begin with
//
$result_inval = NULL;
$result_intype = NULL;
$result_valid = NULL;
$result_norm = NULL;

// Attempt to read the input value and the input type from the POSTed
// values
//
if (array_key_exists('val', $_POST)) {
  $result_inval = $_POST['val'];
}
if (array_key_exists('vtype', $_POST)) {
  $result_intype = $_POST['vtype'];
}

// Parse the input type, setting the vtype variable to the recognized
// value and the result_intype to a string representation or ? if not
// recognized
//
$vtype = NULL;
if (is_null($result_intype) === false) {
  if ($result_intype === 'path_text') {
    $vtype = 'path_text';
    $result_intype = 'Path text';
  
  } else if ($result_intype === 'res_path') {
    $vtype = 'res_path';
    $result_intype = 'Resource path';
  
  } else if ($result_intype === 'domain') {
    $vtype = 'domain';
    $result_intype = 'Domain';
  
  } else if ($result_intype === 'url') {
    $vtype = 'url';
    $result_intype = 'URL';
  
  } else if ($result_intype === 'email') {
    $vtype = 'email';
    $result_intype = 'E-mail address';
  
  } else if ($result_intype === 'time_str') {
    $vtype = 'time_str';
    $result_intype = 'Date/time string';
  
  } else if ($result_intype === 'atom') {
    $vtype = 'atom';
    $result_intype = 'Atom';
  
  } else if ($result_intype === 'filename') {
    $vtype = 'filename';
    $result_intype = 'Filename';
  
  } else {
    $vtype = NULL;
    $result_intype = '?';
  }
}

// Only proceed if we got both an input value AND a recognized type
//
if ((is_null($result_inval) === false) &&
    (is_null($vtype) === false)) {
  
  // Different handling based on the type -- do nothing if unrecognized
  // type
  if ($vtype === 'path_text') {
    // Path text
    $result_norm = JCQTypes::normPathText($result_inval);
    $result_valid = JCQTypes::checkPathText($result_norm);
    
  } else if ($vtype === 'res_path') {
    // Resource path
    $result_norm = JCQTypes::normResPath($result_inval);
    $result_valid = JCQTypes::checkResPath($result_norm);
    
  } else if ($vtype === 'domain') {
    // Domain
    $result_norm = JCQTypes::normDomain($result_inval);
    $result_valid = JCQTypes::checkDomain($result_norm);
    
  } else if ($vtype === 'url') {
    // URL
    $result_norm = JCQTypes::normURL($result_inval);
    $result_valid = JCQTypes::checkURL($result_norm, true);
    
  } else if ($vtype === 'email') {
    // Email address
    $result_norm = JCQTypes::normEmail($result_inval);
    $result_valid = JCQTypes::checkEmail($result_norm);
    
  } else if ($vtype === 'time_str') {
    // Date/time string
    $result_norm = JCQTypes::decodeTime($result_inval);
    if ($result_norm !== false) {
      $result_valid = true;
    } else {
      $result_valid = false;
    }
  
  } else if ($vtype === 'atom') {
    // Atom
    $result_norm = JCQTypes::normAtom($result_inval);
    $result_valid = JCQTypes::checkAtom($result_norm);
  
  } else if ($vtype === 'filename') {
    // Filename
    $result_norm = JCQTypes::normFilename($result_inval);
    $result_valid = JCQTypes::checkFilename($result_norm);
  }
  
  // If result was not valid, clear norm to -
  if ($result_valid !== true) {
    $result_norm = '-';
  }
  
  // Set string value of valid field
  if ($result_valid === true) {
    $result_valid = 'OK';
  } else if ($result_valid === false) {
    $result_valid = 'FAIL';
  } else {
    $result_valid = '?';
  }
}

// If any output variables are not set yet, set them to ?
//
if (is_null($result_inval)) {
  $result_inval = '?';
}
if (is_null($result_intype)) {
  $result_intype = '?';
}
if (is_null($result_valid)) {
  $result_valid = '?';
}
if (is_null($result_norm)) {
  $result_norm = '?';
}

// Before rendering the form, escape necessary characters in each output
// variable
//
$result_inval = htmlspecialchars(
                  $result_inval,
                  ENT_NOQUOTES | ENT_HTML5,
                  'UTF-8',
                  true);

$result_intype = htmlspecialchars(
                  $result_intype,
                  ENT_NOQUOTES | ENT_HTML5,
                  'UTF-8',
                  true);

$result_valid = htmlspecialchars(
                  $result_valid,
                  ENT_NOQUOTES | ENT_HTML5,
                  'UTF-8',
                  true);

$result_norm = htmlspecialchars(
                  $result_norm,
                  ENT_NOQUOTES | ENT_HTML5,
                  'UTF-8',
                  true);

?><!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8"/>
    <title>Types testing result - Jacques-Types</title>
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
    <h1>Types testing result</h1>
    <table>
      <tr><td colspan="2" class="tsec">Input received</td></tr>
      <tr>
        <th>Input value:</th>
        <td><?php echo $result_inval; ?></td>
      </tr>
      <tr>
        <th>Input type:</th>
        <td><?php echo $result_intype; ?></td>
      </tr>
      <tr><td colspan="2" class="tsec">Results</td></tr>
      <tr>
        <th>Validation:</th>
        <td><?php echo $result_valid; ?></td>
      </tr>
      <tr>
        <th>Normalized:</th>
        <td><?php echo $result_norm; ?></td>
      </tr>
    </table>
    <hr/>
    <p>
      <i>return to <a href="test_types_form.html">testing form</a></i>
    </p>
  </body>
</html>
