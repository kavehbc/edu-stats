<?php
	//**************************************************
	// Edu-Stats v.1.9.1
	// Academic Statistics Parser
	// Google Scholar, Scopus, ResearchGate, ResearcherID
	//
	//
	// Developed by Kaveh Bakhtiyari ( http://www.bakhtiyari.com )
	// v1.9.1: 24 April 2017
	//**************************************************
	
	ini_set("user_agent","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36");
	ini_set('allow_url_fopen',1);

	$EduStatsVersion = "1.9.1";

	function get_web_page( $url, $cookiesIn = '', $param = '' ){
        $options = array(
		CURLOPT_POST           => true,
		CURLOPT_POSTFIELDS     => $param,   //Set POST parameters
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
		CURLOPT_COOKIE         => $cookiesIn
        );
		
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
	$SDocuments = -1;
	$ShIndex = -1;
	$SCoAuthors = -1;
	$SReferences = -1;
	
	//ResearcherID Variables
	$ResearcherID_totalArticleCount = -1;
	$ResearcherID_articleCountForMetrics = -1;
	$ResearcherID_timesCited = -1;
	$ResearcherID_averagePerItem = -1;
	$ResearcherID_hindex = -1;
	$ResearcherID_lastUpdatedString = -1;
	
	$GScholar = "";
	$SScopusID = "";
	$ReID = "";
	$prefix = "";
	$output = "javascript";
	
	$GURL = "";
	$SURL = "";
	$ReIDURL = "";
	
	
	if(isset($_GET['google']) && !empty($_GET['google'])){
		$GScholar = $_GET['google'];
		$GURL = "http://scholar.google.com/citations?user=" . $GScholar . "&hl=en";
		google($GURL);
	}
	if(isset($_GET['scopus']) && !empty($_GET['scopus'])){
		$SScopusID = $_GET['scopus'];
		$SURL = "https://www.scopus.com/authid/detail.uri?authorId=" . $SScopusID;
		scopus($SURL);
	}
	if(isset($_GET['reid']) && !empty($_GET['reid'])){
		$ReID = $_GET['reid'];
		$ReIDURL = "http://www.researcherid.com/rid/" . $ReID;
		researcherid($ReIDURL);
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
		
		$Code = file_get_contents($googleurl);
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
		
		global $SDocuments;
		global $ShIndex;
		global $SCoAuthors;
		global $SReferences;
		
		$PageContent = get_web_page($scopusurl);
		$Code = $PageContent['content'];
		
		$SearchFROM = "'see all references cited by this author'";
		$strTO = "</a>";
		$dblFROM = strpos($Code,$SearchFROM,1);
		$SearchFROM = ">";
		$dblFROM = strpos($Code,$SearchFROM,$dblFROM);
		$dblFROM = $dblFROM + strlen($SearchFROM);
		$dblTO = strpos($Code,$strTO,$dblFROM);
		$startPoint = $dblTO;
		$dblTO = $dblTO - $dblFROM;
		
		$strResult = trim(substr($Code,$dblFROM,$dblTO));
		if (strlen($strResult) > 0){
			$strConverted = str_replace(",","",$strResult);
			$SReferences = $strConverted;
		}
		
		
		$strStart = "\"addInfoRow row1\"";
		$startPoint = strpos($Code,$strStart,1);
		
		$SearchFROM = " title='View a list of documents by this author below'>";
		$strTO = "</a>";
		$dblFROM = strpos($Code,$SearchFROM,$startPoint);
		
		$dblFROM = $dblFROM + strlen($SearchFROM);
		$dblTO = strpos($Code,$strTO,$dblFROM);
		$startPoint = $dblTO;
		$dblTO = $dblTO - $dblFROM;
		
		$strResult = trim(substr($Code,$dblFROM,$dblTO));
		if (strlen($strResult) > 0){
			$strConverted = str_replace(",","",$strResult);
			$SDocuments = $strConverted;
		}
		
		
		
		$strStart = "\"addInfoRow row3\"";
		$startPoint = strpos($Code,$strStart,$startPoint);
		
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
			$ShIndex = $strConverted;
		}
		
		
		$strStart = "\"addInfoRow row4\"";
		$startPoint = strpos($Code,$strStart,$startPoint);
		
		$SearchFROM = " data-title='View a list of authors who have published with this author below'>";
		$strTO = "</a>";
		$dblFROM = strpos($Code,$SearchFROM,$startPoint);
		$dblFROM = $dblFROM + strlen($SearchFROM);
		$dblTO = strpos($Code,$strTO,$dblFROM);
		$startPoint = $dblTO;
		$dblTO = $dblTO - $dblFROM;
		
		$strResult = trim(substr($Code,$dblFROM,$dblTO));
		if (strlen($strResult) > 0){
			$strConverted = str_replace(",","",$strResult);
			$SCoAuthors = $strConverted;
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
		
		$PageContent = get_web_page($newURL,$strCookies,$param);
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
		global $SScopusID;
		global $ReID;
		global $prefix;
		
		global $GURL;
		global $GCitations;
		global $GhIndex;
		global $Gi10Index;
		
		global $SURL;
		global $SDocuments;
		global $ShIndex;
		global $SCoAuthors;
		global $SReferences;
		
		global $ReIDURL;
		global $ResearcherID_totalArticleCount;
		global $ResearcherID_articleCountForMetrics;
		global $ResearcherID_timesCited;
		global $ResearcherID_averagePerItem;
		global $ResearcherID_hindex;
		global $ResearcherID_lastUpdatedString;
		
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
		
		if (strlen($SScopusID) > 0){
			echo(PrefixCheck() . "Scopus_ID = \"" . $SScopusID . "\";");
			echo(PrefixCheck() . "Scopus_URL = \"" . $SURL . "\";");
			echo(PrefixCheck() . "Scopus_Documents = " . $SDocuments . ";");
			echo(PrefixCheck() . "Scopus_hIndex = " . $ShIndex . ";");
			echo(PrefixCheck() . "Scopus_CoAuthors = " . $SCoAuthors . ";");
			echo(PrefixCheck() . "Scopus_References = " . $SReferences . ";");
		}
		
		if (strlen($ReID) > 0){
			echo(PrefixCheck() . "ReID_ID = \"" . $ReID . "\";");
			echo(PrefixCheck() . "ReID_URL = \"" . $ReIDURL . "\";");
			echo(PrefixCheck() . "ResearcherID_totalArticleCount = \"" . $ResearcherID_totalArticleCount . "\";");
			echo(PrefixCheck() . "ResearcherID_articleCountForMetrics = \"" . $ResearcherID_articleCountForMetrics . "\";");
			echo(PrefixCheck() . "ResearcherID_timesCited = \"" . $ResearcherID_timesCited . "\";");
			echo(PrefixCheck() . "ResearcherID_averagePerItem = \"" . $ResearcherID_averagePerItem . "\";");
			echo(PrefixCheck() . "ResearcherID_hindex = \"" . $ResearcherID_hindex . "\";");
			echo(PrefixCheck() . "ResearcherID_lastUpdatedString = \"" . $ResearcherID_lastUpdatedString . "\";");
		}
		
	}
	
	function JSONAll(){
		global $GScholar;
		global $SScopusID;
		global $ReID;
		global $prefix;
		
		global $GURL;
		global $GCitations;
		global $GhIndex;
		global $Gi10Index;
		
		global $SURL;
		global $SDocuments;
		global $ShIndex;
		global $SCoAuthors;
		global $SReferences;
		
		global $ReIDURL;
		global $ResearcherID_totalArticleCount;
		global $ResearcherID_articleCountForMetrics;
		global $ResearcherID_timesCited;
		global $ResearcherID_averagePerItem;
		global $ResearcherID_hindex;
		global $ResearcherID_lastUpdatedString;
		
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
		
		if (strlen($SScopusID) > 0){
			$strJSON .= ",";
			$strJSON .= "\"Scopus\":\r\n";
			$strJSON .= "\t\"ID\":\"" . $SScopusID . "\",\r\n";
			$strJSON .= "\t\"URL\":\"" . $SURL . "\",\r\n";
			$strJSON .= "\t\"Documents\":" . $SDocuments . ",\r\n";
			$strJSON .= "\t\"hIndex\":" . $ShIndex . ",\r\n";
			$strJSON .= "\t\"CoAuthors\":" . $SCoAuthors . ",\r\n";
			$strJSON .= "\t\"References\":" . $SReferences . "}\r\n";
		}
		
		if (strlen($ReID) > 0){
			$strJSON .= ",";
			$strJSON .= "\"ResearcherID\":\r\n";
			$strJSON .= "\t\"ID\":\"" . $ReID . "\";\r\n";
			$strJSON .= "\t\"URL\":\"" . $ReIDURL . "\";\r\n";
			$strJSON .= "\t\"totalArticleCount\":" . $ResearcherID_totalArticleCount . ",\r\n";
			$strJSON .= "\t\"articleCountForMetrics\":" . $ResearcherID_articleCountForMetrics . ",\r\n";
			$strJSON .= "\t\"timesCited\":" . $ResearcherID_timesCited . ",\r\n";
			$strJSON .= "\t\"averagePerItem\":" . $ResearcherID_averagePerItem . ",\r\n";
			$strJSON .= "\t\"hindex\":" . $ResearcherID_hindex . ",\r\n";
			$strJSON .= "\t\"lastUpdatedString\":\"" . $ResearcherID_lastUpdatedString . "\"}\r\n";
		}
		$strJSON .= "}";		
		echo ($strJSON);
	}
?>
