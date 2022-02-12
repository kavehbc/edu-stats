<?php
$ServerRef = $_SERVER['HTTP_REFERER'];
if (substr($ServerRef,0,28) == "https://kaveh.bakhtiyari.com" || substr($ServerRef,0,27) == "http://kaveh.bakhtiyari.com" || substr($ServerRef,0,28) == "https://wvvw.monataghavi.com" || substr($ServerRef,0,27) == "http://wvvw.monataghavi.com")
{
	//Everything is good. Do nothing, and pass.
}else{
	header('HTTP/1.0 403 Forbidden');
	echo ("Your access has been denied.");
	exit();
}

	//**************************************************
	// Edu-Stats v.2.0.1
	// Academic Statistics Parser
	// Google Scholar, Scopus, ResearcherID, Mendeley
	//
	//
	// Developed by Kaveh Bakhtiyari ( http://www.bakhtiyari.com )
	// v2.0.1: 12 February 2022
	//**************************************************
	error_reporting(E_ERROR | E_PARSE);
	//error_reporting(E_ALL);
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	ini_set('safe_mode', false);
	//ini_set("log_errors", 1);
	//ini_set("error_log", "php-error.log");
	
	ini_set("user_agent","Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36");
	ini_set('allow_url_fopen',1);

	$EduStatsVersion = "2.0.1";

	function get_web_page( $url, $post = false, $cookiesIn = '', $param = ''){
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
		CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
		CURLOPT_USERAGENT      => "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36",
		CURLOPT_COOKIE         => $cookiesIn
        );
		
		if (strlen($param) > 0){
			$options[CURLOPT_POSTFIELDS] = $param; //Set POST parameters
		}
		
        $ch      = curl_init( $url );
        curl_setopt_array( $ch, $options );
        $rough_content = curl_exec( $ch );
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );
		
        $header_content = substr($rough_content, 0, $header['header_size']);
        $body_content = trim(str_replace($header_content, '', $rough_content));
        $pattern = "#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m"; 
        preg_match_all($pattern, $header_content, $matches); 
        $cookiesOut = implode("; ", $matches['cookie']);
		
        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['headers']  = $header_content;
        $header['content'] = $body_content;
        $header['cookies'] = $cookiesOut;
		return $header;
	}
		
	//Google Scholar Variables
	$GCitations = -1;
	$GhIndex = -1;
	$Gi10Index = -1;
	
	//Scopus Variables
	$Scopus_hIndex = -1;
	$Scopus_Documents = -1;
	$Scopus_CitationDocuments = -1;
	$Scopus_CoAuthors = -1;

	
	//ResearcherID Variables
	$ResearcherID_totalArticleCount = -1;
	$ResearcherID_articleCountForMetrics = -1;
	$ResearcherID_timesCited = -1;
	$ResearcherID_averagePerItem = -1;
	$ResearcherID_hindex = -1;
	$ResearcherID_lastUpdatedString = -1;
	
	//Mendeley Variables
	$Mendeley_Media = -1;
	$Mendeley_hIndex = -1;
	$Mendeley_Citations = -1;
	$Mendeley_Readers = -1;
	$Mendeley_Views = -1;

	$GScholar = "";
	$ScopusID = "";
	$ReID = "";
	$MendeleyID = "";

	$prefix = "";
	$output = "javascript";
	
	$GURL = "";
	$ScopusURL = "";
	$ReIDURL = "";
	$MendeleyURL = "";
	
	
	if(isset($_GET['google']) && !empty($_GET['google'])){
		$GScholar = $_GET['google'];
		$GURL = "https://scholar.google.com/citations?user=" . $GScholar . "&hl=en";
		google($GURL);
	}
	if(isset($_GET['scopus']) && !empty($_GET['scopus'])){
		$ScopusID = $_GET['scopus'];
		$ScopusURL = "https://www.scopus.com/authid/detail.uri?authorId=" . $ScopusID;
		scopus($ScopusURL);
	}
	if(isset($_GET['reid']) && !empty($_GET['reid'])){
		$ReID = $_GET['reid'];
		$ReIDURL = "http://www.researcherid.com/rid/" . $ReID;
		researcherid($ReIDURL);
	}
	if(isset($_GET['mendeley']) && !empty($_GET['mendeley'])){
		$MendeleyID = $_GET['mendeley'];
		$MendeleyURL = "https://www.mendeley.com/profiles/" . $MendeleyID . "/stats/";
		mendeley($MendeleyURL);
	}
	if(isset($_GET['prefix']) && !empty($_GET['prefix'])){
		$prefix = $_GET['prefix'];
	}
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
	
	
	function google($googleurl){
		
		global $GCitations;
		global $GhIndex;
		global $Gi10Index;
		
		$PageContent = get_web_page($googleurl, false);
		$Cookies = $PageContent['cookies'];
		
		$PageContent = get_web_page($googleurl, false, $Cookies);
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
	
	
	function scopus($scopusurl){
		
		global $Scopus_hIndex;
		global $Scopus_Documents;
		global $Scopus_CitationDocuments;
		global $Scopus_CoAuthors;

		$PageContent = get_web_page($scopusurl, false);
		$Cookies = $PageContent['cookies'];
		
		$PageContent = get_web_page($scopusurl, false, $Cookies);
		$Code = $PageContent['content'];
		
		$SearchFROM = "<section id=\"authorDetailsHindex\">";
		$startPoint = strpos($Code,$SearchFROM,1);
		
		$SearchFROM = "<span class=\"fontLarge\">";
		$strTO = "</span>";
		$dblFROM = strpos($Code,$SearchFROM,$startPoint);
		$dblFROM = $dblFROM + strlen($SearchFROM);
		$dblTO = strpos($Code,$strTO,$dblFROM);
		$startPoint = $dblTO;
		$dblTO = $dblTO - $dblFROM;
		
		$strResult = trim(substr($Code,$dblFROM,$dblTO));
		if (strlen($strResult) > 0){
			$strConverted = str_replace(",","",$strResult);
			$Scopus_hIndex = $strConverted;
		}
		
		$SearchFROM = "<section id=\"authorDetailsDocumentsByAuthor\">";
		$startPoint = strpos($Code,$SearchFROM,1);
		
		$SearchFROM = "<span class=\"fontLarge pull-left\">";
		$strTO = "</span>";
		$dblFROM = strpos($Code,$SearchFROM,$startPoint);
		$dblFROM = $dblFROM + strlen($SearchFROM);
		$dblTO = strpos($Code,$strTO,$dblFROM);
		$startPoint = $dblTO;
		$dblTO = $dblTO - $dblFROM;
		
		$strResult = trim(substr($Code,$dblFROM,$dblTO));
		if (strlen($strResult) > 0){
			$strConverted = str_replace(",","",$strResult);
			$Scopus_Documents = $strConverted;
		}
		
		$SearchFROM = "<section id=\"authorDetailsTotalCitations\">";
		$startPoint = strpos($Code,$SearchFROM,1);
		
		$SearchFROM = "<span class=\"btnText\">";
		$strTO = "</span>";
		$dblFROM = strpos($Code,$SearchFROM,$startPoint);
		$dblFROM = $dblFROM + strlen($SearchFROM);
		$dblTO = strpos($Code,$strTO,$dblFROM);
		$startPoint = $dblTO;
		$dblTO = $dblTO - $dblFROM;
		
		$strResult = trim(substr($Code,$dblFROM,$dblTO));
		if (strlen($strResult) > 0){
			$strConverted = str_replace(",","",$strResult);
			$Scopus_CitationDocuments = $strConverted;
		}
				
		$SearchFROM = "<a id=\"coAuthLi\" data-toggle=\"tab\"";
		$startPoint = strpos($Code,$SearchFROM,1);
		
		$SearchFROM = "<span>";
		$strTO = "</span>";
		$dblFROM = strpos($Code,$SearchFROM,$startPoint);
		$dblFROM = $dblFROM + strlen($SearchFROM);
		$dblTO = strpos($Code,$strTO,$dblFROM);
		$startPoint = $dblTO;
		$dblTO = $dblTO - $dblFROM;
		
		$strResult = trim(substr($Code,$dblFROM,$dblTO));
		if (strlen($strResult) > 0){
			$strConverted = str_replace(",","",$strResult);
			$Scopus_CoAuthors = $strConverted;
		}

	}
	
	function researcherid($ridurl){
		
		global $ResearcherID_totalArticleCount;
		global $ResearcherID_articleCountForMetrics;
		global $ResearcherID_timesCited;
		global $ResearcherID_averagePerItem;
		global $ResearcherID_hindex;
		global $ResearcherID_lastUpdatedString;
		
		$Code = file_get_contents($ridurl);
		
		$startPoint = 1;
		
		$SearchFROM = "<input type=\"hidden\" name=\"researcher.id\" value=\"";
		$strTO = "\"";
		$dblFROM = strpos($Code,$SearchFROM,$startPoint);
		$dblFROM = $dblFROM + strlen($SearchFROM);
		$dblTO = strpos($Code,$strTO,$dblFROM);
		$startPoint = $dblTO;
		$dblTO = $dblTO - $dblFROM;
		
		$devReID = Trim(substr($Code,$dblFROM,$dblTO));
		
		$param = "key=" . $devReID . "&listId=LIST1&displayName=My%20Publications&publicProfile=true&_=";
		$strCookies = "researcher.init=\"cmVzZWFyY2hlci5pbml0PVk=\"; JSESSIONID=4AC75E2B669DD47C9F49DE8485DF17B0";
		$newURL = "http://www.researcherid.com/MetricsInPreviewPage.action";
		
		$PageContent = get_web_page($newURL, true, $strCookies, $param);
		$Code = $PageContent['content'];
		
		
		
		$SearchFROM = "<label id=\"metrics_totalArticleCount\">";
		$strTO = "</label>";
		$dblFROM = strpos($Code,$SearchFROM,1);
		$dblFROM = $dblFROM + strlen($SearchFROM);
		$dblTO = strpos($Code,$strTO,$dblFROM);
		$dblTO = $dblTO - $dblFROM;
		$ResearcherID_totalArticleCount = trim(substr($Code,$dblFROM,$dblTO));
		
		
		$SearchFROM = "<label id=\"metrics_articleCountForMetrics\">";
		$strTO = "</label>";
		$dblFROM = strpos($Code,$SearchFROM,1);
		$dblFROM = $dblFROM + strlen($SearchFROM);
		$dblTO = strpos($Code,$strTO,$dblFROM);
		$dblTO = $dblTO - $dblFROM;
		$ResearcherID_articleCountForMetrics = trim(substr($Code,$dblFROM,$dblTO));
		
		
		$SearchFROM = "<label id=\"metrics_timesCited\">";
		$strTO = "</label>";
		$dblFROM = strpos($Code,$SearchFROM,1);
		$dblFROM = $dblFROM + strlen($SearchFROM);
		$dblTO = strpos($Code,$strTO,$dblFROM);
		$dblTO = $dblTO - $dblFROM;
		$ResearcherID_timesCited = trim(substr($Code,$dblFROM,$dblTO));
		
		
		$SearchFROM = "<label id=\"metrics_averagePerItem\">";
		$strTO = "</label>";
		$dblFROM = strpos($Code,$SearchFROM,1);
		$dblFROM = $dblFROM + strlen($SearchFROM);
		$dblTO = strpos($Code,$strTO,$dblFROM);
		$dblTO = $dblTO - $dblFROM;
		$ResearcherID_averagePerItem = trim(substr($Code,$dblFROM,$dblTO));
		
		$SearchFROM = "<label id=\"metrics_hindex\">";
		$strTO = "</label>";
		$dblFROM = strpos($Code,$SearchFROM,1);
		$dblFROM = $dblFROM + strlen($SearchFROM);
		$dblTO = strpos($Code,$strTO,$dblFROM);
		$dblTO = $dblTO - $dblFROM;
		$ResearcherID_hindex = trim(substr($Code,$dblFROM,$dblTO));
		
		
		$SearchFROM = "<label id=\"metrics_lastUpdatedString\">";
		$strTO = "</label>";
		$dblFROM = strpos($Code,$SearchFROM,1);
		$dblFROM = $dblFROM + strlen($SearchFROM);
		$dblTO = strpos($Code,$strTO,$dblFROM);
		$dblTO = $dblTO - $dblFROM;
		$ResearcherID_lastUpdatedString = trim(substr($Code,$dblFROM,$dblTO));
		
	}

	function mendeley($mendeleyurl){
		
		global $Mendeley_Media;
		global $Mendeley_hIndex;
		global $Mendeley_Citations;
		global $Mendeley_Readers;
		global $Mendeley_Views;
		
		$PageContent = get_web_page($mendeleyurl, false);
		$Code = $PageContent['content'];

		$SearchFROM = "Media mentions</span></h3></header><data class=\"number\" value=\"";
		$strTO = "\">";
		$dblFROM = strpos($Code,$SearchFROM,1);
		$dblFROM = $dblFROM + strlen($SearchFROM);
		$dblTO = strpos($Code,$strTO,$dblFROM);
		$startPoint = $dblTO;
		$dblTO = $dblTO - $dblFROM;
		
		$strResult = trim(substr($Code,$dblFROM,$dblTO));
		if (strlen($strResult) > 0){
			$strConverted = str_replace(",","",$strResult);
			$Mendeley_Media = $strConverted;
		}
		
		
		$SearchFROM = "h-index</span></h3></header><data class=\"number\" value=\"";
		$strTO = "\">";
		$dblFROM = strpos($Code,$SearchFROM,$startPoint);
		$dblFROM = $dblFROM + strlen($SearchFROM);
		$dblTO = strpos($Code,$strTO,$dblFROM);
		$startPoint = $dblTO;
		$dblTO = $dblTO - $dblFROM;
		
		$strResult = trim(substr($Code,$dblFROM,$dblTO));
		if (strlen($strResult) > 0){
			$strConverted = str_replace(",","",$strResult);
			$Mendeley_hIndex = $strConverted;
		}
		
		$SearchFROM = "Citations</span></h3></header><data class=\"number\" value=\"";
		$strTO = "\">";
		$dblFROM = strpos($Code,$SearchFROM,$startPoint);
		$dblFROM = $dblFROM + strlen($SearchFROM);
		$dblTO = strpos($Code,$strTO,$dblFROM);
		$startPoint = $dblTO;
		$dblTO = $dblTO - $dblFROM;
		
		$strResult = trim(substr($Code,$dblFROM,$dblTO));
		if (strlen($strResult) > 0){
			$strConverted = str_replace(",","",$strResult);
			$Mendeley_Citations = $strConverted;
		}

		$SearchFROM = "Readers</span></h3></header><data class=\"number\" value=\"";
		$strTO = "\">";
		$dblFROM = strpos($Code,$SearchFROM,$startPoint);
		$dblFROM = $dblFROM + strlen($SearchFROM);
		$dblTO = strpos($Code,$strTO,$dblFROM);
		$startPoint = $dblTO;
		$dblTO = $dblTO - $dblFROM;
		
		$strResult = trim(substr($Code,$dblFROM,$dblTO));
		if (strlen($strResult) > 0){
			$strConverted = str_replace(",","",$strResult);
			$Mendeley_Readers = $strConverted;
		}
		
		$SearchFROM = "Views</span></h3></header><data class=\"number\" value=\"";
		$strTO = "\">";
		$dblFROM = strpos($Code,$SearchFROM,$startPoint);
		$dblFROM = $dblFROM + strlen($SearchFROM);
		$dblTO = strpos($Code,$strTO,$dblFROM);
		$startPoint = $dblTO;
		$dblTO = $dblTO - $dblFROM;
		
		$strResult = trim(substr($Code,$dblFROM,$dblTO));
		if (strlen($strResult) > 0){
			$strConverted = str_replace(",","",$strResult);
			$Mendeley_Views = $strConverted;
		}
		
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
		
		global $GScholar;
		global $ScopusID;
		global $ReID;
		global $MendeleyID;		
		global $prefix;
		
		global $GURL;
		global $GCitations;
		global $GhIndex;
		global $Gi10Index;
		
		global $ScopusURL;
		global $Scopus_hIndex;
		global $Scopus_Documents;
		global $Scopus_CitationDocuments;
		global $Scopus_CoAuthors;
		
		global $ReIDURL;
		global $ResearcherID_totalArticleCount;
		global $ResearcherID_articleCountForMetrics;
		global $ResearcherID_timesCited;
		global $ResearcherID_averagePerItem;
		global $ResearcherID_hindex;
		global $ResearcherID_lastUpdatedString;
		
		global $MendeleyURL;
		global $Mendeley_Media;
		global $Mendeley_hIndex;
		global $Mendeley_Citations;
		global $Mendeley_Readers;
		global $Mendeley_Views;

		global $prefix;
		global $EduStatsVersion;
		
		echo("EduStats = \"" . $EduStatsVersion . "\";");
		
		if (strlen($GScholar) > 0){
			echo(PrefixCheck() . "Google_ID = \"" . $GScholar . "\";");
			echo(PrefixCheck() . "Google_URL = \"" . $GURL . "\";");
			echo(PrefixCheck() . "Google_Citations = " . $GCitations . ";");
			echo(PrefixCheck() . "Google_hIndex = " . $GhIndex . ";");
			echo(PrefixCheck() . "Google_i10Index = " . $Gi10Index . ";");
		}
		
		if (strlen($ScopusID) > 0){
			echo(PrefixCheck() . "Scopus_ID = \"" . $ScopusID . "\";");
			echo(PrefixCheck() . "Scopus_URL = \"" . $ScopusURL . "\";");
			echo(PrefixCheck() . "Scopus_hIndex = " . $Scopus_hIndex . ";");
			echo(PrefixCheck() . "Scopus_Documents = " . $Scopus_Documents . ";");
			echo(PrefixCheck() . "Scopus_CitationDocuments = " . $Scopus_CitationDocuments . ";");
			echo(PrefixCheck() . "Scopus_CoAuthors = " . $Scopus_CoAuthors . ";");
		}
		
		if (strlen($ReID) > 0){
			echo(PrefixCheck() . "ResearcherID_ID = \"" . $ReID . "\";");
			echo(PrefixCheck() . "ResearcherID_URL = \"" . $ReIDURL . "\";");
			echo(PrefixCheck() . "ResearcherID_Documents = \"" . $ResearcherID_totalArticleCount . "\";");
			echo(PrefixCheck() . "ResearcherID_articleCountForMetrics = \"" . $ResearcherID_articleCountForMetrics . "\";");
			echo(PrefixCheck() . "ResearcherID_Citations = \"" . $ResearcherID_timesCited . "\";");
			echo(PrefixCheck() . "ResearcherID_AverageCitations = \"" . $ResearcherID_averagePerItem . "\";");
			echo(PrefixCheck() . "ResearcherID_hIndex = \"" . $ResearcherID_hindex . "\";");
			echo(PrefixCheck() . "ResearcherID_lastUpdatedString = \"" . $ResearcherID_lastUpdatedString . "\";");
		}
		
		if (strlen($MendeleyID) > 0){
			echo(PrefixCheck() . "Mendeley_ID = \"" . $MendeleyID . "\";");
			echo(PrefixCheck() . "Mendeley_URL = \"" . $MendeleyURL . "\";");
			echo(PrefixCheck() . "Mendeley_Media = \"" . $Mendeley_Media . "\";");
			echo(PrefixCheck() . "Mendeley_hIndex = \"" . $Mendeley_hIndex . "\";");
			echo(PrefixCheck() . "ResearcherID_Citations = \"" . $Mendeley_Citations . "\";");
			echo(PrefixCheck() . "Mendeley_Readers = \"" . $Mendeley_Readers . "\";");
			echo(PrefixCheck() . "Mendeley_Views = \"" . $Mendeley_Views . "\";");
		}
	}
	
	function JSONAll(){
		global $GScholar;
		global $ScopusID;
		global $ReID;
		global $MendeleyID;
		global $prefix;
		
		global $GURL;
		global $GCitations;
		global $GhIndex;
		global $Gi10Index;
		
		global $ScopusURL;
		global $Scopus_hIndex;
		global $Scopus_Documents;
		global $Scopus_CitationDocuments;
		global $Scopus_CoAuthors;
		
		global $ReIDURL;
		global $ResearcherID_totalArticleCount;
		global $ResearcherID_articleCountForMetrics;
		global $ResearcherID_timesCited;
		global $ResearcherID_averagePerItem;
		global $ResearcherID_hindex;
		global $ResearcherID_lastUpdatedString;
		
		global $MendeleyURL;
		global $Mendeley_Media;
		global $Mendeley_hIndex;
		global $Mendeley_Citations;
		global $Mendeley_Readers;
		global $Mendeley_Views;

		global $EduStatsVersion;
		
		$strJSON = "";
		$strJSON .= "{\"EduStats\":\"" . $EduStatsVersion . "\"\r\n";
		
		if (strlen($GScholar) > 0){
			$strJSON .= ",\"Google\":\r\n";
			$strJSON .= "\t{\"ID\":\"" . $GScholar . "\",\r\n";
			$strJSON .= "\t\"URL\":\"" . $GURL . "\",\r\n";
			$strJSON .= "\t\"Citations\":" . $GCitations . ",\r\n";
			$strJSON .= "\t\"hIndex\":" . $GhIndex . ",\r\n";
			$strJSON .= "\t\"i10Index\":" . $Gi10Index . "}\r\n";
		}
		
		if (strlen($ScopusID) > 0){
			$strJSON .= ",";
			$strJSON .= "\"Scopus\":\r\n";
			$strJSON .= "\t{\"ID\":\"" . $ScopusID . "\",\r\n";
			$strJSON .= "\t\"URL\":\"" . $ScopusURL . "\",\r\n";
			$strJSON .= "\t\"Documents\":" . $Scopus_Documents . ",\r\n";
			$strJSON .= "\t\"hIndex\":" . $Scopus_hIndex . ",\r\n";
			$strJSON .= "\t\"CoAuthors\":" . $Scopus_CoAuthors . ",\r\n";
			$strJSON .= "\t\"CitationDocuments\":" . $Scopus_CitationDocuments . "}\r\n";
		}
		
		if (strlen($ReID) > 0){
			$strJSON .= ",";
			$strJSON .= "\"ResearcherID\":\r\n";
			$strJSON .= "\t{\"ID\":\"" . $ReID . "\",\r\n";
			$strJSON .= "\t\"URL\":\"" . $ReIDURL . "\",\r\n";
			$strJSON .= "\t\"Documents\":" . $ResearcherID_totalArticleCount . ",\r\n";
			$strJSON .= "\t\"articleCountForMetrics\":" . $ResearcherID_articleCountForMetrics . ",\r\n";
			$strJSON .= "\t\"Citations\":" . $ResearcherID_timesCited . ",\r\n";
			$strJSON .= "\t\"AverageCitations\":" . $ResearcherID_averagePerItem . ",\r\n";
			$strJSON .= "\t\"hIndex\":" . $ResearcherID_hindex . ",\r\n";
			$strJSON .= "\t\"lastUpdatedString\":\"" . $ResearcherID_lastUpdatedString . "\"}\r\n";
		}
		
		if (strlen($MendeleyID) > 0){
			$strJSON .= ",";
			$strJSON .= "\"Mendeley\":\r\n";
			$strJSON .= "\t{\"ID\":\"" . $MendeleyID . "\",\r\n";
			$strJSON .= "\t\"URL\":\"" . $MendeleyURL . "\",\r\n";
			$strJSON .= "\t\"Media\":" . $Mendeley_Media . ",\r\n";
			$strJSON .= "\t\"hIndex\":" . $Mendeley_hIndex . ",\r\n";
			$strJSON .= "\t\"Citations\":" . $Mendeley_Citations . ",\r\n";
			$strJSON .= "\t\"Readers\":" . $Mendeley_Readers . ",\r\n";
			$strJSON .= "\t\"Views\":" . $Mendeley_Views . "}\r\n";
		}
		$strJSON .= "}";		
		echo ($strJSON);
	}
?>
