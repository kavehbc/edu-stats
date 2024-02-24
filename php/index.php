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
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('safe_mode', false);
//ini_set("log_errors", 1);
//ini_set("error_log", "php-error.log");

ini_set("user_agent","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.82 Safari/537.36");
ini_set('allow_url_fopen', 1);

$whitelist_urls = array("edu-stats.bakhtiyari.com", "edu-stats.kaveh.me", "kaveh.bakhtiyari.com", "wvvw.monataghavi.com");

$validated = False;
if (isset($_SERVER['HTTP_REFERER'])){
	$server_ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
	$parse = parse_url($server_ref);
	$host = $parse['host'];
	
	foreach ($whitelist_urls as $value){
		if ($host == $value){
			$validated = True;
		}
	}
}

if (!$validated)
{
	header('HTTP/1.0 403 Forbidden');
	echo("Your access has been denied.");
	exit();
}

$EduStatsVersion = "2.0.1";

function get_web_page($url, $post = false, $cookiesIn = '', $param = ''){
	$options = array(
	CURLOPT_POST           => $post,
	CURLOPT_RETURNTRANSFER => true,     // return web page
	CURLOPT_HEADER         => true,     //return headers in addition to content
	CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	CURLOPT_ENCODING       => "",       // handle all encodings
	CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
	CURLOPT_TIMEOUT        => 120,      // timeout on response
	CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	CURLINFO_HEADER_OUT    => true,
	CURLOPT_SSL_VERIFYPEER => false,     // Disabled SSL Cert checks
	CURLOPT_SSL_VERIFYHOST => false,
	CURLOPT_VERBOSE        => true,
	CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2_0,
	CURLOPT_USERAGENT      => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.82 Safari/537.36",
	CURLOPT_COOKIE         => $cookiesIn,
	CURLOPT_COOKIEJAR      => dirname(__FILE__) . '/cookie.txt',
	CURLOPT_COOKIEFILE     => dirname(__FILE__) . '/cookie.txt'
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
	
	$header_content = substr($rough_content, 0, $header['header_size']);
	$body_content = trim(str_replace($header_content, '', $rough_content));
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

$gscholar_id = "";
if(isset($_GET['google']) && !empty($_GET['google'])){
	$gscholar_id = $_GET['google'];
	google($gscholar_id);
}

$scopus_id = "";
if(isset($_GET['scopus']) && !empty($_GET['scopus'])){
	$scopus_id = $_GET['scopus'];
	scopus($scopus_id);
}

$publons_id = "";
if(isset($_GET['publons']) && !empty($_GET['publons'])){
	$publons_id = $_GET['publons'];
	publons($publons_id);
}

$prefix = "";
if(isset($_GET['prefix']) && !empty($_GET['prefix'])){
	$prefix = $_GET['prefix'];
}

$output = "javascript";
if(isset($_GET['output']) && !empty($_GET['output'])){
	$output = $_GET['output'];
}

switch ($output){
	case "json":
		JSONAll();
		break;
	case "javascript":
		ScriptAll();
		break;
	default:
		ScriptAll();
}


function google($GScholar){
	
	global $GURL;
	global $GCitations;
	global $GhIndex;
	global $Gi10Index;
	
	$GURL = "https://scholar.google.com/citations?user=" . $GScholar . "&hl=en";
	
	$PageContent = get_web_page($GURL, false);
	$Cookies = $PageContent['cookies'];
	
	$PageContent = get_web_page($GURL, false, $Cookies);
	$Code = $PageContent['content'];
		
	$SearchFROM = "<td class=\"gsc_rsb_std\">";
	$strTO = "</td>";
	$startPoint = 1;
	
	for ($i = 1; $i <= 5; $i++){
		$dblFROM = strpos($Code,$SearchFROM,$startPoint);
		$dblFROM = $dblFROM + strlen($SearchFROM);
		$dblTO = strpos($Code,$strTO,$dblFROM);
		$startPoint = $dblTO;
		$dblTO = $dblTO - $dblFROM;
		
		$strResult = Trim(substr($Code,$dblFROM,$dblTO));
		if (strlen($strResult) > 0){
			$strConverted = str_replace(",","",$strResult);
			if ($i == 1){
				$GCitations = $strConverted;
				}else if ($i == 3){
				$GhIndex = $strConverted;
				}else if ($i == 5){
				$Gi10Index = $strConverted;
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

	$ScopusURL = "https://www.scopus.com/authid/detail.uri?authorId=" . $ScopusID;
	$ScopusEntryURL = "https://scopus.com/";
	$scopus_api_url = "https://www.scopus.com/api/authors/" . $ScopusID . "/metrics";

	$page_content = get_web_page($ScopusEntryURL);
	$cookies = $page_content['cookies'];
		
	$page_content = get_web_page($scopus_api_url, False, $cookies);
	$content = $page_content['content'];
	
	$scopus = new SimpleXMLElement($content);
	$Scopus_hIndex = $scopus->h_index;
    $Scopus_Documents = $scopus->documents;
	$Scopus_Citations = $scopus->citations->citation_count;
	$Scopus_CitationDocuments = $scopus->citations->by_documents_count;
}


function publons($publonsID){
	
	global $PublonsURL;
	global $publons_averagePerItem;
	global $publons_averagePerYear;
	global $publons_hIndex;
	global $publons_timesCited;
	global $publons_numPublicationsInWosCc;
	
	$PublonsURL = "https://publons.com/researcher/" . $publonsID . "/";
	$publons_url = "https://publons.com/api/stats/individual/" . $publonsID . "/";
	
	//$Code = file_get_contents($ridurl);
	
	$page_content = get_web_page($publons_url);
	$str_json = $page_content['content'];

	$json = json_decode($str_json, true);
	
	$publons_averagePerItem = $json['averagePerItem'];
	$publons_averagePerYear = $json['averagePerYear'];
	$publons_hIndex = $json['hIndex'];
	$publons_timesCited = $json['timesCited'];
	$publons_numPublicationsInWosCc = $json['numPublicationsInWosCc'];
	
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
	
	echo("EduStats = \"" . $EduStatsVersion . "\";");
	
	if (strlen($gscholar_id) > 0){
		echo(PrefixCheck() . "Google_ID = \"" . $gscholar_id . "\";");
		echo(PrefixCheck() . "Google_URL = \"" . $GURL . "\";");
		echo(PrefixCheck() . "Google_Citations = " . $GCitations . ";");
		echo(PrefixCheck() . "Google_hIndex = " . $GhIndex . ";");
		echo(PrefixCheck() . "Google_i10Index = " . $Gi10Index . ";");
	}
	
	if (strlen($scopus_id) > 0){
		echo(PrefixCheck() . "Scopus_ID = \"" . $scopus_id . "\";");
		echo(PrefixCheck() . "Scopus_URL = \"" . $ScopusURL . "\";");
		echo(PrefixCheck() . "Scopus_hIndex = " . $Scopus_hIndex . ";");
		echo(PrefixCheck() . "Scopus_Citations = " . $Scopus_Citations . ";");		
		echo(PrefixCheck() . "Scopus_Documents = " . $Scopus_Documents . ";");
		echo(PrefixCheck() . "Scopus_CitationDocuments = " . $Scopus_CitationDocuments . ";");
	}
	
	global $publons_averagePerItem;
	global $publons_averagePerYear;
	global $publons_hIndex;
	global $publons_timesCited;
	global $publons_numPublicationsInWosCc;
	
	if (strlen($publons_id) > 0){
		echo(PrefixCheck() . "Publons_ID = \"" . $publons_id . "\";");
		echo(PrefixCheck() . "Publons_URL = \"" . $PublonsURL . "\";");
		echo(PrefixCheck() . "publons_averagePerItem = \"" . $publons_averagePerItem . "\";");
		echo(PrefixCheck() . "publons_averagePerYear = \"" . $publons_averagePerYear . "\";");
		echo(PrefixCheck() . "publons_hIndex = \"" . $publons_hIndex . "\";");
		echo(PrefixCheck() . "publons_timesCited = \"" . $publons_timesCited . "\";");
		echo(PrefixCheck() . "publons_numPublicationsInWosCc = \"" . $publons_numPublicationsInWosCc . "\";");
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
	
	$strJSON = "";
	$strJSON .= "{\"EduStats\":\"" . $EduStatsVersion . "\"\r\n";
	
	if (strlen($gscholar_id) > 0){
		$strJSON .= ",\"Google\":\r\n";
		$strJSON .= "\t{\"ID\":\"" . $gscholar_id . "\",\r\n";
		$strJSON .= "\t\"URL\":\"" . $GURL . "\",\r\n";
		$strJSON .= "\t\"Citations\":" . $GCitations . ",\r\n";
		$strJSON .= "\t\"hIndex\":" . $GhIndex . ",\r\n";
		$strJSON .= "\t\"i10Index\":" . $Gi10Index . "}\r\n";
	}
	
	if (strlen($scopus_id) > 0){
		$strJSON .= ",";
		$strJSON .= "\"Scopus\":\r\n";
		$strJSON .= "\t{\"ID\":\"" . $scopus_id . "\",\r\n";
		$strJSON .= "\t\"URL\":\"" . $ScopusURL . "\",\r\n";
		$strJSON .= "\t\"Documents\":" . $Scopus_Documents . ",\r\n";
		$strJSON .= "\t\"hIndex\":" . $Scopus_hIndex . ",\r\n";
		$strJSON .= "\t\"Citations\":" . $Scopus_Citations . ",\r\n";
		$strJSON .= "\t\"CitationDocuments\":" . $Scopus_CitationDocuments . "}\r\n";
	}
	
	if (strlen($publons_id) > 0){
		$strJSON .= ",";
		$strJSON .= "\"Publons\":\r\n";
		$strJSON .= "\t{\"ID\":\"" . $publons_id . "\",\r\n";
		$strJSON .= "\t\"URL\":\"" . $PublonsURL . "\",\r\n";
		$strJSON .= "\t\"averagePerItem\":" . $publons_averagePerItem . ",\r\n";
		$strJSON .= "\t\"averagePerYear\":" . $publons_averagePerYear . ",\r\n";
		$strJSON .= "\t\"hIndex\":" . $publons_hIndex . ",\r\n";
		$strJSON .= "\t\"timesCited\":" . $publons_timesCited . ",\r\n";
		$strJSON .= "\t\"numPublicationsInWosCc\":" . $publons_numPublicationsInWosCc . "}\r\n";
	}
	
	$strJSON .= "}";		
	echo ($strJSON);
}
?>
