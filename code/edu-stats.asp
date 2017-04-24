<%@Language="VBScript" %>
<%
'**************************************************
' Edu-Stats v.1.9.1
' Academic Statistics Parser
' Google Scholar, Scopus, ResearcherID
'
'
' Developed by Kaveh Bakhtiyari ( http://www.bakhtiyari.com )
' v1.9.1: 24 April 2017
'**************************************************
'Option Explicit
Server.ScriptTimeOut = 2147483647
Server.ScriptTimeOut = 7000
'On Error Resume Next

EduStatsVersion = "1.9.1"
UserAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36"

'Google Scholar Variables
GCitations = -1
GhIndex = -1
Gi10Index = -1

'Scopus Variables
SDocuments = -1
ShIndex = -1
SCoAuthors = -1
SReferences = -1

'ResearcherID Variables
ResearcherID_totalArticleCount = -1
ResearcherID_articleCountForMetrics = -1
ResearcherID_timesCited = -1
ResearcherID_averagePerItem = -1
ResearcherID_hindex = -1
ResearcherID_lastUpdatedString = -1

GScholar = Request("google")
SScopusID = Request("scopus")
ReID = Request("reid")
prefix = Request("prefix")
output = Request("output")


If Len(GScholar) > 0 Then
	GURL = "http://scholar.google.com/citations?user=" & GScholar & "&hl=en"
	GoogleScholar(GURL)
End If

If Len(SScopusID) > 0 Then
	SURL = "https://www.scopus.com/authid/detail.uri?authorId=" & SScopusID
	Scopus(SURL)
End If

If Len(ReID) > 0 Then
	ReIDURL = "http://www.researcherid.com/rid/" & ReID
	ResearcherID(ReIDURL)
End If

Select Case output
Case "json"
	JSONAll
Case Else
	ScriptAll
End Select



 
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
    If Len(prefix) > 1 AND Len(prefix) <= 10 Then
        PrefixCheck = prefix & "_"
    Else 
        PrefixCheck = ""
    End If
End Function


Function ScriptAll()

	Response.Write "EduStats = """ & EduStatsVersion & """;"
	
	If Len(GScholar) > 0 Then
	Response.Write PrefixCheck() & "Google_ID = """ & GScholar & """;"
	Response.Write PrefixCheck() & "Google_URL = """ & GURL & """;"
	Response.Write PrefixCheck() & "Google_Citations = " & GCitations & ";"
	Response.Write PrefixCheck() & "Google_hIndex = " & GhIndex & ";"
	Response.Write PrefixCheck() & "Google_i10Index = " & Gi10Index & ";"
	End If
	
	If Len(SScopusID) > 0 Then
	Response.Write PrefixCheck() & "Scopus_ID = """ & SScopusID & """;"
	Response.Write PrefixCheck() & "Scopus_URL = """ & SURL & """;"
	Response.Write PrefixCheck() & "Scopus_Documents = " & SDocuments & ";"
	Response.Write PrefixCheck() & "Scopus_hIndex = " & ShIndex & ";"
	Response.Write PrefixCheck() & "Scopus_CoAuthors = " & SCoAuthors & ";"
	Response.Write PrefixCheck() & "Scopus_References = " & SReferences & ";"
	End If
		
	If Len(ReID) > 0 Then
	Response.Write PrefixCheck() & "ReID_ID = """ & ReID & """;"
	Response.Write PrefixCheck() & "ReID_URL = """ & ReIDURL & """;"
	Response.Write PrefixCheck() & "ResearcherID_totalArticleCount = """ & ResearcherID_totalArticleCount & """;"
	Response.Write PrefixCheck() & "ResearcherID_articleCountForMetrics = """ & ResearcherID_articleCountForMetrics & """;"
	Response.Write PrefixCheck() & "ResearcherID_timesCited = """ & ResearcherID_timesCited & """;"
	Response.Write PrefixCheck() & "ResearcherID_averagePerItem = """ & ResearcherID_averagePerItem & """;"
	Response.Write PrefixCheck() & "ResearcherID_hindex = """ & ResearcherID_hindex & """;"
	Response.Write PrefixCheck() & "ResearcherID_lastUpdatedString = """ & ResearcherID_lastUpdatedString & """;"
	End If
	
End Function

Function JSONAll()
	strJSON = ""
	strJSON = strJSON & "{""EduStats"":""" & EduStatsVersion & """" & vbCrLf
	
	If Len(GScholar) > 0 Then
		strJSON = strJSON & ","
		strJSON = strJSON & """Google"":" & vbCrLf
		strJSON = strJSON & vbTab & "{""ID"":""" & GScholar & """," & vbCrLf
		strJSON = strJSON & vbTab & """URL"":""" & GURL & """," & vbCrLf
		strJSON = strJSON & vbTab & """Citations"":" & GCitations & "," & vbCrLf
		strJSON = strJSON & vbTab & """hIndex"":" & GhIndex & "," & vbCrLf
		strJSON = strJSON & vbTab & """i10Index"":" & Gi10Index & "}" & vbCrLf
	End If
	
	If Len(SScopusID) > 0 Then
		strJSON = strJSON & ","
		strJSON = strJSON & """Scopus"":" & vbCrLf
		strJSON = strJSON & vbTab & "{""ID"":""" & SScopusID & """," & vbCrLf
		strJSON = strJSON & vbTab & """URL"":""" & SURL & """," & vbCrLf
		strJSON = strJSON & vbTab & """Documents"":" & SDocuments & "," & vbCrLf
		strJSON = strJSON & vbTab & """hIndex"":" & ShIndex & "," & vbCrLf
		strJSON = strJSON & vbTab & """CoAuthors"":" & SCoAuthors & "," & vbCrLf
		strJSON = strJSON & vbTab & """References"":" & SReferences & "}" & vbCrLf
	End If
		
	If Len(ReID) > 0 Then
		strJSON = strJSON & ","
		strJSON = strJSON & """ResearcherID"":" & vbCrLf
		strJSON = strJSON & vbTab & "{""ID"":""" & ReID & """," & vbCrLf
		strJSON = strJSON & vbTab & """URL"":""" & ReIDURL & """," & vbCrLf
		strJSON = strJSON & vbTab & """totalArticleCount"":" & ResearcherID_totalArticleCount & "," & vbCrLf
		strJSON = strJSON & vbTab & """articleCountForMetrics"":" & ResearcherID_articleCountForMetrics & "," & vbCrLf
		strJSON = strJSON & vbTab & """timesCited"":" & ResearcherID_timesCited & "," & vbCrLf
		strJSON = strJSON & vbTab & """averagePerItem"":" & ResearcherID_averagePerItem & "," & vbCrLf
		strJSON = strJSON & vbTab & """hindex"":" & ResearcherID_hindex & "," & vbCrLf
		strJSON = strJSON & vbTab & """lastUpdatedString"":""" & ResearcherID_lastUpdatedString & """}" & vbCrLf
	End If
	
	strJSON = strJSON & "}"
	Response.Write strJSON 
End Function
%>
