<?php
//**************************************************
// Edu-Stats v.2.0.1
// Academic Statistics Parser
// Google Scholar, Scopus, Publons
//
//
// Developed by Kaveh Bakhtiyari (https://bakhtiyari.com)
// v2.0.1: 12 February 2022
//**************************************************

error_reporting(E_ERROR | E_PARSE);
//error_reporting(E_ALL);
// Never expose errors (paths, internals) to visitors; log them instead
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set("log_errors", 1);
//ini_set("error_log", "php-error.log");

header('X-Content-Type-Options: nosniff');

$whitelist_urls = array("edu-stats.bakhtiyari.com", "edu-stats.kaveh.me", "kaveh.bakhtiyari.com", "wvvw.monataghavi.com");

// Note: the Referer header can be forged by non-browser clients, so this only
// limits casual embedding/hotlinking; it is not an authentication mechanism.
$validated = False;
if (isset($_SERVER['HTTP_REFERER']) && is_string($_SERVER['HTTP_REFERER'])){
	$host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
	if (is_string($host) && in_array(strtolower($host), $whitelist_urls, true)){
		$validated = True;
	}
}

if (!$validated)
{
	header('HTTP/1.0 403 Forbidden');
	header('Content-Type: text/plain; charset=utf-8');
	echo("Your access has been denied.");
	exit();
}

$EduStatsVersion = "2.0.1";

// Returns the query string parameter if it matches $pattern, "" if it is absent,
// and stops with HTTP 400 if it is present but invalid.
function get_param($name, $pattern){
	if (!isset($_GET[$name]) || $_GET[$name] === ''){
		return "";
	}
	$value = $_GET[$name];
	if (!is_string($value) || !preg_match($pattern, $value)){
		header('HTTP/1.0 400 Bad Request');
		header('Content-Type: text/plain; charset=utf-8');
		echo("Invalid '" . $name . "' parameter.");
		exit();
	}
	return $value;
}

// Scraped values are untrusted; only pass through finite numbers
function to_number($value, $default = -1){
	$value = trim((string)$value);
	if (is_numeric($value) && is_finite($value + 0)){
		return $value + 0;
	}
	return $default;
}

// Encodes a value as a safe JavaScript literal
function js_value($value){
	return json_encode($value, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES);
}

// Cookie jar is kept outside the web root so it cannot be downloaded
function cookie_file(){
	return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'edustats_cookie.txt';
}

function get_web_page($url, $post = false, $cookiesIn = '', $param = ''){
	$options = array(
	CURLOPT_POST           => $post,
	CURLOPT_RETURNTRANSFER => true,     // return web page
	CURLOPT_HEADER         => true,     //return headers in addition to content
	CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	CURLOPT_ENCODING       => "",       // handle all encodings
	CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	CURLOPT_CONNECTTIMEOUT => 10,       // timeout on connect
	CURLOPT_TIMEOUT        => 30,       // timeout on response
	CURLOPT_MAXREDIRS      => 5,        // stop after 5 redirects
	CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS, // HTTPS only, including redirects
	CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
	CURLINFO_HEADER_OUT    => true,
	CURLOPT_SSL_VERIFYPEER => true,     // verify SSL certificates
	CURLOPT_SSL_VERIFYHOST => 2,
	CURLOPT_VERBOSE        => false,
	CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2_0,
	CURLOPT_USERAGENT      => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.82 Safari/537.36",
	CURLOPT_COOKIE         => $cookiesIn,
	CURLOPT_COOKIEJAR      => cookie_file(),
	CURLOPT_COOKIEFILE     => cookie_file()
	);

	if (strlen($param) > 0){
		$options[CURLOPT_POSTFIELDS] = $param; //Set POST parameters
	}

	$ch      = curl_init($url);
	curl_setopt_array($ch, $options);
	$rough_content = curl_exec($ch);
	$err     = curl_errno($ch);
	$errmsg  = curl_error($ch);
	$header  = curl_getinfo($ch);
	curl_close($ch);

	if ($rough_content === false){
		$rough_content = '';
	}
	$header_content = substr($rough_content, 0, $header['header_size']);
	$body_content = trim(substr($rough_content, $header['header_size']));
	$pattern = "#set-cookie:\\s+(?<cookie>[^=]+=[^;]+)#m"; 
	preg_match_all($pattern, $header_content, $matches); 
	$cookiesOut = implode("; ", $matches['cookie']);
	
	$header['errno']   = $err;
	$header['errmsg']  = $errmsg;
	$header['headers']  = $header_content;
	$header['content'] = $body_content;
	$header['cookies'] = $cookiesOut;
	$header['rough'] = $rough_content;
	return $header;
}
	
//Google Scholar Variables
$GURL = '';
$GCitations = -1;
$GhIndex = -1;
$Gi10Index = -1;

//Scopus Variables
$ScopusURL = '';
$Scopus_hIndex = -1;
$Scopus_Citations = -1;
$Scopus_Documents = -1;
$Scopus_CitationDocuments = -1;

//Publons Variables
$PublonsURL = '';
$publons_averagePerItem = -1;
$publons_averagePerYear = -1;
$publons_hIndex = -1;
$publons_timesCited = -1;
$publons_numPublicationsInWosCc = -1;

// Validate all input before any outbound request is made
$gscholar_id = get_param('google', '/^[A-Za-z0-9_-]{1,32}\z/');
$scopus_id = get_param('scopus', '/^[0-9]{1,20}\z/');
$publons_id = get_param('publons', '/^[A-Za-z0-9-]{1,32}\z/');
$prefix = get_param('prefix', '/^[A-Za-z_][A-Za-z0-9_]{0,9}\z/'); // must form a valid JS identifier

$output = "javascript";
if(isset($_GET['output']) && is_string($_GET['output']) && !empty($_GET['output'])){
	$output = $_GET['output'];
}

if (strlen($gscholar_id) > 0){
	google($gscholar_id);
}
if (strlen($scopus_id) > 0){
	scopus($scopus_id);
}
if (strlen($publons_id) > 0){
	publons($publons_id);
}

switch ($output){
	case "json":
		header('Content-Type: application/json; charset=utf-8');
		JSONAll();
		break;
	case "javascript":
	default:
		header('Content-Type: application/javascript; charset=utf-8');
		ScriptAll();
}


function google($GScholar){
	
	global $GURL;
	global $GCitations;
	global $GhIndex;
	global $Gi10Index;
	
	$GURL = "https://scholar.google.com/citations?user=" . rawurlencode($GScholar) . "&hl=en";
	
	$PageContent = get_web_page($GURL, false);
	$Cookies = $PageContent['cookies'];
	
	$PageContent = get_web_page($GURL, false, $Cookies);
	$Code = $PageContent['content'];
		
	$SearchFROM = "<td class=\"gsc_rsb_std\">";
	$strTO = "</td>";
	$startPoint = 1;
	
	for ($i = 1; $i <= 5; $i++){
		$dblFROM = strpos($Code,$SearchFROM,$startPoint);
		if ($dblFROM === false){
			break;
		}
		$dblFROM = $dblFROM + strlen($SearchFROM);
		$dblTO = strpos($Code,$strTO,$dblFROM);
		if ($dblTO === false){
			break;
		}
		$startPoint = $dblTO;
		$dblTO = $dblTO - $dblFROM;
		
		$strResult = Trim(substr($Code,$dblFROM,$dblTO));
		if (strlen($strResult) > 0){
			$strConverted = str_replace(",","",$strResult);
			if ($i == 1){
				$GCitations = to_number($strConverted);
				}else if ($i == 3){
				$GhIndex = to_number($strConverted);
				}else if ($i == 5){
				$Gi10Index = to_number($strConverted);
			}
		}
		
	}
}


function scopus($ScopusID){
	
	global $ScopusURL;
	global $Scopus_hIndex;
	global $Scopus_Documents;
	global $Scopus_Citations;
	global $Scopus_CitationDocuments;

	$ScopusURL = "https://www.scopus.com/authid/detail.uri?authorId=" . rawurlencode($ScopusID);
	$ScopusEntryURL = "https://scopus.com/";
	$scopus_api_url = "https://www.scopus.com/api/authors/" . rawurlencode($ScopusID) . "/metrics";

	$page_content = get_web_page($ScopusEntryURL);
	$cookies = $page_content['cookies'];

	$page_content = get_web_page($scopus_api_url, False, $cookies);
	$content = $page_content['content'];

	// LIBXML_NONET blocks network access while parsing (no external entities/DTDs)
	libxml_use_internal_errors(true);
	$scopus = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NONET);
	libxml_clear_errors();
	if ($scopus === false){
		return;
	}
	$Scopus_hIndex = to_number($scopus->h_index);
	$Scopus_Documents = to_number($scopus->documents);
	$Scopus_Citations = to_number($scopus->citations->citation_count);
	$Scopus_CitationDocuments = to_number($scopus->citations->by_documents_count);
}


function publons($publonsID){
	
	global $PublonsURL;
	global $publons_averagePerItem;
	global $publons_averagePerYear;
	global $publons_hIndex;
	global $publons_timesCited;
	global $publons_numPublicationsInWosCc;
	
	$PublonsURL = "https://publons.com/researcher/" . rawurlencode($publonsID) . "/";
	$publons_url = "https://publons.com/api/stats/individual/" . rawurlencode($publonsID) . "/";

	//$Code = file_get_contents($ridurl);

	$page_content = get_web_page($publons_url);
	$str_json = $page_content['content'];

	$json = json_decode($str_json, true);
	if (!is_array($json)){
		return;
	}

	$publons_averagePerItem = to_number(isset($json['averagePerItem']) ? $json['averagePerItem'] : '');
	$publons_averagePerYear = to_number(isset($json['averagePerYear']) ? $json['averagePerYear'] : '');
	$publons_hIndex = to_number(isset($json['hIndex']) ? $json['hIndex'] : '');
	$publons_timesCited = to_number(isset($json['timesCited']) ? $json['timesCited'] : '');
	$publons_numPublicationsInWosCc = to_number(isset($json['numPublicationsInWosCc']) ? $json['numPublicationsInWosCc'] : '');
	
}


function PrefixCheck(){
	global $prefix;
	if (strlen($prefix) > 1 && strlen($prefix) <= 10){
		return ($prefix . "_");
	}else{ 
		return ("");
	}
}

function ScriptAll(){

	global $gscholar_id;
	global $scopus_id;
	global $publons_id;
	global $prefix;
	
	global $GURL;
	global $GCitations;
	global $GhIndex;
	global $Gi10Index;
	
	global $ScopusURL;
	global $Scopus_hIndex;
	global $Scopus_Documents;
	global $Scopus_Citations;
	global $Scopus_CitationDocuments;
	
	global $PublonsURL;
	global $publons_averagePerItem;
	global $publons_averagePerYear;
	global $publons_hIndex;
	global $publons_timesCited;
	global $publons_numPublicationsInWosCc;
	
	global $prefix;
	global $EduStatsVersion;
	
	echo("EduStats = " . js_value($EduStatsVersion) . ";");

	if (strlen($gscholar_id) > 0){
		echo(PrefixCheck() . "Google_ID = " . js_value($gscholar_id) . ";");
		echo(PrefixCheck() . "Google_URL = " . js_value($GURL) . ";");
		echo(PrefixCheck() . "Google_Citations = " . js_value($GCitations) . ";");
		echo(PrefixCheck() . "Google_hIndex = " . js_value($GhIndex) . ";");
		echo(PrefixCheck() . "Google_i10Index = " . js_value($Gi10Index) . ";");
	}

	if (strlen($scopus_id) > 0){
		echo(PrefixCheck() . "Scopus_ID = " . js_value($scopus_id) . ";");
		echo(PrefixCheck() . "Scopus_URL = " . js_value($ScopusURL) . ";");
		echo(PrefixCheck() . "Scopus_hIndex = " . js_value($Scopus_hIndex) . ";");
		echo(PrefixCheck() . "Scopus_Citations = " . js_value($Scopus_Citations) . ";");
		echo(PrefixCheck() . "Scopus_Documents = " . js_value($Scopus_Documents) . ";");
		echo(PrefixCheck() . "Scopus_CitationDocuments = " . js_value($Scopus_CitationDocuments) . ";");
	}
	
	global $publons_averagePerItem;
	global $publons_averagePerYear;
	global $publons_hIndex;
	global $publons_timesCited;
	global $publons_numPublicationsInWosCc;
	
	if (strlen($publons_id) > 0){
		echo(PrefixCheck() . "Publons_ID = " . js_value($publons_id) . ";");
		echo(PrefixCheck() . "Publons_URL = " . js_value($PublonsURL) . ";");
		echo(PrefixCheck() . "publons_averagePerItem = " . js_value((string)$publons_averagePerItem) . ";");
		echo(PrefixCheck() . "publons_averagePerYear = " . js_value((string)$publons_averagePerYear) . ";");
		echo(PrefixCheck() . "publons_hIndex = " . js_value((string)$publons_hIndex) . ";");
		echo(PrefixCheck() . "publons_timesCited = " . js_value((string)$publons_timesCited) . ";");
		echo(PrefixCheck() . "publons_numPublicationsInWosCc = " . js_value((string)$publons_numPublicationsInWosCc) . ";");
	}
}

function JSONAll(){
	global $gscholar_id;
	global $scopus_id;
	global $publons_id;
	global $prefix;
	
	global $GURL;
	global $GCitations;
	global $GhIndex;
	global $Gi10Index;
	
	global $ScopusURL;
	global $Scopus_hIndex;
	global $Scopus_Documents;
	global $Scopus_Citations;
	global $Scopus_CitationDocuments;
	
	global $PublonsURL;
	global $publons_averagePerItem;
	global $publons_averagePerYear;
	global $publons_hIndex;
	global $publons_timesCited;
	global $publons_numPublicationsInWosCc;
	
	global $EduStatsVersion;
	
	$result = array("EduStats" => $EduStatsVersion);

	if (strlen($gscholar_id) > 0){
		$result["Google"] = array(
			"ID"        => $gscholar_id,
			"URL"       => $GURL,
			"Citations" => $GCitations,
			"hIndex"    => $GhIndex,
			"i10Index"  => $Gi10Index
		);
	}

	if (strlen($scopus_id) > 0){
		$result["Scopus"] = array(
			"ID"                => $scopus_id,
			"URL"               => $ScopusURL,
			"Documents"         => $Scopus_Documents,
			"hIndex"            => $Scopus_hIndex,
			"Citations"         => $Scopus_Citations,
			"CitationDocuments" => $Scopus_CitationDocuments
		);
	}

	if (strlen($publons_id) > 0){
		$result["Publons"] = array(
			"ID"                     => $publons_id,
			"URL"                    => $PublonsURL,
			"averagePerItem"         => $publons_averagePerItem,
			"averagePerYear"         => $publons_averagePerYear,
			"hIndex"                 => $publons_hIndex,
			"timesCited"             => $publons_timesCited,
			"numPublicationsInWosCc" => $publons_numPublicationsInWosCc
		);
	}

	// json_encode escapes all values; JSON_HEX_TAG keeps it safe if inserted into HTML
	echo (json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG));
}
?>
