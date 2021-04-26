<?php

////////////////////////////////////////////////////////////////////////
//                                                                    //
//                            jcqtypes.php                            //
//                                                                    //
//                                                                    //
//                                                                    //
// Definition of the JCQTypes class, which provides static methods    //
// for normalizing and validating various common data types.          //
//                                                                    //
////////////////////////////////////////////////////////////////////////

/*
 * If this script was invoked directly by a client browser, return a 404
 * error to hide it.
 * 
 * This script may only be used when included from other PHP scripts.
 */
if (__FILE__ === $_SERVER['SCRIPT_FILENAME']) {
  http_response_code(404);
  header('Content-Type: text/plain');
  echo "Error 404: Not Found\n";
  exit;
}

////////////////////////////////////////////////////////////////////////
//                                                                    //
//                        JCQServerType class                         //
//                                                                    //
////////////////////////////////////////////////////////////////////////

class JCQServerType {
  
  // Parsed fields
  //
  private $m_domain;  // string or empty string
  private $m_ipv4;    // string or empty string
  private $m_port;    // string or empty string
  private $m_hasip;   // boolean
  
  /*
   * Construct a new instance by passing a string to parse.
   * 
   * Clients are recommended to use the static functions of JCQTypes
   * instead of directly using this class.
   * 
   * If a non-string parameter is passed, the effect is the same as
   * passing an empty string.
   * 
   * Note that this constructor will trim the given string of leading
   * and trailing whitespace.  If you need to validate a string as-is,
   * use JCQTypes::blankCheck() before passing the string to this
   * function to make sure the string isn't altered during parsing.
   * 
   * Parameters:
   * 
   *   $str : string | mixed - the string to parse
   */
  public function __construct($str) {
    
    // Initialize fields
    $this->m_domain = '';
    $this->m_ipv4 = '';
    $this->m_port = '';
    $this->m_hasip = false;
    
    // If non-string passed, replace with empty string
    if (is_string($str) !== true) {
      $str = '';
    }
    
    // Trim the string parameter
    $str = trim($str);
    
    // Look for a colon to see if there is a port parameter
    $pi = strpos($str, ':');
    if ($pi !== false) {
      // Colon present, so colon and everything after it is the port
      // field
      $this->m_port = substr($str, $pi);
      
      // If colon is not first character, drop the colon and everything
      // after it from the string; else, change string to empty string
      if ($pi > 0) {
        $str = substr($str, 0, $pi);
      } else {
        $str = '';
      }
      
    } else {
      // No colon present, so store empty string in port field
      $this->m_port = '';
    }
    
    // Check whether all characters in the string are decimal digits or
    // dots, and count the number of dots if everything is a decimal
    // digit or dot
    $all_num = true;
    $has_num = false;
    $dot_count = 0;
    $slen = strlen($str);
    for($i = 0; $i < $slen; $i++) {
      
      // Get current character code
      $c = ord($str[$i]);
      
      // Check what character is
      if (($c >= ord('0')) && ($c <= ord('9'))) {
        // Decimal digit, so set the has_num flag
        $has_num = true;
        
      } else if ($c === ord('.')) {
        // Dot, so increase dot count
        $dot_count++;
        
      } else {
        // Not a decimal digit or dot, so clear all_num flag and leave
        // loop
        $all_num = false;
        break;
      }
    }
    
    // Store string as an IP address and set hasip flag if everything in
    // the string is either a decimal digit or a dot, there is at least
    // one decimal digit, and there are exactly three dots; else, store
    // as a domain name
    if ($all_num && $has_num && ($dot_count === 3)) {
      // IP address
      $this->m_ipv4 = $str;
      $this->m_hasip = true;
      
    } else {
      // Domain name
      $this->m_domain = $str;
      $this->m_hasip = false;
    }
  }
  
  /*
   * Normalize the fields within this object.
   */
  public function normalize() {
    
    // Normalize either the IPv4 address or the domain
    if ($this->m_hasip) {
      // Normalize the IPv4 address -- just use the blankCheck()
      // function on the IP address and clear it to blank if the check
      // fails
      if (JCQTypes::blankCheck($this->m_ipv4) !== true) {
        $this->m_ipv4 = '';
      }
      
    } else {
      // Normalize the domain -- first use the blankCheck() function and
      // clear to an empty string if this fails; otherwise, use
      // normDomain()
      if (JCQTypes::blankCheck($this->m_domain) === true) {
        $this->m_domain = JCQTypes::normDomain($this->m_domain);
      } else {
        $this->m_domain = '';
      }
    }
    
    // If port is just the colon, change it to an empty string; else,
    // normalize non-empty ports
    if (strlen($this->m_port) < 2) {
      $this->m_port = '';
    
    } else {
      // At least two characters in the port string, so we have to
      // normalize it -- begin by extracting everything after the colon
      $str = substr($this->m_port, 1);
      
      // Check that everything is a decimal digit and count the number
      // of leading zeroes
      $all_digits = true;
      $leading = true;
      $lead_zero = 0;
      $slen = strlen($str);
      for($x = 0; $x < $slen; $x++) {
        
        // Get numeric value of current character
        $c = ord($str[$x]);
        
        // Check that it is a digit
        if (($c < ord('0')) || ($c > ord('9'))) {
          $all_digits = false;
          break;
        }
        
        // If non-zero digit, clear leading mode
        if ($c > ord('0')) {
          $leading = false;
        }
        
        // If still in leading mode, increase lead zero count
        if ($leading) {
          $lead_zero++;
        }
      }
      
      // Only proceed with normalizing port string if everything after
      // the colon is a digit; else, leave port as-is
      if ($all_digits) {
      
        // If leading zero count equals the number of digits, then
        // decrease it by one so that one zero will be left over
        if ($lead_zero >= $slen) {
          $lead_zero--;
        }
      
        // If there are leading zeroes, trim them
        if ($lead_zero > 0) {
          $str = substr($str, $lead_zero);
        }
        
        // Reassemble the port as a colon followed by the number trimmed
        // of leading zeros
        $this->m_port = ':' . $str;
      }
    }
  }
  
  /*
   * Validate the fields within this object.
   * 
   * If the allowip parameter is true, then IPv4 addresses will be
   * allowed.  If it is false, then IPv4 addresses will not validate.
   * If it is non-boolean then this function will return false.
   * 
   * Note that during construction of the object, leading and trailing
   * whitespace is trimmed.  Therefore, if you want to validate a string
   * as-is, you should use blankCheck() on the string before passing it
   * to the constructor.
   * 
   * Parameters:
   * 
   *   $allowip : boolean | mixed - true to allow IPv4 addresses, false
   *   to not allow them
   * 
   * Return:
   * 
   *   true if validation passes, false if validation fails
   */
  public function check($allowip) {
    
    // Return false if parameter is not boolean
    if (is_bool($allowip) !== true) {
      return false;
    }
    
    // If parameter is not true and this object has an IPv4 address,
    // then fail
    if (($allowip !== true) && $this->m_hasip) {
      return false;
    }
    
    // Validate either the domain name or the IPv4 addresses, depending
    // on which the object has
    if ($this->m_hasip) {
      // IPv4 address validation -- for purposes of validation, make a
      // copy of the IPv4 address and add a dot to the end of it
      $istr = $this->m_ipv4 . '.';
      
      // Keep validating while the address copy is not empty
      $dot_count = 0;
      while (strlen($istr) > 0) {
      
        // Get the location of the next dot
        $di = strpos($istr, '.');
        if ($di === false) {
          // Since the last character of the string is a dot, this
          // shouldn't happen -- if it does for some reason, validation
          // fails
          return false;
        }
        
        // Increase the dot count; if greater than four (because we
        // added a dot to the end) then the IPv4 address is not valid
        $dot_count++;
        if ($dot_count > 4) {
          return false;
        }
        
        // If next dot is at beginning of string, it means either that
        // there was a dot at the beginning of the IPv4 address or there
        // were two dots in a row; neither case is valid
        if ($di < 1) {
          return false;
        }
        
        // Extract the numeric field
        $nf = substr($istr, 0, $di);
        
        // If numeric field more than three digits, fail
        $slen = strlen($nf);
        if ($slen > 3) {
          return false;
        }
        
        // If numeric field more than one digit, first digit may not be
        // zero
        if (($slen > 1) && ($nf[0] === '0')) {
          return false;
        }
        
        // Make sure all characters are digits
        for ($i = 0; $i < $slen; $i++) {
          $c = ord($nf[$i]);
          if (($c < ord('0')) || ($c > ord('9'))) {
            return false;
          }
        }
        
        // Make sure value doesn't exceed 255
        if (intval($nf) > 255) {
          return false;
        }
        
        // Drop numeric field and dot from the string copy
        $istr = substr($istr, $slen + 1);
      }
      
    } else {
      // Domain name validation
      if (JCQTypes::checkDomain($this->m_domain) !== true) {
        return false;
      }
    }
    
    // Validate port string if not empty
    $slen = strlen($this->m_port);
    if ($slen > 0) {
    
      // Must be at least two characters
      if ($slen < 2) {
        return false;
      }
      
      // First character must be colon
      if (($this->m_port)[0] !== ':') {
        return false;
      }
      
      // Second character may not be zero
      if (($this->m_port)[1] === '0') {
        return false;
      }
    
      // Everything after first character must be decimal digit
      for($x = 1; $x < $slen; $x++) {
        $c = ord(($this->m_port)[$x]);
        if (($c < ord('0')) || ($c > ord('9'))) {
          return false;
        }
      }
    }
    
    // If we got all the way here, return true
    return true;
  }
  
  /*
   * Clear the port value to empty.
   * 
   * If the object was already normalized, the object after this
   * operation will still be normalized.
   * 
   * If the object was already valid, the object after this operation
   * will still be valid.
   */
  public function clearPort() {
    $this->m_port = '';
  }
  
  /*
   * Get the domain stored in this object.
   * 
   * If an IPv4 address is stored in this object, empty string is
   * returned by this function.
   * 
   * The return value is not guaranteed to be valid.
   * 
   * Return:
   * 
   *   the domain, or empty string if there is no domain
   */
  public function getDomain() {
    if ($this->m_hasip) {
      return '';
    } else {
      return $this->m_domain;
    }
  }
  
  /*
   * Get the IPv4 address stored in this object.
   * 
   * If a domain is stored in this object, empty string is returned by
   * this function.
   * 
   * The return value is not guaranteed to be valid.
   * 
   * Return:
   * 
   *   the IPv4 address, or empty string if there is no IP address
   */
  public function getIPv4() {
    if ($this->m_hasip) {
      return $this->m_ipv4;
    } else {
      return '';
    }
  }
  
  /*
   * Get the port component stored in this object.
   * 
   * If no port component is stored, empty string is returned by this
   * function.
   * 
   * The port component is a string that begins with a colon and is
   * followed by decimal digits.  However, the return value of this
   * function is not guaranteed to be valid.
   * 
   * Return:
   * 
   *   the port component string, or empty string if there is no port
   */
  public function getPort() {
    return $this->m_port;
  }
  
  /*
   * Return whether an IPv4 address is stored within this object.
   * 
   * Return:
   * 
   *   true if an IPv4 address is stored; false if a domain is stored
   */
  public function hasIPv4() {
    return $this->m_hasip;
  }
  
  /*
   * Compose the full server string.
   * 
   * This is a combination of either the domain name or the IPv4
   * address, followed by the port (if there is such a description).
   * 
   * The return value is not guaranteed to be valid.
   * 
   * Return:
   * 
   *   the composed server string
   */
  public function getFull() {
    if ($this->m_hasip) {
      return $this->m_ipv4 . $this->m_port;
    } else {
      return $this->m_domain . $this->m_port;
    }
  }
}

////////////////////////////////////////////////////////////////////////
//                                                                    //
//                          JCQURLType class                          //
//                                                                    //
////////////////////////////////////////////////////////////////////////

class JCQURLType {
  
  // Parsed fields
  //
  private $m_protocol;  // string
  private $m_server;    // JCQServerType
  private $m_path;      // string
  private $m_query;     // string
  private $m_frag;      // string
  
  /*
   * Construct a new instance by passing a string to parse.
   * 
   * Clients are recommended to use the static functions of JCQTypes
   * instead of directly using this class.
   * 
   * If a non-string parameter is passed, the effect is the same as
   * passing an empty string.
   * 
   * Note that this constructor will trim the given string of leading
   * and trailing whitespace.  If you need to validate a string as-is,
   * use JCQTypes::blankCheck() before passing the string to this 
   * function to make sure the string isn't altered during parsing.
   * 
   * Parameters:
   * 
   *   $str : string | mixed - the string to parse
   */
  public function __construct($str) {
    
    // Initialize fields
    $this->m_protocol = '';
    $this->m_server = new JCQServerType('');
    $this->m_path = '';
    $this->m_query = '';
    $this->m_frag = '';
    
    // If non-string passed, replace with empty string
    if (is_string($str) !== true) {
      $str = '';
    }
    
    // Trim the string parameter
    $str = trim($str);
    
    // Figure out where the :// is within the URL
    $pend = strpos($str, '://');
    
    // Only proceed if the :// substring was found
    if ($pend !== false) {
    
      // Find the length of the protocol substring
      $pend = $pend + 3;
    
      // Extract the protocol and drop from string
      $this->m_protocol = substr($str, 0, $pend);
      if (strlen($str) > $pend) {
        $str = substr($str, $pend);
      } else {
        $str = '';
      }
      
      // Now that protocol has been removed, figure out where the next
      // slash / character is
      $trail = strpos($str, '/');
      
      // Figure out the server portion of the string and drop it
      $server_portion = NULL;
      if ($trail === false) {
        // No further slash was found, so the whole rest of the string
        // is the server portion
        $server_portion = $str;
        $str = '';
        
      } else {
        // Slash was found, so everything up to but excluding slash is
        // server portion
        if ($trail > 0) {
          $server_portion = substr($str, 0, $trail);
        } else {
          $server_portion = '';
        }
        
        // Drop server portion from string
        if ($trail > 0) {
          $str = substr($str, $trail);
        }
      }
      
      // Check that server portion is not whitespace padded, setting it
      // to empty if it is; otherwise, construct server object from it
      if (JCQTypes::blankCheck($server_portion)) {
        // No whitespace padding, so construct server object
        $this->m_server = new JCQServerType($server_portion);
        
      } else {
        // Whitespace padding, which is invalid, so blank server portion
        $server_portion = '';
      }
      
      // Find the query and fragment characters, if present
      $query_i = false;
      $frag_i = false;
      
      $query_i = strpos($str, '?');
      if ($query_i === false) {
        // No ? was found, so search for fragment in whole string
        $frag_i = strpos($str, '#');
        
      } else {
        // ? was found, so search for fragment beginning there
        $frag_i = strpos($str, '#', $query_i);
      }
      
      // If fragment was found, extract it and drop from string
      if ($frag_i !== false) {
        $this->m_frag = substr($str, $frag_i);
        if ($frag_i > 0) {
          $str = substr($str, 0, $frag_i);
        } else {
          $str = '';
        }
      }
      
      // If query was found, extract it and drop from string
      if ($query_i !== false) {
        $this->m_query = substr($str, $query_i);
        if ($query_i > 0) {
          $str = substr($str, 0, $query_i);
        } else {
          $str = '';
        }
      }
      
      // Everything that remains is the path
      $this->m_path = $str;
    }
  }
  
  /*
   * Normalize the fields within this object.
   */
  public function normalize() {
    
    // Normalize the protocol by converting to lowercase
    if (strlen($this->m_protocol) > 0) {
      $this->m_protocol = strtolower($this->m_protocol);
    }
    
    // Normalize the server object
    ($this->m_server)->normalize();
    
    // If the server object has a port, and that port is the default
    // port for the protocol, drop the port
    $port = ($this->m_server)->getPort();
    if (strlen($port) > 0) {
      if ((($this->m_protocol === 'http://') &&
            ($port === ':80')) ||
          (($this->m_protocol === 'https://') &&
            ($port === ':443'))) {
        ($this->m_server)->clearPort();
      }
    }
    
    // If path is empty, set it to /
    if (strlen($this->m_path) < 1) {
      $this->m_path = '/';
    }
    
    // Check for whitespace padding on path; if present, set path to
    // empty (which will not validate); if not present, use normResPath
    // to normalize it
    if (JCQTypes::blankCheck($this->m_path)) {
      $this->m_path = JCQTypes::normResPath($this->m_path);
    } else {
      $this->m_path = '';
    }
    
    // Normalize query and fragment the same way
    for($i = 0; $i < 2; $i++) {
      
      // Get query or fragment
      $str = NULL;
      if ($i === 0) {
        $str = $this->m_query;
      } else {
        $str = $this->m_frag;
      }
      
      // If field one character long, clear to empty
      if (strlen($str) < 2) {
        $str = '';
      }
      
      // If field still not empty, proceed with normalization
      if (strlen($str) > 0) {
      
        // Get everything beyond the first character
        $payload = substr($str, 1);
        
        // Check for whitespace padding (which is not allowed), and if
        // it is present in remainder string, set remainder string to %
        // (which is not valid and will not normalize)
        if (JCQTypes::blankCheck($payload) !== true) {
          $payload = '%';
        }
        
        // Normalize the remainder text
        $payload = JCQTypes::normPathText($payload);
        
        // Combine with the appropriate first character to get the
        // normalized result
        if ($i === 0) {
          $str = '?' . $payload;
        } else {
          $str = '#' . $payload;
        }
      }
      
      // Store normalized query or fragment
      if ($i === 0) {
        $this->m_query = $str;
      } else {
        $this->m_frag = $str;
      }
    }
  }
  
  /*
   * Validate the fields within this object.
   * 
   * If the allowip parameter is true, then IPv4 addresses will be
   * allowed.  If it is false, then IPv4 addresses will not validate.
   * If it is non-boolean then this function will return false.
   * 
   * Note that during construction of the object, leading and trailing
   * whitespace is trimmed.  Therefore, if you want to validate a string
   * as-is, you should use blankCheck() on the string before passing it
   * to the constructor.
   * 
   * Parameters:
   * 
   *   $allowip : boolean | mixed - true to allow IPv4 addresses, false
   *   to not allow them
   * 
   * Return:
   * 
   *   true if validation passes, false if validation fails
   */
  public function check($allowip) {
    
    // Return false if parameter is not boolean
    if (is_bool($allowip) !== true) {
      return false;
    }
    
    // Check that protocol is HTTP or HTTPS in lowercase
    if (($this->m_protocol !== 'http://') &&
        ($this->m_protocol !== 'https://')) {
      return false;
    }
    
    // Check the server object
    if (($this->m_server)->check($allowip) !== true) {
      return false;
    }
    
    // Check the resource path
    if (JCQTypes::checkResPath($this->m_path) !== true) {
      return false;
    }
    
    // If query not empty, check it
    if (strlen($this->m_query) > 0) {
      
      // Query must be at least two characters if not empty
      if (strlen($this->m_query) < 2) {
        return false;
      }
      
      // First character must be ?
      if (($this->m_query)[0] !== '?') {
        return false;
      }
      
      // Check payload
      if (JCQTypes::checkPathText(
              substr($this->m_query, 1)) !== true) {
        return false;
      }
    }
    
    // If fragment not empty, check it
    if (strlen($this->m_frag) > 0) {
      
      // Fragment must be at least two characters if not empty
      if (strlen($this->m_frag) < 2) {
        return false;
      }
      
      // First character must be #
      if (($this->m_frag)[0] !== '#') {
        return false;
      }
      
      // Check payload
      if (JCQTypes::checkPathText(
              substr($this->m_frag, 1)) !== true) {
        return false;
      }
    }
    
    // If we got here, validation passed
    return true;
  }
  
  /*
   * Get the protocol string stored in this object.
   * 
   * Empty string may be returned by this function.  The return value of
   * this function is not guaranteed to be valid.
   * 
   * Return:
   * 
   *   the protocol string
   */
  public function getProtocol() {
    return $this->m_protocol;
  }
  
  /*
   * Get the server object stored in this object.
   * 
   * The return value of this function is not guaranteed to be valid.
   * However, it is always non-NULL.
   * 
   * Note that a reference to the internal server object is returned.
   * If changes are made to the returned object, they change this URL
   * object, too.
   * 
   * Return:
   * 
   *   the server object, or type JCQServerType
   */
  public function getServer() {
    return $this->m_server;
  }
  
  /*
   * Get the path string stored in this object.
   * 
   * Empty string may be returned by this function.  The return value of
   * this function is not guaranteed to be valid.
   * 
   * Return:
   * 
   *   the path string
   */
  public function getPath() {
    return $this->m_path;
  }
  
  /*
   * Get the query string stored in this object.
   * 
   * This includes the opening ? character, unless the query was not
   * present, in which case the return value is empty string.
   * 
   * Empty string may be returned by this function.  The return value of
   * this function is not guaranteed to be valid.
   * 
   * Return:
   * 
   *   the query string
   */
  public function getQuery() {
    return $this->m_query;
  }
  
  /*
   * Get the fragment string stored in this object.
   * 
   * This includes the opening # character, unless the fragment was not
   * present, in which case the return value is empty string.
   * 
   * Empty string may be returned by this function.  The return value of
   * this function is not guaranteed to be valid.
   * 
   * Return:
   * 
   *   the fragment string
   */
  public function getFragment() {
    return $this->m_frag;
  }
  
  /*
   * Compose the full URL string.
   * 
   * This is a combination of all the fields in proper order.
   * 
   * The return value is not guaranteed to be valid.
   * 
   * Return:
   * 
   *   the composed URL string
   */
  public function getFull() {
    return $this->m_protocol .
            ($this->m_server)->getFull() .
            $this->m_path .
            $this->m_query .
            $this->m_frag;
  }
}

////////////////////////////////////////////////////////////////////////
//                                                                    //
//                           JCQTypes class                           //
//                                                                    //
////////////////////////////////////////////////////////////////////////

class JCQTypes {
  
  // Private constructor so no instances can be constructed
  //
  private function __construct() { }

  // Constants for use by xmlUniString function
  //
  const CDATA  = 1;
  const SQUOTE = 2;
  const DQUOTE = 3;
  
  /*
   * Returns true only if the given parameter is a non-empty string that
   * neither begins nor ends with whitespace.
   * 
   * Whitespace is defined equivalently to the PHP trim() function, with
   * space and \t \n \r \0 \v as whitespace characters.
   * 
   * Parameters:
   * 
   *   $str : string | mixed - the value to check
   * 
   * Return:
   * 
   *   true only if given value is a non-empty string that neither
   *   begins nor ends with whitespace; false otherwise
   */
  public static function blankCheck($str) {
    
    // Result begins at false
    $result = false;
    
    // Only proceed if parameter is a string
    if (is_string($str)) {
      
      // Only proceed if parameter is not empty
      $slen = strlen($str);
      if ($slen > 0) {
        
        // Get first and last characters
        $c_first = $str[0];
        $c_last = $str[$slen - 1];
        
        // Only set result to true if first and last characters are not
        // whitespace
        $result = true;
        for($i = 0; $i < 2; $i++) {
        
          // Get first or last character
          $c = NULL;
          if ($i === 0) {
            $c = $c_first;
          } else {
            $c = $c_last;
          }
          
          // If first or last character is whitespace, set result to
          // false and leave loop; else, leave result set to true
          if (($c === ' ') || ($c === "\t") ||
              ($c === "\n") || ($c === "\r") ||
              ($c === "\0") || ($c === "\v")) {
            $result = false;
            break;
          }
        }
      }
    }
    
    // Return result
    return $result;
  }
  
  /*
   * Given a leading byte in a UTF-8 code, determine how many bytes the
   * codepoint is encoded with in UTF-8.
   * 
   * false is returned if the given byte value is not a valid leading
   * byte.  Else, the return value is in range 1-4.
   * 
   * Parameters:
   * 
   *   $i : integer - the value of the first byte in a UTF-8 codepoint
   * 
   * Return:
   * 
   *   integer counting number of bytes in UTF-8 encoding, or false if
   *   not a valid lead byte
   */
  private static function utfLen($i) {
    
    // Check parameter type
    if (is_int($i) !== true) {
      throw new Exception('jcqtypes-' . strval(__LINE__));
    }
    
    // Determine result, with false used as default if not valid
    $result = false;
    if (($i >= 0) && ($i <= 0x7f)) {
      $result = 1;
    
    } else if (($i >= 0xc0) && ($i <= 0xdf)) {
      $result = 2;
    
    } else if (($i >= 0xe0) && ($i <= 0xef)) {
      $result = 3;
      
    } else if (($i >= 0xf0) && ($i <= 0xf7)) {
      $result = 4;
    }
    
    // Return result
    return $result;
  }
  
  /*
   * Given a binary string containing a single UTF-8 codepoint, decode
   * the codepoint value.
   * 
   * You can use the utfLen() function to determine the number of bytes
   * in a UTF-8 encoding of a codepoint given the first byte of an
   * encoded codepoint.  If the length of the given string does not
   * exactly match the length of the encoded UTF-8 codepoint, this
   * function will fail.
   * 
   * This function will also fail on overlong encodings and surrogate
   * codepoints.
   * 
   * If the function fails, false is returned.  An Exception is thrown
   * if a non-string is passed.
   * 
   * Parameters:
   * 
   *   $str : string - a single encoded UTF-8 codepoint
   * 
   * Return:
   * 
   *   an integer representing the Unicode codepoint value, or false if
   *   decoding failed
   */
  private static function utfOrd($str) {
    
    // Check parameter type
    if (is_string($str) !== true) {
      throw new Exception('jcqtypes-' . strval(__LINE__));
    }
    
    // Get string length
    $slen = strlen($str);
    
    // Fail if empty string
    if ($slen < 1) {
      return false;
    }
    
    // Get encoded length from lead byte and make sure given string has
    // this length
    $elen = self::utfLen(ord($str[0]));
    if ($elen === false) {
      return false;
    }
    if ($elen !== $slen) {
      return false;
    }
    
    // Decode depending on length of encoding
    $result = false;
    switch ($elen) {
      case 1:
        // 1-byte encoding
        $result = ord($str[0]);
        break;
        
      case 2:
        // 2-byte encoding
        $result = ord($str[0]) & 0x1f;
        $result = ($result << 6) | (ord($str[1]) & 0x3f);
        break;
      
      case 3:
        // 3-byte encoding
        $result = ord($str[0]) & 0x0f;
        $result = ($result << 6) | (ord($str[1]) & 0x3f);
        $result = ($result << 6) | (ord($str[2]) & 0x3f);
        break;
      
      case 4:
        // 4-byte encoding
        $result = ord($str[0]) & 0x07;
        $result = ($result << 6) | (ord($str[1]) & 0x3f);
        $result = ($result << 6) | (ord($str[2]) & 0x3f);
        $result = ($result << 6) | (ord($str[3]) & 0x3f);
        break;
      
      default:
        // Shouldn't happen
        throw new Exception('jcqtypes-' . strval(__LINE__));
    }
    
    // Check range to prevent overlong encodings
    if ($elen === 2) {
      if ($result < 0x80) {
        return false;
      }
      
    } else if ($elen === 3) {
      if ($result < 0x800) {
        return false;
      }
      
    } else if ($elen >= 4) {
      if ($result < 0x10000) {
        return false;
      }
    }
    
    // Check range for surrogates
    if (($result >= 0xd800) && ($result <= 0xdfff)) {
      return false;
    }
    
    // Return result
    return $result;
  }
  
  /*
   * Check whether a given Unicode codepoint is on the "bad codepoint"
   * list defined by the XML 1.0 standard as either being forbidden or
   * not recommended.
   * 
   * Parameters:
   * 
   *   $i : integer - the codepoint to check
   * 
   * Return:
   * 
   *   true if bad codepoint, false otherwise
   */
  private static function isBadCodepoint($i) {
    
    // Check type
    if (is_int($i) !== true) {
      throw new Exception('jcqtypes-' . strval(__LINE__));
    }
    
    // Check range
    $result = false;
    if ((($i >= 0x80) && ($i <= 0x84)) ||
        (($i >= 0x86) && ($i <= 0x9f)) ||
        (($i >= 0xd800) && ($i <= 0xdfff)) ||
        (($i >= 0xfdd0) && ($i <= 0xfdef)) ||
        (($i >= 0xfffe) && ($i <= 0xffff)) ||
        (($i >= 0x1fffe) && ($i <= 0x1ffff)) ||
        (($i >= 0x2fffe) && ($i <= 0x2ffff)) ||
        (($i >= 0x3fffe) && ($i <= 0x3ffff)) ||
        (($i >= 0x4fffe) && ($i <= 0x4ffff)) ||
        (($i >= 0x5fffe) && ($i <= 0x5ffff)) ||
        (($i >= 0x6fffe) && ($i <= 0x6ffff)) ||
        (($i >= 0x7fffe) && ($i <= 0x7ffff)) ||
        (($i >= 0x8fffe) && ($i <= 0x8ffff)) ||
        (($i >= 0x9fffe) && ($i <= 0x9ffff)) ||
        (($i >= 0xafffe) && ($i <= 0xaffff)) ||
        (($i >= 0xbfffe) && ($i <= 0xbffff)) ||
        (($i >= 0xcfffe) && ($i <= 0xcffff)) ||
        (($i >= 0xdfffe) && ($i <= 0xdffff)) ||
        (($i >= 0xefffe) && ($i <= 0xeffff)) ||
        (($i >= 0xffffe) && ($i <= 0xfffff)) ||
        ($i >= 0x10fffe)) {
      $result = true;
    }
    
    // Return result
    return $result;
  }
  
  /*
   * Check whether the given code corresponds to one of the "unsafe"
   * characters.
   * 
   * Pass an integer character code; non-integers will always result in
   * a false return.
   * 
   * Parameters:
   * 
   *   $i : integer | mixed - the character code to check
   * 
   * Return:
   * 
   *   true if character code is for an unsafe character, false in all
   *   other cases
   */
  private static function isUnsafeCode($i) {
    
    // Set result to false
    $result = false;
    
    // Only proceed if integer passed
    if (is_int($i)) {
      
      // Check for "unsafe" character code
      if (($i === 0x22) || 
          ($i === 0x23) ||
          ($i === 0x25) ||
          ($i === 0x2f) ||
          ($i === 0x3c) ||
          ($i === 0x3e) ||
          ($i === 0x3f) ||
          (($i >= 0x5b) && ($i <= 0x5e)) ||
          ($i === 0x60) ||
          (($i >= 0x7b) && ($i <= 0x7d))) {
        $result = true;
      }
    }
    
    // Return result
    return $result;
  }
  
  /*
   * Normalize a string of "path text."
   * 
   * This function does NOT guarantee that the returned string is valid
   * path text.
   * 
   * Parameters:
   * 
   *   $str : string | mixed - the path text to normalize
   * 
   * Return:
   * 
   *   the normalized path text string
   */
  public static function normPathText($str) {
    // Begin with an empty result
    $result = '';
    
    // Only proceed if parameter is a string; else, return empty string
    if (is_string($str)) {
      
      // Trim leading and trailing whitespace from the input string
      $str = trim($str);
      
      // Keep processing until no more % characters are left
      for ($i = strpos($str, '%');
            $i !== false;
            $i = strpos($str, '%')) {
        
        // If i is not the start of the string, transfer everything up
        // to i to the result
        if ($i > 0) {
          $result = $result . substr($str, 0, $i);
          $str = substr($str, $i);
        }
        
        // % escape is now at start of str; if there are less than three
        // characters left in str, then transfer rest of str to result
        // and leave loop
        if (strlen($str) < 3) {
          $result = $result . $str;
          $str = '';
          break;
        }
        
        // % escape is at start of str and there are at least three
        // characters, so get the first and second character codes after
        // the escape
        $cd1 = ord($str[1]);
        $cd2 = ord($str[2]);
        
        // Convert both characters to their numeric value according to
        // base-16 encoding, setting them to -1 if they are not a valid
        // base-16 digit
        for($j = 0; $j < 2; $j++) {
          
          // Get either first or second digit code
          $d = NULL;
          if ($j === 0) {
            $d = $cd1;
          } else {
            $d = $cd2;
          }
          
          // Convert to numeric base-16 value, or -1 if not a recognized
          // base-16 digit
          if (($d >= ord('0')) && ($d <= ord('9'))) {
            $d = $d - ord('0');
            
          } else if (($d >= ord('A')) && ($d <= ord('F'))) {
            $d = $d - ord('A') + 10;
            
          } else if (($d >= ord('a')) && ($d <= ord('f'))) {
            $d = $d - ord('a') + 10;
            
          } else {
            // Not a recognized base-16 digit
            $d = -1;
          }
          
          // Store to either first or second digit
          if ($j === 0) {
            $cd1 = $d;
          } else {
            $cd2 = $d;
          }
        }
        
        // Different handling depending on whether both digits are
        // base-16 digits or not
        if (($cd1 >= 0) && ($cd2 >= 0)) {
          
          // Both digits valid base-16, so get the full encoded value
          $encv = ($cd1 * 16) + $cd2;
          
          // Start the needenc flag at false
          $needenc = false;
          
          // Determine whether the encoded value really needs to be
          // percent-encoded
          if ($encv < 0x21) {
            // Control character, so encode
            $needenc = true;
            
          } else if ($encv > 0x7e) {
            // Control character or extended character, so encode
            $needenc = true;
            
          } else if (self::isUnsafeCode($encv)) {
            // "Unsafe" character, so encode
            $needenc = true;
            
          } else {
            // All other cases do NOT need encoding
            $needenc = false;
          }
          
          // Add either an uppercase-base-16-encoded escape or the
          // literal character, depending on whether encoding is really
          // needed
          if ($needenc) {
            // Need encoding, so translate encoded value into uppercase
            // base-16 digits
            $cd1 = intdiv($encv, 16);
            $cd2 = $encv % 16;
            for($j = 0; $j < 2; $j++) {
              
              // Get first or second digit
              $d = NULL;
              if ($j === 0) {
                $d = $cd1;
              } else {
                $d = $cd2;
              }
              
              // Convert digit to character code
              if ($d < 10) {
                $d = $d + ord('0');
              } else {
                $d = ($d - 10) + ord('A');
              }
              
              // Convert character code to string
              $d = chr($d);
              
              // Set first or second digit string
              if ($j === 0) {
                $cd1 = $d;
              } else {
                $cd2 = $d;
              }
            }
            
            // Append percent-encoded result
            $result = $result . '%' . $cd1 . $cd2;
            
          } else {
            // Don't need encoding, so just append literal character
            $result = $result . chr($encv);
          }
          
          // Drop the percent escape from the source string
          if (strlen($str) > 3) {
            // There's more in the source string after the escape, so
            // drop the escape and continue
            $str = substr($str, 3);
          
          } else {
            // Percent escape was last thing in string, so leave loop
            // after dropping it
            $str = '';
            break;
          }
          
        } else {
          // At least one digit is not a valid base-16 digit, so the
          // escape is not valid; transfer just the % sign to result and
          // continue loop
          $result = $result . '%';
          $str = substr($str, 1);
        }
      }
      
      // If anything remains in the string, transfer it to the result
      if (strlen($str) > 0) {
        $result = $result . $str;
        $str = '';
      }
      
      // Replace / and ? in path text with percent escapes
      $result = str_replace('/', '%2F', $result);
      $result = str_replace('?', '%3F', $result);
    }
    
    // Return result
    return $result;
  }
  
  /*
   * Validate a normalized string of "path text."
   * 
   * You should normalize the path text using normPathText() before
   * passing to this function, else non-normalized text may fail
   * validation.
   * 
   * Parameters:
   * 
   *   $str : string | mixed - the normalized path text to validate
   * 
   * Return:
   * 
   *   true if normalized path text is valid, false if it is not
   */
  public static function checkPathText($str) {
    
    // Begin with result of false
    $result = false;
    
    // Only proceed if a string was passed
    if (is_string($str)) {
      
      // Set result to true
      $result = true;
      
      // Go through and check each character (empty strings are valid)
      $slen = strlen($str);
      for($x = 0; $x < $slen; $x++) {
      
        // Get current character code
        $c = ord($str[$x]);

        // Different handling for % and other characters
        if ($c === ord('%')) {
          
          // % character, so first of all verify that at least two
          // characters after it
          if ($slen - $x < 3) {
            // % character has less than two characters after it
            $result = false;
            break;
          }
          
          // Get the two character codes after it
          $cd1 = ord($str[$x + 1]);
          $cd2 = ord($str[$x + 2]);
          
          // Translate each character to numeric value, allowing ONLY
          // *uppercase* base-16 digits, and writing -1 if not a valid
          // *uppercase* base-16 digit
          for($y = 0; $y < 2; $y++) {
            
            // Get current digit
            $d = NULL;
            if ($y === 0) {
              $d = $cd1;
            } else {
              $d = $cd2;
            }
            
            // Translate according to uppercase-only base-16, or -1
            if (($d >= ord('0')) && ($d <= ord('9'))) {
              $d = $d - ord('0');
            
            } else if (($d >= ord('A')) && ($d <= ord('F'))) {
              $d = $d - ord('A') + 10;
            
            } else {
              $d = -1;
            }
            
            // Store current digit
            if ($y === 0) {
              $cd1 = $d;
            } else {
              $cd2 = $d;
            }
          }
          
          // If either digit not valid, result is invalid
          if (($cd1 < 0) || ($cd2 < 0)) {
            $result = false;
            break;
          }
          
          // Form complete character code
          $encv = ($cd1 * 16) + $cd2;
          
          // Check that encoded value is control code, extended code, or
          // unsafe code
          if (($encv >= 0x21) && ($encv <= 0x7e) &&
              (self::isUnsafeCode($encv) === false)) {
            // Unnecessary percent encoding, so fail
            $result = false;
            break;
          }
          
        } else {
          // Not a % character, so we just need to check that it is in
          // range [0x21, 0x7e] AND that it is not an "unsafe" character
          if (($c < 0x21) || ($c > 0x7e) ||
                self::isUnsafeCode($c)) {
            // Invalid character present
            $result = false;
            break;
          }
        }
      }
    }
    
    // Return result
    return $result;
  }
  
  /*
   * Normalize a resource path string.
   * 
   * This function does NOT guarantee that the returned string is a
   * valid resource path.
   * 
   * Parameters:
   * 
   *   $str : string | mixed - the resource path to normalize
   * 
   * Return:
   * 
   *   the normalized resource path string
   */
  public static function normResPath($str) {

    // If passed a non-string, return an empty string
    if (is_string($str) !== true) {
      return '';
    }
    
    // If there are any literal ? or # characters in the resource path,
    // return an empty string
    if ((strpos($str, '?') !== false) ||
        (strpos($str, '#') !== false)) {
      return '';
    }
    
    // Trim leading and trailing whitespace
    $str = trim($str);
    
    // Begin with empty result
    $result = '';
    
    // Keep processing while there are / characters
    for($i = strpos($str, '/');
        $i !== false;
        $i = strpos($str, '/')) {
      
      // If / character is at beginning of string, then just transfer it
      // to result and continue if more string left or break if nothing
      // left
      if ($i < 1) {
        $result = $result . '/';
        if (strlen($str) > 1) {
          $str = substr($str, 1);
          continue;
        
        } else {
          $str = '';
          break;
        }
      }
      
      // The / character is not at beginning of string, so get the path
      // component, excluding the / character
      $pc = substr($str, 0, $i);
      
      // Only normalize path component if it passes blankCheck
      if (self::blankCheck($pc)) {
        // Passes blankCheck, so normalize the path component as path
        // text
        $pc = self::normPathText($pc);
      }
      
      // Add the possibly normalized path component to the result and
      // drop the path component from the input string
      $result = $result . $pc;
      $str = substr($str, $i);
      
      // Add the slash to the result
      $result = $result . '/';
      
      // Drop the slash from the string, leaving loop if this was the
      // last character in the string
      if (strlen($str) > 1) {
        // Slash not the last character in string
        $str = substr($str, 1);
        
      } else {
        // Slash was the last character in string
        $str = '';
        break;
      }
    }
    
    // No more / in input string -- if the input string has not been
    // reduced to empty, process the last part of it
    if (strlen($str) > 0) {
      // Only normalize last part of string if it passes blankCheck
      if (self::blankCheck($str)) {
        // Passes blankCheck, so normalize last part as path text
        $str = self::normPathText($str);
      }
      
      // Add possibly normalized last part to result and set input
      // string to blank
      $result = $result . $str;
      $str = '';
    }
    
    // Return result
    return $result;
  }
  
  /*
   * Validate a normalized resource path string.
   * 
   * You should normalize the resource path using normResPath() before
   * passing to this function, else non-normalized text may fail
   * validation.
   * 
   * Parameters:
   * 
   *   $str : string | mixed - the normalized resource path to validate
   * 
   * Return:
   * 
   *   true if normalized resource path is valid, false if it is not
   */
  public static function checkResPath($str) {
    
    // If non-string passed, return false
    if (is_string($str) === false) {
      return false;
    }
    
    // Path must be at least one character and first character must be
    // the /
    if (strlen($str) < 1) {
      return false;
    }
    if ($str[0] !== '/') {
      return false;
    }
    
    // If path is just / by itself, then it is valid
    if (strlen($str) === 1) {
      return true;
    }
    
    // If we got here then the path is at least two characters; drop the
    // opening / so we can check the rest of the path
    $str = substr($str, 1);
    
    // For the purposes of validation, add a terminating / to the end of
    // the path if it is not already present
    $slen = strlen($str);
    if ($str[$slen - 1] !== '/') {
      $str = $str . '/';
    }
    
    // We now have a sequence of path components, each terminated with a
    // forward slash / character; check all of them
    $result = true;
    while (strlen($str) > 0) {
      
      // Path string not empty yet, so figure out where this path
      // component ends
      $x = strpos($str, '/');
      if ($x === false) {
        // Shouldn't happen because we made sure that the last character
        // of the string is / -- just set result to false and leave loop
        // if we get here for some reason
        $result = false;
        break;
      }
      
      // If / occurs at start of string, we have empty path component,
      // which means there were two / characters in a row, which isn't
      // allowed, so set result to false and leave loop in this case
      if ($x < 1) {
        $result = false;
        break;
      }
      
      // Get the current path component
      $pc = substr($str, 0, $x);
      
      // If path component is . or .. then set result to false and leave
      // loop because we aren't allowing these relative path components
      if (($pc === '.') || ($pc === '..')) {
        $result = false;
        break;
      }
      
      // Check the path component as valid path text, setting result to
      // false and leaving loop if it is not
      if (self::checkPathText($pc) !== true) {
        $result = false;
        break;
      }
      
      // Path component successfully validated, so remove it from string
      $str = substr($str, $x);
      
      // Drop the / terminating character
      if (strlen($str) > 1) {
        $str = substr($str, 1);
      } else {
        $str = '';
      }
    }
    
    // Return result
    return $result;
  }
  
  /*
   * Normalize a domain string.
   * 
   * This function does NOT guarantee that the returned string is a
   * valid domain.
   * 
   * Parameters:
   * 
   *   $str : string | mixed - the domain to normalize
   * 
   * Return:
   * 
   *   the normalized domain string
   */
  public static function normDomain($str) {
    
    // Result starts out empty
    $result = '';
    
    // Only proceed if a string was passed
    if (is_string($str)) {
      // String was passed, so begin by trimming leading and trailing
      // whitespace
      $result = trim($str);
      
      // Only proceed further if domain is not empty
      $slen = strlen($result);
      if ($slen > 0) {
        
        // If last character is a period, drop it
        if ($result[$slen - 1] === '.') {
          if ($slen > 1) {
            $result = substr($result, 0, $slen - 1);
          } else {
            $result = '';
          }
        }
        
        // Only proceed further if domain is not empty
        if (strlen($result) > 0) {
          // Make all letters in the domain lowercase
          $result = strtolower($result);
        }
      }
    }
    
    // Return result
    return $result;
  }
  
  /*
   * Validate a normalized domain string.
   * 
   * You should normalize the domain using normDomain() before passing
   * to this function, else non-normalized text may fail validation.
   * 
   * Parameters:
   * 
   *   $str : string | mixed - the normalized domain to validate
   * 
   * Return:
   * 
   *   true if normalized domain is valid, false if it is not
   */
  public static function checkDomain($str) {
    
    // If non-string passed, return false
    if (is_string($str) !== true) {
      return false;
    }
    
    // Check the domain length
    $slen = strlen($str);
    if (($slen < 1) || ($slen > 255)) {
      return false;
    }
    
    // Check that there is at least one period in the domain, and the
    // last character is not a period
    $lastp = strrpos($str, '.');
    if ($lastp === false) {
      return false;
    }
    if ($lastp >= $slen - 1) {
      return false;
    }
    
    // Check that after the last dot, there is at least one character
    // that is not a decimal digit
    $upperpart = substr($str, $lastp + 1);
    $slu = strlen($upperpart);
    $foundit = false;
    for($x = 0; $x < $slu; $x++) {
      $c = ord($upperpart[$x]);
      if (($c < ord('0')) || ($c > ord('9'))) {
        $foundit = true;
        break;
      }
    }
    if ($foundit !== true) {
      return false;
    }
    
    // Add a dot to the end of the domain so that each domain component
    // ends with a dot
    $str = $str . '.';
    
    // Now go through and check each label
    $result = true;
    while (strlen($str) > 0) {
      
      // Get the next dot in the domain string
      $di = strpos($str, '.');
      if ($di === false) {
        // Shouldn't happen because last character of string is dot; if
        // we do get here somehow, fail
        $result = false;
        break;
      }
      
      // If next dot is at start of string, then either there was a dot
      // at the start of the domain or there were two dots in a row;
      // neither case is valid, so fail in either case
      if ($di < 1) {
        $result = false;
        break;
      }
      
      // Get the current label
      $lbl = substr($str, 0, $di);
      
      // Check length of label
      $llen = strlen($lbl);
      if ($llen > 63) {
        $result = false;
        break;
      }
      
      // Check that neither first nor last character of label is a
      // hyphen
      if (($lbl[0] === '-') || ($lbl[$llen - 1] === '-')) {
        $result = false;
        break;
      }
      
      // Check that each character of label is either a lowercase
      // letter, a number, or a hyphen
      for($i = 0; $i < $llen; $i++) {
      
        // Get current character numeric value
        $c = ord($lbl[$i]);
        
        // Check current character
        if ((($c < ord('a')) || ($c > ord('z'))) &&
            (($c < ord('0')) || ($c > ord('9'))) &&
            ($c !== ord('-'))) {
          $result = false;
          break;
        }
      }
      if ($result !== true) {
        break;
      }
      
      // Drop the label from the string
      $str = substr($str, $di);
      
      // Drop the dot, leaving loop if the dot was the last thing
      if (strlen($str) > 1) {
        $str = substr($str, 1);
      } else {
        $str = '';
        break;
      }
    }
    
    // Return result
    return $result;
  }
  
  /*
   * Normalize a URL string.
   * 
   * This function does NOT guarantee that the returned string is a
   * valid URL.
   * 
   * Parameters:
   * 
   *   $str : string | mixed - the URL to normalize
   * 
   * Return:
   * 
   *   the normalized URL string
   */
  public static function normURL($str) {
    
    // If non-string passed, return empty string
    if (is_string($str) !== true) {
      return '';
    }
    
    // Use the JCQURLType class to normalize
    $url = new JCQURLType($str);
    $url->normalize();
    return $url->getFull();
  }
  
  /*
   * Validate a normalized URL string.
   * 
   * You should normalize the URL using normURL() before passing to this
   * function, else non-normalized text may fail validation.
   * 
   * If the allowip parameter is true, then IPv4 addresses will be
   * allowed.  If it is false, then IPv4 addresses will not validate.
   * If it is non-boolean then this function will return false.
   * 
   * Parameters:
   * 
   *   $str : string | mixed - the normalized domain to validate
   * 
   *   $allowip : boolean | mixed - true to allow IPv4 addresses, false
   *   to not allow them
   * 
   * Return:
   * 
   *   true if normalized URL is valid, false if it is not
   */
  public static function checkURL($str, $allowip) {

    // If either parameter has wrong type, return false
    if ((is_string($str) !== true) || (is_bool($allowip) !== true)) {
      return false;
    }
   
    // Check for invalid whitespace padding first, before passing
    // through to URL object
    if (self::blankCheck($str) !== true) {
      return false;
    }
    
    // Use the URL object to validate
    $url = new JCQURLType($str);
    return $url->check($allowip);
  }
  
  /*
   * Normalize an email address.
   * 
   * This function does NOT guarantee that the returned string is a
   * valid email address.
   * 
   * Parameters:
   * 
   *   $str : string | mixed - the email address to normalize
   * 
   * Return:
   * 
   *   the normalized email address string
   */
  public static function normEmail($str) {
    
    // If non-string passed, return empty string
    if (is_string($str) !== true) {
      return '';
    }
    
    // Trim leading and trailing whitespace, and convert to lowercase
    $str = trim($str);
    $str = strtolower($str);
    
    // Find the first @ character, returning empty string if no such
    // character
    $atpos = strpos($str, '@');
    if ($atpos === false) {
      return '';
    }
    
    // Get local part and domain
    $lpart = NULL;
    if ($atpos > 0) {
      $lpart = substr($str, 0, $atpos);
    } else {
      $lpart = '';
    }
    
    $dpart = NULL;
    if ($atpos < strlen($str) - 1) {
      $dpart = substr($str, $atpos + 1);
    } else {
      $dpart = '';
    }
    
    // Check for whitespace padding, which is not allowed; return empty
    // string if either check fails
    if ((self::blankCheck($lpart) !== true) ||
        (self::blankCheck($dpart) !== true)) {
      return '';
    }
    
    // Local part needs no further normalization, but normalize domain
    $dpart = self::normDomain($dpart);
    
    // Reassemble the normalized email address
    return $lpart . '@' . $dpart;
  }
  
  /*
   * Validate a normalized email address.
   * 
   * You should normalize the email address using normEmail() before
   * passing to this function, else non-normalized text may fail
   * validation.
   * 
   * Parameters:
   * 
   *   $str : string | mixed - the normalized email address to validate
   * 
   * Return:
   * 
   *   true if normalized email address is valid, false if it is not
   */
  public static function checkEmail($str) {
    
    // If non-string passed, return false
    if (is_string($str) !== true) {
      return false;
    }
    
    // Check the email address length
    if (strlen($str) > 320) {
      return false;
    }
    
    // Find the first @ sign, failing if it does not exist
    $atpos = strpos($str, '@');
    if ($atpos === false) {
      return false;
    }
    
    // Get local part and domain
    $lpart = NULL;
    if ($atpos > 0) {
      $lpart = substr($str, 0, $atpos);
    } else {
      $lpart = '';
    }
    
    $dpart = NULL;
    if ($atpos < strlen($str) - 1) {
      $dpart = substr($str, $atpos + 1);
    } else {
      $dpart = '';
    }
    
    // Check for whitespace padding, which is not allowed
    if ((self::blankCheck($lpart) !== true) ||
        (self::blankCheck($dpart) !== true)) {
      return false;
    }
    
    // Check length of local part
    $slen = strlen($lpart);
    if (($slen < 1) || ($slen > 64)) {
      return false;
    }
    
    // Check characters of local part
    for($x = 0; $x < $slen; $x++) {
      
      // Get numeric code of current character
      $c = ord($str[$x]);
      
      // Check that character is valid
      if ((($c < ord('a')) || ($c > ord('z'))) &&
          (($c < ord('0')) || ($c > ord('9'))) &&
          ($c !== 0x21) &&
          (($c < 0x23) || ($c > 0x27)) &&
          (($c < 0x2a) || ($c > 0x2b)) &&
          (($c < 0x2d) || ($c > 0x2f)) &&
          ($c !== 0x3d) &&
          ($c !== 0x3f) &&
          (($c < 0x5e) || ($c > 0x60)) &&
          (($c < 0x7b) || ($c > 0x7e))) {
        return false;
      }
      
      // Extra checks if this is a dot character
      if ($c === ord('.')) {
      
        // Dot may be neither first nor last character
        if (($x < 1) || ($x >= $slen - 1)) {
          return false;
        }
        
        // Previous character may not also be dot
        if ($str[$x - 1] === '.') {
          return false;
        }
      }
    }
    
    // Check the domain part
    if (self::checkDomain($dpart) !== true) {
      return false;
    }
    
    // If we got here, email address is valid
    return true;
  }
  
  /*
   * Encode an integer timestamp value into an RFC-3339 string
   * representation.
   * 
   * Parameters:
   * 
   *   $ival : integer | mixed - the integer timestamp value to encode
   * 
   * Return:
   * 
   *   the string RFC-3339 representation, or false if the parameter was
   *   not valid
   */
  public static function encodeTime($ival) {
    
    // Fail if non-integer was passed or integer is less than one
    if (is_int($ival) !== true) {
      return false;
    }
    if ($ival < 1) {
      return false;
    }
    
    // Get the UTC timezone
    $tz = new DateTimeZone('UTC');
    
    // Get a new DateTime (default value of current time) with UTC time
    // zone
    $dt = new DateTime("now", $tz);
    
    // Set the DateTime to the given timestamp
    if ($dt->setTimestamp($ival) === false) {
      return false;
    }
    
    // Format the timestamp as RFC-3339
    return $dt->format(DATE_ATOM);
  }
  
  /*
   * Decode an RFC-3339 string representation into an integer timestamp
   * value.
   * 
   * Parameters:
   * 
   *   $sval : string | mixed - the RFC-3339 string representation
   * 
   * Return:
   * 
   *   the integer timestamp value, or false if the parameter was not
   *   valid
   */
  public static function decodeTime($sval) {
    
    // Fail if non-string was passed
    if (is_string($sval) !== true) {
      return true;
    }
    
    // Trim string and convert to uppercase
    $sval = trim($sval);
    $sval = strtoupper($sval);
    
    // Get the UTC timezone
    $tz = new DateTimeZone('UTC');
    
    // Get a new DateTime with the RFC-3339 format of the given string
    $dt = DateTime::createFromFormat(DATE_ATOM, $sval, $tz);
    if ($dt === false) {
      return false;
    }
    
    // Return the corresponding timestamp
    return $dt->getTimestamp();
  }
  
  /*
   * Normalize an atom.
   * 
   * This function does NOT guarantee that the returned string is a
   * valid atom.
   * 
   * Parameters:
   * 
   *   $str : string | mixed - the atom to normalize
   * 
   * Return:
   * 
   *   the normalized atom string
   */
  public static function normAtom($str) {
    
    // If non-string passed, return empty string
    if (is_string($str) !== true) {
      return '';
    }
    
    // Just trim leading and trailing whitespace
    return trim($str);
  }
  
  /*
   * Validate a normalized atom.
   * 
   * You should normalize the atom using normAtom() before passing to
   * this function, else non-normalized text may fail validation.
   * 
   * Parameters:
   * 
   *   $str : string | mixed - the normalized atom to validate
   * 
   * Return:
   * 
   *   true if normalized atom is valid, false if it is not
   */
  public static function checkAtom($str) {
    
    // If non-string passed, return false
    if (is_string($str) !== true) {
      return false;
    }
    
    // Make sure string not empty
    $slen = strlen($str);
    if ($slen < 1) {
      return false;
    }
    
    // Validate all characters
    for($x = 0; $x < $slen; $x++) {
      
      // Get current character code
      $c = ord($str[$x]);
      
      // If this is first character, make sure not a decimal digit
      if ($x < 1) {
        if (($c >= ord('0')) && ($c <= ord('9'))) {
          return false;
        }
      }
      
      // Check that character is alphanumeric or underscore
      if ((($c < ord('0')) || ($c > ord('9'))) &&
          (($c < ord('A')) || ($c > ord('Z'))) &&
          (($c < ord('a')) || ($c > ord('z'))) &&
          ($c !== ord('_'))) {
        return false;
      }
    }
    
    // If we got here, the atom name is valid
    return true;
  }
  
  /*
   * Convert a raw binary string to a Unicode string with backslash
   * escaping.
   * 
   * Parameters:
   * 
   *   $str : string - the binary string
   * 
   *   $multiline : boolean - true to allow for multi-line formatting
   *   codes, false to filter them out
   * 
   * Return:
   * 
   *   the converted Unicode string
   */
  public static function makeUniString($str, $multiline) {
    
    // Check parameter types
    if (is_string($str) !== true) {
      throw new Exception('jcqtypes-' . strval(__LINE__));
    }
    if (is_bool($multiline) !== true) {
      throw new Exception('jcqtypes-' . strval(__LINE__));
    }
    
    // First of all, escape backslash characters as \\
    $str = str_replace("\\", "\\\\", $str);
    
    // Second, convert HT to either \t or space depending on whether
    // multiline mode is enabled
    if ($multiline) {
      $str = str_replace("\t", "\\t", $str);
    } else {
      $str = str_replace("\t", " ", $str);
    }
    
    // Third, convert line breaks to either \n or space depending on
    // whether multiline mode is enabled
    $line_target = ' ';
    if ($multiline) {
      $line_target = "\\n";
    }
    $str = str_replace("\r\n", $line_target, $str);
    $str = str_replace("\r", $line_target, $str);
    $str = str_replace("\n", $line_target, $str);
    
    // Copy the string to $ostr and then transfer only characters that
    // are not ASCII controls back to $str
    $ostr = $str;
    $str = '';
    $slen = strlen($ostr);
    for($x = 0; $x < $slen; $x++) {
      
      // Get current character code
      $c = ord($ostr[$x]);
      
      // Transfer back to $str unless it is an ASCII control
      if (($c >= 0x20) && ($c !== 0x7f)) {
        $str = $str . chr($c);
      }
    }
    
    // If encoding isn't valid UTF-8, we need to convert it
    if (mb_check_encoding($str, 'UTF-8') !== true) {
      
      // We don't have a UTF-8 string, so assume we have a Windows-1252
      // string instead -- copy the string to $ostr and then transfer
      // only characters that are not undefined Windows-1252 characters
      // back to $str
      $ostr = $str;
      $str = '';
      $slen = strlen($ostr);
      for($x = 0; $x < $slen; $x++) {
        
        // Get current character code
        $c = ord($ostr[$x]);
        
        // Transfer back to $str unless it is an undefined Windows-1252
        // code
        if (($c !== 0x81) && ($c !== 0x8d) && ($c !== 0x8f) &&
            ($c !== 0x90) && ($c !== 0x9d)) {
          $str = $str . chr($c);
        }
      }
      
      // Convert Windows-1252 to UTF-8, which should always work since
      // we handled undefined characters above
      $str = mb_convert_encoding($str, 'UTF-8', 'Windows-1252');
      if ($str === false) {
        throw new Exception('jcqtypes-' . strval(__LINE__));
      }
    }
    
    // If the string has at least three bytes, we need to check for a
    // UTF-8 BOM and drop it if present
    if (strlen($str) >= 3) {
      if ((ord($str[0]) === 0xef) &&
          (ord($str[1]) === 0xbb) &&
          (ord($str[2]) === 0xbf)) {
        
        // First three bytes are a UTF-8 Byte Order Mark (BOM), so drop
        // it
        if (strlen($str) > 3) {
          $str = substr($str, 3);
        } else {
          $str = '';
        }
      }
    }
    
    // Copy string to $ostr and then transfer only UTF-8 codepoints that
    // are not "bad" codepoints back to $str
    $ostr = $str;
    $str = '';
    $slen = strlen($ostr);
    for($i = 0; $i < $slen; $i++) {
      
      // Get the length of the UTF-8 codepoint at the current position
      $ul = self::utfLen(ord($ostr[$i]));
      if ($ul === false) {
        throw new Exception('jcqtypes-' . strval(__LINE__));
      }
      
      // Make sure enough space left in the string
      if ($ul > $slen - $i) {
        throw new Exception('jcqtypes-' . strval(__LINE__));
      }
      
      // Extract the whole UTF-8 codepoint and advance $i additional
      // bytes if necessary
      $ucp = substr($ostr, $i, $ul);
      if ($ul > 1) {
        $i = $i + ($ul - 1);
      }
      
      // Get the codepoint
      $cp = self::utfOrd($ucp);
      if ($cp === false) {
        throw new Exception('jcqtypes-' . strval(__LINE__));
      }
      
      // Transfer whole UTF-8 codepoint, unless it is a "bad" codepoint
      if (self::isBadCodepoint($cp) === false) {
        $str = $str . $ucp;
      }
    }
    
    // Finally, normalize the string to NFC
    $str = Normalizer::normalize($str, Normalizer::FORM_C);
    if ($str === false) {
      throw new Exception('jcqtypes-' . strval(__LINE__));
    }
    
    // Return the string
    return $str;
  }
  
  /*
   * Check that the given string is a valid Unicode string (with
   * backslash escaping).
   * 
   * Parameters:
   * 
   *   $str : string | mixed - the string value to check
   * 
   * Return:
   * 
   *   true if valid string, false if not
   */
  public static function checkUniString($str) {
    
    // Fail if not a string
    if (is_string($str) !== true) {
      return false;
    }
    
    // Fail if not UTF-8
    if (mb_check_encoding($str, 'UTF-8') !== true) {
      return false;
    }
    
    // Fail if not normalized
    if (Normalizer::isNormalized($str, Normalizer::FORM_C) !== true) {
      return false;
    }
    
    // Check the string codepoint by codepoint
    $result = true;
    $slen = strlen($str);
    for($x = 0; $x < $slen; $x++) {
    
      // Figure out UTF-8 encoding length at current location
      $elen = self::utfLen(ord($str[$x]));
      if ($elen === false) {
        // Invalid UTF-8
        $result = false;
        break;
      }
      
      // Make sure whole codepoint is present
      if ($elen > $slen - $x) {
        $result = false;
        break;
      }
      
      // Get the codepoint and advance x extra if multibyte
      $ecp = substr($str, $x, $elen);
      if ($elen > 1) {
        $x = $x + ($elen - 1);
      }
      
      // Get the Unicode codepoint
      $cp = self::utfOrd($ecp);
      if ($cp === false) {
        $result = false;
        break;
      }
      
      // Check that not ASCII control
      if (($cp < 0x20) || ($cp === 0x7f)) {
        $result = false;
        break;
      }
      
      // Check that not a "bad" codepoint
      if (self::isBadCodepoint($cp)) {
        $result = false;
        break;
      }
      
      // If an ASCII backslash, extra processing
      if ($cp === ord("\\")) {
        
        // Escaping backslash may not be last character
        if ($x >= $slen - 1) {
          $result = false;
          break;
        }
        
        // Next byte must be another backslash or ASCII lowercase t or n
        $c = $str[$x + 1];
        if (($c !== "\\") && ($c !== 't') && ($c !== 'n')) {
          $result = false;
          break;
        }
        
        // If we got here, then skip over the escape character
        $x++;
      }
    }
    
    // Return result
    return $result;
  }
  
  /*
   * Verify that a given string is a valid backslash-escaped Unicode
   * string and then decode the backslashes.
   * 
   * An Exception is thrown if str doesn't pass checkUniString.
   * 
   * multiline is true if tabs (\t) and line breaks (\n) should be
   * produced, false if they should be decoded to spaces.
   * 
   * Parameters:
   * 
   *   $str : string - the escaped Unicode string to decode
   * 
   *   $multiline : boolean - true to allow multi-line strings, false
   *   for single-line strings
   * 
   * Return:
   * 
   *   the decoded Unicode string
   */
  public static function decodeUniString($str, $multiline) {
    
    // Check the string parameter
    if (self::checkUniString($str) !== true) {
      throw new Exception('jcqtypes-' . strval(__LINE__));
    }
    
    // Check the multiline parameter
    if (is_bool($multiline) !== true) {
      throw new Exception('jcqtypes-' . strval(__LINE__));
    }
    
    // Decode escapes
    if ($multiline) {
      $str = str_replace("\\t", "\t", $str);
      $str = str_replace("\\n", "\n", $str);
      
    } else {
      $str = str_replace("\\t", ' ', $str);
      $str = str_replace("\\n", ' ', $str);
    }
    
    $str = str_replace("\\\\", "\\", $str);
    
    // Return decoded result
    return $str;
  }
  
  /*
   * Package a Unicode string so that it has single quotes escaped as
   * two single quotes in a row, necessary for SQL string literal 
   * values.
   * 
   * This does NOT include the surrounding single-quote characters for
   * the literal.
   * 
   * checkUniString() is performed on the parameter, and if it fails,
   * an exception is thrown.
   * 
   * Parameters:
   * 
   *   $str : string - the escaped Unicode string to package
   * 
   * Return:
   * 
   *   the SQL-packaged Unicode string
   */
  public static function sqlUniString($str) {
    // Check parameter
    if (self::checkUniString($str) !== true) {
      throw new Exception('jcqtypes-' . strval(__LINE__));
    }
    
    // Escape all single quotes
    $str = str_replace("'", "''", $str);
    
    // Return result
    return $str;
  }
  
  /*
   * Escape a Unicode string for an XML attribute value.
   * 
   * This does NOT include the surrounding quote characters for
   * attribute values.  If you are making an attribute value, be sure
   * that the loc parameter matches the kind of quotes you will be
   * using.
   * 
   * The loc parameter indicates in which context the string data will
   * be used.  It is one of the following constants, defined by this
   * class:
   * 
   *   JCQTypes::CDATA  - used within regular character data
   *   JCQTypes::SQUOTE - used within single-quoted attribute values
   *   JCQTypes::DQUOTE - used within double-quoted attribute values
   * 
   * checkUniString() is performed on the parameter, and if it fails, an
   * Exception is thrown.  An exception is also thrown if the loc
   * parameter is not one of the recognized constant values.
   * 
   * Parameters:
   * 
   *   $str : string - the Unicode string value to escape
   * 
   *   $loc : integer - a constant value indicating where the text will
   *   be used within the XML document
   * 
   * Return:
   * 
   *   the XML-escaped string
   */
  public static function xmlUniString($str, $loc) {
    
    // Check parameters
    if (self::checkUniString($str) !== true) {
      throw new Exception('jcqtypes-' . strval(__LINE__));
    }
    if (is_int($loc) !== true) {
      throw new Exception('jcqtypes-' . strval(__LINE__));
    }
    if (($loc !== self::CDATA) && ($loc !== self::SQUOTE) &&
        ($loc !== self::DQUOTE)) {
      throw new Exception('jcqtypes-' . strval(__LINE__));
    }
    
    // Escape & first
    $str = str_replace('&', '&amp;', $str);
    
    // Escape < >
    $str = str_replace('<', '&lt;', $str);
    $str = str_replace('>', '&gt;', $str);
    
    // Escape appropriate quote, if any
    if ($loc === self::DQUOTE) {
      $str = str_replace('"', '&quot;', $str);
    } else if ($loc === self::SQUOTE) {
      $str = str_replace("'", '&apos;', $str);
    }
    
    // Return result
    return $str;
  }
  
  /*
   * Normalize a filename.
   * 
   * This function does NOT guarantee that the returned string is a
   * valid filename.
   * 
   * Parameters:
   * 
   *   $str : string | mixed - the filename to normalize
   * 
   * Return:
   * 
   *   the normalized filename string
   */
  public static function normFilename($str) {
    
    // If non-string passed, return empty string
    if (is_string($str) !== true) {
      return '';
    }
    
    // Just trim leading and trailing whitespace and convert to
    // lowercase
    return trim(strtolower($str));
  }
  
  /*
   * Validate a normalized filename.
   * 
   * You should normalize the filename using normFilename() before
   * passing to this function, else non-normalized text may fail
   * validation.
   * 
   * Parameters:
   * 
   *   $str : string | mixed - the normalized filename to validate
   * 
   * Return:
   * 
   *   true if normalized filename is valid, false if it is not
   */
  public static function checkFilename($str) {
    
    // If non-string passed, return false
    if (is_string($str) !== true) {
      return false;
    }
    
    // Get and check length of filename
    $slen = strlen($str);
    if (($slen < 1) || ($slen > 31)) {
      return false;
    }
    
    // Check each character
    for($i = 0; $i < $slen; $i++) {
      
      // Get current character code
      $c = ord($str[$i]);
      
      // Check character range
      if ((($c < ord('a')) || ($c > ord('z'))) &&
          (($c < ord('0')) || ($c > ord('9'))) &&
          ($c !== ord('_')) && ($c !== ord('.'))) {
        return false;
      }
    }
    
    // Check if there is a dot
    $dloc = strpos($str, '.');
    
    // If there is a dot, check if there is more than one dot (which is
    // not allowed), and also check that dot is neither first nor last
    // character
    if ($dloc !== false) {
      if ($dloc !== strrpos($str, '.')) {
        return false;
      }
      if (($dloc < 1) || ($dloc >= $slen - 1)) {
        return false;
      }
    }
    
    // Determine filename proper
    $fpr = NULL;
    if ($dloc !== false) {
      $fpr = substr($str, 0, $dloc);
    } else {
      $fpr = $str;
    }
    
    // If filename proper is 3 characters, check for special device name
    if (strlen($fpr) === 3) {
      if (($fpr === 'aux') ||
          ($fpr === 'con') ||
          ($fpr === 'nul') ||
          ($fpr === 'prn')) {
        return false;
      }
    }
    
    // If filename proper if 4 characters, check for special device name
    if (strlen($fpr) === 4) {
      // Only proceed with check if 4th character is digit 1-9
      $fc = ord($fpr[3]);
      if (($fc >= ord('1')) && ($fc <= ord('9'))) {
        // Now look at only first three characters
        $fpr = substr($fpr, 0, 3);
        if (($fpr === 'com') || ($fpr === 'lpt')) {
          return false;
        }
      }
    }
    
    // If we got here, filename is okay
    return true;
  }
}
