<%@Language="VBScript" %>
<%
'**************************************************
' Edu-Stats v.1.9
' Academic Statistics Parser
' Google Scholar, Scopus, ResearchGate, ResearcherID
'
'
' Developed by Kaveh Bakhtiyari ( http://www.bakhtiyari.com )
' v1.9: 14 April 2017
' Google Scholar bug fixed.
' Scopus bug fixed.
'
' v1.8: 8 April 2015
' ResearchGate bug fixed.
'
' v1.7: 11 January 2015
' ResearcherID parser support added.
'
' v1.6: 10 January 2015
' SSL (HTTPS) bug fixed on Google Scholar.
'
' v1.5: 6 January 2015
' Prefix attribute added for multi-extraction.
'
' v1.0: 2 January 2015
'**************************************************

Server.ScriptTimeOut = 2147483647
Server.ScriptTimeOut = 7000
'On Error Resume Next

UserAgent = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.2 (KHTML, like Gecko) Chrome/15.0.874.121 Safari/535.2"

'Google Scholar Variables
GCitations = -1
GhIndex = -1
Gi10Index = -1

'Scopus Variables
SDocuments = -1
ShIndex = -1
SCoAuthors = -1
SReferences = -1

'ResearchGate Variables
RGPublications = -1
RGViews = -1 
RGDownloads = -1
RGCitations = -1
RGImpactPoints = -1
RGScore = -1

'ResearcherID Variables
ResearcherID_totalArticleCount = -1
ResearcherID_articleCountForMetrics = -1
ResearcherID_timesCited = -1
ResearcherID_averagePerItem = -1
ResearcherID_hindex = -1
ResearcherID_lastUpdatedString = -1

'Sample Variable = "?google=7ftCdTQAAAAJ&scopus=54413825200&rg=Kaveh_Bakhtiyari&reid=E-5776-2011"

GScholar = Request("google")
SScopusID = Request("scopus")
RGName = Request("rg")
ReID = Request("reid")
prefix = Request("prefix")

If Len(prefix) <= 10 Then
	prefix = prefix & "_"
Else
	prefix = ""
End If

If Len(GScholar) > 0 Then
	GURL = "http://scholar.google.com/citations?user=" & GScholar & "&hl=en"
	GoogleScholar(GURL)
End If

If Len(SScopusID) > 0 Then
	SURL = "https://www.scopus.com/authid/detail.uri?authorId=" & SScopusID
	Scopus(SURL)
End If

If Len(RGName) > 0 Then
	RGURL = "https://www.researchgate.net/profile/" & RGName
	ResearchGate(RGURL)
End If

If Len(ReID) > 0 Then
	ReIDURL = "http://www.researcherid.com/rid/" & ReID
	ResearcherID(ReIDURL)
End If


If Len(Request("print")) > 0 Then
	PrintAll
Else
	ScriptAll
End If






 
Function GoogleScholar(URL)

'Set Xobj = Server.CreateObject("Msxml2.XMLHTTP")
Set Xobj = Server.CreateObject("Msxml2.ServerXMLHTTP.3.0")
Xobj.Open "GET",URL,false
Xobj.SetRequestHeader "User-Agent", UserAgent
Xobj.SetRequestHeader "Referer", URL
'Xobj.SetRequestHeader "Content-type", "application/x-www-form-urlencoded"
Xobj.SetRequestHeader "Content-type", "text/html"
'Xobj.SetRequestHeader "Content-length", Len(Params)
Xobj.SetRequestHeader "Connection", "close"

Xobj.Send

If Int(xobj.Status) = 200 Then
	Code = xobj.ResponseText
	pageSize = Len(Code)
Else
	Code = "Server can not accessible at the moment!"
	pageSize = 0
End If

Set Xobj = Nothing

SearchFROM = "<td class=""gsc_rsb_std"">"
strTO = "</td>"
startPoint = 1

	FOR i = 1 to 5
dblFROM = InStr(startPoint,Code,SearchFROM)
dblFROM = dblFROM + Len(SearchFROM)
dblTO = InStr(dblFROM,Code,strTO)
startPoint = dblTO
dblTO = dblTO - dblFROM

strResult = Trim(Mid(Code,dblFROM,dblTO))
	If Len(strResult) > 0 Then
		strConverted = Replace(strResult,",","")
		If i = 1 Then
			GCitations = CDbl(strConverted)
		Elseif i = 3 Then
			GhIndex = CDbl(strConverted)
		Elseif i = 5 Then
			Gi10Index = CDbl(strConverted)
		End If
	End If

	Next
End Function




Function Scopus(URL)
Params = ""

'Set Xobj = Server.CreateObject("Msxml2.XMLHTTP")
Set Xobj = Server.CreateObject("Msxml2.ServerXMLHTTP.3.0")
Xobj.Open "GET",URL,false
Xobj.SetRequestHeader "User-Agent", UserAgent
Xobj.SetRequestHeader "Referer", URL
'Xobj.SetRequestHeader "Content-type", "application/x-www-form-urlencoded"
Xobj.SetRequestHeader "Content-type", "text/html"
'Xobj.SetRequestHeader "Content-length", Len(Params)
Xobj.SetRequestHeader "Connection", "close"

Xobj.Send

If Int(xobj.Status) = 200 Then
	Code = xobj.ResponseText
	strCookies = Xobj.getResponseHeader("Set-Cookie")
	'strCookies = Xobj.getAllResponseHeaders()
	pageSize = Len(Code)
Else
	Code = "Server can not accesible at the moment!"
	pageSize = 0
End If
		 
Set Xobj = Nothing

Params = ""

Set Xobj = Server.CreateObject("Msxml2.ServerXMLHTTP.3.0")
Xobj.Open "POST",URL,false
Xobj.setRequestHeader "User-Agent", UserAgent
Xobj.SetRequestHeader "Referer", URL
Xobj.setRequestHeader "Cookie", strCookies
Xobj.SetRequestHeader "Content-type", "application/x-www-form-urlencoded"
Xobj.SetRequestHeader "Content-length", Len(Params) + Len (strCookies)
Xobj.SetRequestHeader "Connection", "close"

Xobj.Send Params

If Int(xobj.Status) = 200 Then
	Code = xobj.ResponseText
	strCookies = Xobj.getResponseHeader("Set-Cookie")
	'strCookies = Xobj.getAllResponseHeaders()
	pageSize = Len(Code)
Else
	Code = "Server can not accessible at the moment!"
	pageSize = 0
End If

SearchFROM = "'see all references cited by this author'"
strTO = "</a>"
dblFROM = InStr(1,Code,SearchFROM)
SearchFROM = ">"
dblFROM = InStr(dblFROM,Code,SearchFROM)
dblFROM = dblFROM + Len(SearchFROM)
dblTO = InStr(dblFROM,Code,strTO)
startPoint = dblTO
dblTO = dblTO - dblFROM

strResult = Trim(Mid(Code,dblFROM,dblTO))
	If Len(strResult) > 0 Then
		strConverted = Replace(strResult,",","")
		SReferences = strConverted
	End If


strStart = """addInfoRow row1"""
startPoint = InStr(1,Code,strStart)

SearchFROM = " title='View a list of documents by this author below'>"
strTO = "</a>"
dblFROM = InStr(startPoint,Code,SearchFROM)

dblFROM = dblFROM + Len(SearchFROM)
dblTO = InStr(dblFROM,Code,strTO)
startPoint = dblTO
dblTO = dblTO - dblFROM

strResult = Trim(Mid(Code,dblFROM,dblTO))
	If Len(strResult) > 0 Then
		strConverted = Replace(strResult,",","")
		SDocuments = CDbl(strConverted)
	End If



strStart = """addInfoRow row3"""
startPoint = InStr(startPoint,Code,strStart)

SearchFROM = "<span>"
strTO = "</span>"
dblFROM = InStr(startPoint,Code,SearchFROM)
dblFROM = dblFROM + Len(SearchFROM)
dblTO = InStr(dblFROM,Code,strTO)
startPoint = dblTO
dblTO = dblTO - dblFROM

strResult = Trim(Mid(Code,dblFROM,dblTO))
	If Len(strResult) > 0 Then
		strConverted = Replace(strResult,",","")
		ShIndex = CDbl(strConverted)
	End If
 
 
strStart = """addInfoRow row4"""
startPoint = InStr(startPoint,Code,strStart)

SearchFROM = " data-title='View a list of authors who have published with this author below'>"
strTO = "</a>"
dblFROM = InStr(startPoint,Code,SearchFROM)
dblFROM = dblFROM + Len(SearchFROM)
dblTO = InStr(dblFROM,Code,strTO)
startPoint = dblTO
dblTO = dblTO - dblFROM

strResult = Trim(Mid(Code,dblFROM,dblTO))
	If Len(strResult) > 0 Then
		strConverted = Replace(strResult,",","")
		SCoAuthors = strConverted
	End If


End Function





Function ResearchGate(URL)

Params = ""

'Set Xobj = Server.CreateObject("Msxml2.XMLHTTP")
Set Xobj = Server.CreateObject("Msxml2.ServerXMLHTTP.3.0")
Xobj.Open "GET",URL,false
Xobj.SetRequestHeader "User-Agent", UserAgent
'Xobj.SetRequestHeader "Referer", URL
'Xobj.SetRequestHeader "Content-type", "application/x-www-form-urlencoded"
'Xobj.SetRequestHeader "Content-type", "text/html"
'Xobj.SetRequestHeader "Content-length", Len(Params)
'Xobj.SetRequestHeader "Connection", "close"

Xobj.Send

If Int(xobj.Status) = 200 Then
	Code = xobj.ResponseText
	strCookies = Xobj.getResponseHeader("Set-Cookie")
	'strCookies = Xobj.getAllResponseHeaders()
	pageSize = Len(Code)
Else
	Code = "Server can not accesible at the moment!"
	pageSize = 0
End If
		 
Set Xobj = Nothing


startPoint = 1

SearchFROM = "<span class=""ico-rgscore-13x16""></span>"
strTO = "</div>"
dblFROM = InStr(startPoint,Code,SearchFROM)
dblFROM = dblFROM + Len(SearchFROM)
dblTO = InStr(dblFROM,Code,strTO)
startPoint = dblTO
dblTO = dblTO - dblFROM

strResult = Trim(Mid(Code,dblFROM,dblTO))
	If Len(strResult) > 0 Then
		strConverted = Replace(strResult,",","")
		RGScore = CDbl(strConverted)
	End If

SearchFROM = "<div class=""stats-count"">"
strTO = "</div>"

	FOR i = 1 to 5
dblFROM = InStr(startPoint,Code,SearchFROM)
dblFROM = dblFROM + Len(SearchFROM)
dblTO = InStr(dblFROM,Code,strTO)
startPoint = dblTO
dblTO = dblTO - dblFROM

strResult = Trim(Mid(Code,dblFROM,dblTO))
	If Len(strResult) > 0 Then
		strConverted = strResult
		'strConverted = Replace(strConverted,",","")
		strConverted = Replace(strConverted,chr(13),"")
		strConverted = Replace(strConverted,chr(10),"")
		strConverted = Trim(strConverted)
		'strConverted = Asc(Left(strConverted,1))
		If i = 1 Then
			RGPublications = strConverted
		Elseif i = 2 Then
			RGViews = strConverted
		Elseif i = 3 Then
			RGDownloads = strConverted
		Elseif i = 4 Then
			RGCitations = strConverted
		Elseif i = 5 Then
			RGImpactPoints = strConverted
		End If
	End If

	Next

 
End Function








Function ResearcherID (URL)

'Set Xobj = Server.CreateObject("Msxml2.XMLHTTP")
Set Xobj = Server.CreateObject("Msxml2.ServerXMLHTTP.3.0")
Xobj.Open "GET",URL,false
Xobj.SetRequestHeader "User-Agent", UserAgent
Xobj.SetRequestHeader "Referer", URL
'Xobj.SetRequestHeader "Content-type", "application/x-www-form-urlencoded"
Xobj.SetRequestHeader "Content-type", "text/html"
'Xobj.SetRequestHeader "Content-length", Len(Params)
Xobj.SetRequestHeader "Connection", "close"

Xobj.Send

If Int(xobj.Status) = 200 Then
	Code = xobj.ResponseText
	pageSize = Len(Code)
Else
	Code = "Server can not accessible at the moment!"
	pageSize = 0
End If
		 
Set Xobj = Nothing




startPoint = 1

SearchFROM = "<input type=""hidden"" name=""researcher.id"" value="""
strTO = """"
dblFROM = InStr(startPoint,Code,SearchFROM)
dblFROM = dblFROM + Len(SearchFROM)
dblTO = InStr(dblFROM,Code,strTO)
startPoint = dblTO
dblTO = dblTO - dblFROM

devReID = Trim(Mid(Code,dblFROM,dblTO))






param = "key=" & devReID & "&listId=LIST1&displayName=My%20Publications&publicProfile=true&_="
strCookies = "researcher.init=""cmVzZWFyY2hlci5pbml0PVk=""; JSESSIONID=4AC75E2B669DD47C9F49DE8485DF17B0"
URL = "http://www.researcherid.com/MetricsInPreviewPage.action"

'Set Xobj = Server.CreateObject("Msxml2.XMLHTTP")
Set Xobj = Server.CreateObject("Msxml2.ServerXMLHTTP.3.0")
Xobj.Open "POST",URL,false

Xobj.SetRequestHeader "Host", "www.researcherid.com"
Xobj.SetRequestHeader "Content-Length", 75 'Len(strCookies)
Xobj.SetRequestHeader "Origin", "http://www.researcherid.com"
Xobj.SetRequestHeader "User-Agent", UserAgent
Xobj.SetRequestHeader "Content-type", "application/x-www-form-urlencoded; charset=UTF-8"
Xobj.SetRequestHeader "Accept", "text/javascript, text/html, application/xml, text/xml, */*"
Xobj.SetRequestHeader "X-Prototype-Version", "1.5.1"
Xobj.SetRequestHeader "X-Requested-With", "XMLHttpRequest"
Xobj.SetRequestHeader "Referer", URL
'"http://www.researcherid.com/rid/E-5776-2011"
Xobj.SetRequestHeader "Accept-Encoding", "gzip, deflate"
Xobj.SetRequestHeader "Accept-Language", "en-US,en;q=0.8,fa;q=0.6,en-GB;q=0.4"
Xobj.setRequestHeader "Cookie", strCookies
Xobj.SetRequestHeader "Connection", "close"


Xobj.Send param

If Int(xobj.Status) = 200 Then
	Code = xobj.ResponseText
	strCookies = Xobj.getResponseHeader("Set-Cookie")
	'strCookies = Xobj.getAllResponseHeaders()
	pageSize = Len(Code)
Else
	Code = "Server can not accessible at the moment!"
	pageSize = 0
End If

Set Xobj = Nothing

SearchFROM = "<label id=""metrics_totalArticleCount"">"
strTO = "</label>"
dblFROM = InStr(1,Code,SearchFROM)
dblFROM = dblFROM + Len(SearchFROM)
dblTO = InStr(dblFROM,Code,strTO)
dblTO = dblTO - dblFROM
ResearcherID_totalArticleCount = Trim(Mid(Code,dblFROM,dblTO))


SearchFROM = "<label id=""metrics_articleCountForMetrics"">"
strTO = "</label>"
dblFROM = InStr(1,Code,SearchFROM)
dblFROM = dblFROM + Len(SearchFROM)
dblTO = InStr(dblFROM,Code,strTO)
dblTO = dblTO - dblFROM
ResearcherID_articleCountForMetrics = Trim(Mid(Code,dblFROM,dblTO))


SearchFROM = "<label id=""metrics_timesCited"">"
strTO = "</label>"
dblFROM = InStr(1,Code,SearchFROM)
dblFROM = dblFROM + Len(SearchFROM)
dblTO = InStr(dblFROM,Code,strTO)
dblTO = dblTO - dblFROM
ResearcherID_timesCited = Trim(Mid(Code,dblFROM,dblTO))


SearchFROM = "<label id=""metrics_averagePerItem"">"
strTO = "</label>"
dblFROM = InStr(1,Code,SearchFROM)
dblFROM = dblFROM + Len(SearchFROM)
dblTO = InStr(dblFROM,Code,strTO)
dblTO = dblTO - dblFROM
ResearcherID_averagePerItem = Trim(Mid(Code,dblFROM,dblTO))

SearchFROM = "<label id=""metrics_hindex"">"
strTO = "</label>"
dblFROM = InStr(1,Code,SearchFROM)
dblFROM = dblFROM + Len(SearchFROM)
dblTO = InStr(dblFROM,Code,strTO)
dblTO = dblTO - dblFROM
ResearcherID_hindex = Trim(Mid(Code,dblFROM,dblTO))


SearchFROM = "<label id=""metrics_lastUpdatedString"">"
strTO = "</label>"
dblFROM = InStr(1,Code,SearchFROM)
dblFROM = dblFROM + Len(SearchFROM)
dblTO = InStr(dblFROM,Code,strTO)
dblTO = dblTO - dblFROM
ResearcherID_lastUpdatedString = Trim(Mid(Code,dblFROM,dblTO))

 
End Function






Function PrefixCheck()
    If Len(prefix) > 1 Then
        PrefixCheck = prefix
    Else 
        PrefixCheck = ""
    End If
End Function


Function ScriptAll()
	If Len(GScholar) > 0 Then
	Response.Write PrefixCheck() & "Google_ID = """ & GScholar & """;"
	Response.Write "Google_URL = """ & GURL & """;"
	Response.Write "Google_Citations = " & GCitations & ";"
	Response.Write "Google_hIndex = " & GhIndex & ";"
	Response.Write "Google_i10Index = " & Gi10Index & ";"
	End If
	
	If Len(SScopusID) > 0 Then
	Response.Write "Scopus_ID = """ & SScopusID & """;"
	Response.Write "Scopus_URL = """ & SURL & """;"
	Response.Write "Scopus_Documents = " & SDocuments & ";"
	Response.Write "Scopus_hIndex = " & ShIndex & ";"
	Response.Write "Scopus_CoAuthors = " & SCoAuthors & ";"
	Response.Write "Scopus_References = " & SReferences & ";"
	End If
	
	If Len(RGName) > 0 Then
	Response.Write "RG_ID = """ & RGName & """;"
	Response.Write "RG_URL = """ & RGURL & """;"
	Response.Write "RG_Score = """ & RGScore & """;"
	Response.Write "RG_Publications = """ & RGPublications & """;"
	Response.Write "RG_Views = """ & RGViews & """;"
	Response.Write "RG_Downloads = """ & RGDownloads & """;"
	Response.Write "RG_Citations = """ & RGCitations & """;"
	Response.Write "RG_ImpactPoints = """ & RGImpactPoints & """;"
	End If
	
	If Len(ReID) > 0 Then
	Response.Write "ReID_ID = """ & ReID & """;"
	Response.Write "ReID_URL = """ & ReIDURL & """;"
	Response.Write "ResearcherID_totalArticleCount = """ & ResearcherID_totalArticleCount & """;"
	Response.Write "ResearcherID_articleCountForMetrics = """ & ResearcherID_articleCountForMetrics & """;"
	Response.Write "ResearcherID_timesCited = """ & ResearcherID_timesCited & """;"
	Response.Write "ResearcherID_averagePerItem = """ & ResearcherID_averagePerItem & """;"
	Response.Write "ResearcherID_hindex = """ & ResearcherID_hindex & """;"
	Response.Write "ResearcherID_lastUpdatedString = """ & ResearcherID_lastUpdatedString & """;"
	End If
	
End Function

Function PrintAll()
	Response.Write "Google Citations: " & GCitations & "<br />"
	Response.Write "Google h-Index: " & GhIndex & "<br />"
	Response.Write "Google i10-Index: " & Gi10Index & "<br />"

	Response.Write "Scopus Documents: " & SDocuments & "<br />"
	Response.Write "Scopus h-Index: " & ShIndex & "<br />"
	Response.Write "Scopus Co-Authors: " & SCoAuthors & "<br />"
	Response.Write "Scopus References: " & SReferences & "<br />"

	Response.Write "RG Score: " & RGScore & "<br />"
	Response.Write "RG Publications: " & RGPublications & "<br />"
	Response.Write "RG Views: " & RGViews & "<br />"
	Response.Write "RG Downloads: " & RGDownloads & "<br />"
	Response.Write "RG Citations: " & RGCitations & "<br />"
	Response.Write "RG Impact Points: " & RGImpactPoints & "<br />"
	
	Response.Write "ResearcherID_totalArticleCount: " & ResearcherID_totalArticleCount & "<br />"
	Response.Write "ResearcherID_articleCountForMetrics: " & ResearcherID_articleCountForMetrics & "<br />"
	Response.Write "ResearcherID_timesCited: " & ResearcherID_timesCited & "<br />"
	Response.Write "ResearcherID_averagePerItem: " & ResearcherID_averagePerItem & "<br />"
	Response.Write "ResearcherID_hindex: " & ResearcherID_hindex & "<br />"
	Response.Write "ResearcherID_lastUpdatedString: " & ResearcherID_lastUpdatedString & "<br />"
End Function
%>
