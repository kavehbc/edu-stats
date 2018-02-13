<%@Language="VBScript" %>
<%
'**************************************************
' Edu-Stats v.2.0.0
' Academic Statistics Parser
' Google Scholar, Scopus, ResearcherID, Mendeley
'
'
' Developed by Kaveh Bakhtiyari ( http://www.bakhtiyari.com )
' v2.0.0: 13 February 2018
'**************************************************
'Option Explicit
Server.ScriptTimeOut = 2147483647
Server.ScriptTimeOut = 7000
'On Error Resume Next

EduStatsVersion = "2.0.0"
UserAgent = "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36"

'Google Scholar Variables
GCitations = -1
GhIndex = -1
Gi10Index = -1

'Scopus Variables
Scopus_Documents = -1
Scopus_hIndex = -1
Scopus_CoAuthors = -1
Scopus_CitationDocuments = -1

'ResearcherID Variables
ResearcherID_totalArticleCount = -1
ResearcherID_articleCountForMetrics = -1
ResearcherID_timesCited = -1
ResearcherID_averagePerItem = -1
ResearcherID_hindex = -1
ResearcherID_lastUpdatedString = -1

'Mendeley Variables
Mendeley_Media = -1
Mendeley_hIndex = -1
Mendeley_Citations = -1
Mendeley_Readers = -1
Mendeley_Views = -1

GScholar = Request("google")
ScopusID = Request("scopus")
ReID = Request("reid")
MendeleyID = Request("mendeley")

prefix = Request("prefix")
output = Request("output")


If Len(GScholar) > 0 Then
	GURL = "http://scholar.google.com/citations?user=" & GScholar & "&hl=en"
	GoogleScholar(GURL)
End If

If Len(ScopusID) > 0 Then
	ScopusURL = "https://www.scopus.com/authid/detail.uri?authorId=" & SScopusID
	Scopus(ScopusURL)
End If

If Len(ReID) > 0 Then
	ReIDURL = "http://www.researcherid.com/rid/" & ReID
	ResearcherID(ReIDURL)
End If

If Len(MendeleyID) > 0 Then
	MendeleyURL = "https://www.mendeley.com/profiles/" & MendeleyID & "/stats/"
	Mendeley(MendeleyURL)
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
	Code = "Server is not accessible at the moment!"
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
Xobj.setRequestHeader "User-Agent", UserAgent
Xobj.SetRequestHeader "Content-type", "text/html"
Xobj.SetRequestHeader "Connection", "close"


Xobj.Send

If Int(xobj.Status) = 200 Then
	Code = xobj.ResponseText
	strCookies = Xobj.getResponseHeader("Set-Cookie")
	'strCookies = Xobj.getAllResponseHeaders()
	pageSize = Len(Code)
Else
	Code = "Server is not accessible at the moment!"
	pageSize = 0
End If
		 
Set Xobj = Nothing

Params = ""

Set Xobj = Server.CreateObject("Msxml2.ServerXMLHTTP.3.0")
Xobj.Open "GET",URL,false
Xobj.setRequestHeader "User-Agent", UserAgent
Xobj.SetRequestHeader "Referer", URL
Xobj.setRequestHeader "Cookie", strCookies
Xobj.SetRequestHeader "Content-type", "text/html"
Xobj.SetRequestHeader "Content-length", Len(Params) + Len (strCookies)
Xobj.SetRequestHeader "Connection", "close"

Xobj.Send Params

If Int(xobj.Status) = 200 Then
	Code = xobj.ResponseText
	strCookies = Xobj.getResponseHeader("Set-Cookie")
	'strCookies = Xobj.getAllResponseHeaders()
	pageSize = Len(Code)
Else
	Code = "Server is not accessible at the moment!"
	pageSize = 0
End If



SearchFROM = "<section id=""authorDetailsHindex"">"
startPoint = InStr(1,Code,SearchFROM)

SearchFROM = "<span class=""fontLarge"">"
strTO = "</span>"
dblFROM = InStr(startPoint,Code,SearchFROM)
dblFROM = dblFROM + Len(SearchFROM)
dblTO = InStr(dblFROM,Code,strTO)
startPoint = dblTO
dblTO = dblTO - dblFROM

strResult = Trim(Mid(Code,dblFROM,dblTO))
	If Len(strResult) > 0 Then
		strConverted = Replace(strResult,",","")
		Scopus_hIndex = strConverted
	End If


SearchFROM = "<section id=""authorDetailsDocumentsByAuthor"">"
startPoint = InStr(1,Code,SearchFROM)

SearchFROM = "<span class=""fontLarge pull-left"">"
strTO = "</span>"
dblFROM = InStr(startPoint,Code,SearchFROM)
dblFROM = dblFROM + Len(SearchFROM)
dblTO = InStr(dblFROM,Code,strTO)
startPoint = dblTO
dblTO = dblTO - dblFROM

strResult = Trim(Mid(Code,dblFROM,dblTO))
	If Len(strResult) > 0 Then
		strConverted = Replace(strResult,",","")
		Scopus_Documents = strConverted
	End If

SearchFROM = "<section id=""authorDetailsTotalCitations"">"
startPoint = InStr(1,Code,SearchFROM)

SearchFROM = "<span class=""btnText"">"
strTO = "</span>"
dblFROM = InStr(startPoint,Code,SearchFROM)
dblFROM = dblFROM + Len(SearchFROM)
dblTO = InStr(dblFROM,Code,strTO)
startPoint = dblTO
dblTO = dblTO - dblFROM

strResult = Trim(Mid(Code,dblFROM,dblTO))
	If Len(strResult) > 0 Then
		strConverted = Replace(strResult,",","")
		Scopus_CitationDocuments = strConverted
	End If


SearchFROM = "<a id=""coAuthLi"" data-toggle=""tab"""
startPoint = InStr(1,Code,SearchFROM)

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
		Scopus_CoAuthors = strConverted
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
	Code = "Server is not accessible at the moment!"
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
	Code = "Server is not accessible at the moment!"
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




Function Mendeley(URL)

Params = ""

Set Xobj = Server.CreateObject("Msxml2.ServerXMLHTTP.3.0")
Xobj.Open "GET",URL,false
Xobj.setRequestHeader "User-Agent", UserAgent
Xobj.SetRequestHeader "Content-type", "text/html"
Xobj.SetRequestHeader "Connection", "close"

Xobj.Send

If Int(xobj.Status) = 200 Then
	Code = xobj.ResponseText
	pageSize = Len(Code)
Else
	Code = "Server is not accessible at the moment!"
	pageSize = 0
End If

SearchFROM = "Media mentions</span></h3></header><data class=""number"" value="""
strTO = """>"
dblFROM = InStr(1,Code,SearchFROM)
dblFROM = dblFROM + Len(SearchFROM)
dblTO = InStr(dblFROM,Code,strTO)
startPoint = dblTO
dblTO = dblTO - dblFROM

strResult = Trim(Mid(Code,dblFROM,dblTO))
	If Len(strResult) > 0 Then
		strConverted = Replace(strResult,",","")
		Mendeley_Media = strConverted
	End If


SearchFROM = "h-index</span></h3></header><data class=""number"" value="""
strTO = """>"
dblFROM = InStr(startPoint,Code,SearchFROM)
dblFROM = dblFROM + Len(SearchFROM)
dblTO = InStr(dblFROM,Code,strTO)
startPoint = dblTO
dblTO = dblTO - dblFROM

strResult = Trim(Mid(Code,dblFROM,dblTO))
	If Len(strResult) > 0 Then
		strConverted = Replace(strResult,",","")
		Mendeley_hIndex = CDbl(strConverted)
	End If

SearchFROM = "Citations</span></h3></header><data class=""number"" value="""
strTO = """>"
dblFROM = InStr(startPoint,Code,SearchFROM)
dblFROM = dblFROM + Len(SearchFROM)
dblTO = InStr(dblFROM,Code,strTO)
startPoint = dblTO
dblTO = dblTO - dblFROM

strResult = Trim(Mid(Code,dblFROM,dblTO))
	If Len(strResult) > 0 Then
		strConverted = Replace(strResult,",","")
		Mendeley_Citations = CDbl(strConverted)
	End If

SearchFROM = "Readers</span></h3></header><data class=""number"" value="""
strTO = """>"
dblFROM = InStr(startPoint,Code,SearchFROM)
dblFROM = dblFROM + Len(SearchFROM)
dblTO = InStr(dblFROM,Code,strTO)
startPoint = dblTO
dblTO = dblTO - dblFROM

strResult = Trim(Mid(Code,dblFROM,dblTO))
	If Len(strResult) > 0 Then
		strConverted = Replace(strResult,",","")
		Mendeley_Readers = CDbl(strConverted)
	End If

SearchFROM = "Views</span></h3></header><data class=""number"" value="""
strTO = """>"
dblFROM = InStr(startPoint,Code,SearchFROM)
dblFROM = dblFROM + Len(SearchFROM)
dblTO = InStr(dblFROM,Code,strTO)
startPoint = dblTO
dblTO = dblTO - dblFROM

strResult = Trim(Mid(Code,dblFROM,dblTO))
	If Len(strResult) > 0 Then
		strConverted = Replace(strResult,",","")
		Mendeley_Views = CDbl(strConverted)
	End If


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
	
	If Len(ScopusID) > 0 Then
	Response.Write PrefixCheck() & "Scopus_ID = """ & ScopusID & """;"
	Response.Write PrefixCheck() & "Scopus_URL = """ & ScopusURL & """;"
	Response.Write PrefixCheck() & "Scopus_Documents = " & Scopus_Documents & ";"
	Response.Write PrefixCheck() & "Scopus_hIndex = " & Scopus_hIndex & ";"
	Response.Write PrefixCheck() & "Scopus_CoAuthors = " & Scopus_CoAuthors & ";"
	Response.Write PrefixCheck() & "Scopus_CitationDocuments = " & Scopus_CitationDocuments & ";"
	End If
		
	If Len(ReID) > 0 Then
	Response.Write PrefixCheck() & "ResearcherID_ID = """ & ReID & """;"
	Response.Write PrefixCheck() & "ResearcherID_URL = """ & ReIDURL & """;"
	Response.Write PrefixCheck() & "ResearcherID_Documents = """ & ResearcherID_totalArticleCount & """;"
	Response.Write PrefixCheck() & "ResearcherID_articleCountForMetrics = """ & ResearcherID_articleCountForMetrics & """;"
	Response.Write PrefixCheck() & "ResearcherID_Citations = """ & ResearcherID_timesCited & """;"
	Response.Write PrefixCheck() & "ResearcherID_AverageCitations = """ & ResearcherID_averagePerItem & """;"
	Response.Write PrefixCheck() & "ResearcherID_hIndex = """ & ResearcherID_hindex & """;"
	Response.Write PrefixCheck() & "ResearcherID_lastUpdatedString = """ & ResearcherID_lastUpdatedString & """;"
	End If

	If Len(MendeleyID) > 0 Then
	Response.Write PrefixCheck() & "Mendeley_ID = """ & MendeleyID & """;"
	Response.Write PrefixCheck() & "Mendeley_URL = """ & MendeleyURL & """;"
	Response.Write PrefixCheck() & "Mendeley_Media = " & Mendeley_Media & ";"
	Response.Write PrefixCheck() & "Mendeley_hIndex = " & Mendeley_hIndex & ";"
	Response.Write PrefixCheck() & "Mendeley_Citations = " & Mendeley_Citations & ";"
	Response.Write PrefixCheck() & "Mendeley_Readers = " & Mendeley_Readers & ";"
	Response.Write PrefixCheck() & "Mendeley_Views = " & Mendeley_Views & ";"
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
	
	If Len(ScopusID) > 0 Then
		strJSON = strJSON & ","
		strJSON = strJSON & """Scopus"":" & vbCrLf
		strJSON = strJSON & vbTab & "{""ID"":""" & ScopusID & """," & vbCrLf
		strJSON = strJSON & vbTab & """URL"":""" & ScopusURL & """," & vbCrLf
		strJSON = strJSON & vbTab & """Documents"":" & Scopus_Documents & "," & vbCrLf
		strJSON = strJSON & vbTab & """hIndex"":" & Scopus_hIndex & "," & vbCrLf
		strJSON = strJSON & vbTab & """CoAuthors"":" & Scopus_CoAuthors & "," & vbCrLf
		strJSON = strJSON & vbTab & """CitationDocuments"":" & Scopus_CitationDocuments & "}" & vbCrLf
	End If
		
	If Len(ReID) > 0 Then
		strJSON = strJSON & ","
		strJSON = strJSON & """ResearcherID"":" & vbCrLf
		strJSON = strJSON & vbTab & "{""ID"":""" & ReID & """," & vbCrLf
		strJSON = strJSON & vbTab & """URL"":""" & ReIDURL & """," & vbCrLf
		strJSON = strJSON & vbTab & """Documents"":" & ResearcherID_totalArticleCount & "," & vbCrLf
		strJSON = strJSON & vbTab & """articleCountForMetrics"":" & ResearcherID_articleCountForMetrics & "," & vbCrLf
		strJSON = strJSON & vbTab & """Citations"":" & ResearcherID_timesCited & "," & vbCrLf
		strJSON = strJSON & vbTab & """AverageCitations"":" & ResearcherID_averagePerItem & "," & vbCrLf
		strJSON = strJSON & vbTab & """hIndex"":" & ResearcherID_hindex & "," & vbCrLf
		strJSON = strJSON & vbTab & """lastUpdatedString"":""" & ResearcherID_lastUpdatedString & """}" & vbCrLf
	End If

	If Len(MendeleyID) > 0 Then
		strJSON = strJSON & ","
		strJSON = strJSON & """Mendeley"":" & vbCrLf
		strJSON = strJSON & vbTab & "{""ID"":""" & MendeleyID & """," & vbCrLf
		strJSON = strJSON & vbTab & """URL"":""" & MendeleyURL & """," & vbCrLf
		strJSON = strJSON & vbTab & """Media"":" & Mendeley_Media & "," & vbCrLf
		strJSON = strJSON & vbTab & """hIndex"":" & Mendeley_hIndex & "," & vbCrLf
		strJSON = strJSON & vbTab & """Citations"":" & Mendeley_Citations & "," & vbCrLf
		strJSON = strJSON & vbTab & """Readers"":" & Mendeley_Readers & "," & vbCrLf
		strJSON = strJSON & vbTab & """Views"":" & Mendeley_Views & "}" & vbCrLf
	End If
	
	strJSON = strJSON & "}"
	Response.Write strJSON 
End Function
%>
