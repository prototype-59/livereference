<?php
/*
-------------------------------------------------------------------------------
	module:	data provider for livereference jQuery plugin
	author: Aleksandar Radovanovic
	examples and documentation at: http://livereference.org/livereference
-------------------------------------------------------------------------------
*/

// define some constants
define ("GOOGLE_BOOKS_API", "https://www.googleapis.com/books/v1/volumes?fields=items(volumeInfo)&q=isbn");
define ("PUBMED_API", "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?");
define("ID_TYPE", 0);	// define indexes of input parameters
define("ID_VALUE", 1);

// publication particulars
class Publication
{
 	var $publication = array (
 	'id'			=> '', // in a form: LOCAL:$id, r ISBN:$id or PMID:$id 
	'title'			=> '', // publication title
	'subtitle'		=> '', // publication subtitle
	'journal'		=> '', // journal name
	'author'		=> '', // publication author
	'publisher'		=> '', // publication publisher
	'pubdate'		=> '',  // publication date
	'url'			=>	''   // link to publication	on the web
	);			
};

// get input parameters
$id = explode(':', empty( $_GET["id"]) ? 'none:0' : $_GET["id"], 2); // get the id in the form: type:id
$endnote = empty( $_GET["endnote"]) ? 'nofile' : $_GET["endnote"];	// EndNote references
$widgets = empty( $_GET["widgets"]) ? 'nofile' : $_GET["widgets"];	// widgets source
$localfile = empty( $_GET["lf"]) ? 'nofile' : $_GET["lf"];	// local reference file
$cstyle = empty( $_GET["cstyle"]) ? 'vancouver' : $_GET["cstyle"];	// citation style

// process the request
switch ($id[ID_TYPE])
{
	case "none":
		print "no id is given";
		break;
	case "PMID":
		print getPubMedArticle ($id[ID_VALUE], $cstyle);
		break;
	case "ISBN":
		print getGoogleBookInfo ($id[ID_VALUE], $cstyle);
		break;
	case 'ENDNOTE':
		print getEndNoteReference ($id[ID_VALUE], $endnote, $cstyle);
		break;
	case 'WIDGET':
		print getWidgetReference ($id[ID_VALUE], $widgets);
		break;
	case "TSV":
		print getTSVReference ($id[ID_VALUE],$localfile, $cstyle);
		break;
	default:
		print "no valid reference source is given";
		break;
}

/*
-------------------------------------------------------------------------------
 function:		getWidgetReference ($id, $widgets)
 description:	get widget code from the local xml file
-------------------------------------------------------------------------------
*/
function getEndNoteReference ($id, $endnotefile, $cstyle)
{
	$xml = simplexml_load_file($endnotefile);
	if (!$xml) {	// miss-formatted EndNote file
		$output = "miss-formatted or missing EndNote file: $endnotefile";	
	 	return ($output);	
	}
	$publication = new Publication;
	$publication->id = "REF:$id";
	$publication->title = "Endnote reference $id not found";
	$records = $xml->records;
	foreach ($records->record as $record)
	{
		if ($record->{'rec-number'} == $id) {
			$publication->title = $record->titles->title->style;
			$publication->journal = $record->titles->{'secondary-title'}->style;
			$publication->pubdate = isset($record->dates->year->style) ? $record->dates->year->style ." " : "";
			$publication->pubdate .= isset($record->dates->{'pub-dates'}->date->style) ? $record->dates->{'pub-dates'}->date->style : "";
			$publication->url  = isset($record->urls->{'related-urls'}->url->style) ? $record->urls->{'related-urls'}->url->style : "";
			$publication->author = "";
			foreach ($record->contributors->authors->author as $author)
			{
				$publication->author .= $author->style;
			}
			break;
		}
	}
	$output = formatLiveRef ($publication, $cstyle);
 	return ($output);
}

/*
-------------------------------------------------------------------------------
 function:		getWidgetReference ($id, $widgets)
 description:	get widget code from the local xml file
-------------------------------------------------------------------------------
*/
function getWidgetReference ($id, $widgetsfile)
{
	$xml = simplexml_load_file($widgetsfile);
	if (!$xml) {	// miss-formatted widget file
		$output = "miss-formatted widget file: $widgetsfile";	
	 	return ($output);	
	}
	$output = "widget  $id not found";
	foreach ($xml->widget as $widget)
	{
		if ($widget->id == $id) {
			$output =  $widget->src ."<br/><span style='cursor:pointer'>x</span>";
			break;
		}
	}
	return($output);
}

/*
-------------------------------------------------------------------------------
 function:		getLocalReference ($id, $localfile)
 description:	get publication info from a local tab delimited file:
 				id[0] title[1] subtitle[2] author[3] journal[4] pubdate[5] url[6]
-------------------------------------------------------------------------------
*/
function getTSVReference ($id, $localfile, $cstyle)
{
	// check if local references holding file exists
	if ($localfile == 'nofile') { return ('no reference source is given'); }
	$handle = @fopen ($localfile, "r");
	if (!$handle) {return ('failed to open reference file');}
	$found = false;
	$output = "reference not found";
	$publication = new Publication;
	while (!feof($handle) && !$found) 
 	{ 
 		$line = explode("\t", fgets($handle));
 		if ($line[0] == $id)
 		{
 			$found = TRUE;
 			$publication->id = "REF:$id";
			$publication->title = $line[1];
			$publication->subtitle = $line[2];
			$publication->journal = $line[4];
			$publication->author = $line[3];
			$publication->pubdate = $line[5];
			$publication->url  = $line[6];		
		}
 	} 
 	fclose($handle);
 	$output = formatLiveRef ($publication, $cstyle);
 	return ($output);
}

/*
-------------------------------------------------------------------------------
 function:		getGoogleBookInfo ($id)
 description:	get the book info from Google Books
-------------------------------------------------------------------------------
*/
function getGoogleBookInfo ($id, $cstyle)
{
	$bookJSON = file_get_contents (GOOGLE_BOOKS_API .$id);
	$bookinfo = json_decode ($bookJSON, true);
	$publication = new Publication;
	$publication->id = "ISBN:$id";
	$publication->title = $bookinfo['items'][0]['volumeInfo']['title'];
	$publication->subtitle = isset($bookinfo['items'][0]['volumeInfo']['subtitle']) ? $bookinfo['items'][0]['volumeInfo']['subtitle'] : "";
	$publication->pubdate = isset($bookinfo['items'][0]['volumeInfo']['publishedDate']) ? $bookinfo['items'][0]['volumeInfo']['publishedDate'] : "";
	$publication->url = $bookinfo['items'][0]['volumeInfo']['infoLink'];	
	$publication->author = "";
	foreach ($bookinfo['items'][0]['volumeInfo']['authors'] as $author)
	{
		$publication->author .= "$author, ";
	}
	$publication->author = rtrim($publication->author,', ');
	$output = formatLiveRef ($publication, $cstyle);
 	return ($output);
}

/*
-------------------------------------------------------------------------------
 function:		getPubMedArticle ($id)
 description:	get the article information from the PubMed database
-------------------------------------------------------------------------------
*/
function getPubMedArticle ($id, $cstyle)
{
	$PubMedParams = array (
		'db' => 'pubmed',
		'retmode' => 'xml',
		'retmax' => 1,
		'usehistory' => 'n',
		'id' => $id,
	);
	$efetch = PUBMED_API .http_build_query($PubMedParams);
	$publication = new Publication;
	$xml = @simplexml_load_file($efetch);
	if (!$xml) {	// if NCBI is offline
		$publication->id = "PMID:$id";
		$publication->title = "NCBI server is temporarily unable to service your request.";	
		$output = formatLiveRef ($publication, $cstyle);
	 	return ($output);	
	}	
	$publication->id = "PMID:$id";
	$publication->title = implode ($xml->xpath('PubmedArticle/MedlineCitation/Article/ArticleTitle'));
	$publication->subtitle = "";
	$publication->journal = implode ($xml->xpath('PubmedArticle/MedlineCitation/Article/Journal/Title'));
	$publication->author = "";
	$publication->pubdate = implode ($xml->xpath('PubmedArticle/MedlineCitation/Article/Journal/JournalIssue/PubDate/Month')) ."-" .
		implode ($xml->xpath('PubmedArticle/MedlineCitation/Article/Journal/JournalIssue/PubDate/Year'));
	$publication->url = "http://www.ncbi.nlm.nih.gov/pubmed/$id";
	foreach($xml->xpath('PubmedArticle/MedlineCitation/Article/AuthorList') as $author) 
	{
		foreach($author as $eachAuthor)
		{
			$publication->author .= $eachAuthor->ForeName ." " .$eachAuthor->LastName .", "; 
		}
	}
	$output = formatLiveRef ($publication, $cstyle);
 	return ($output);
}

/*
-------------------------------------------------------------------------------
 function:		formatLiveRef ($publication)
 description:	create HTML output from the publication details
 input:			publication information
					citation  style; apa is default
-------------------------------------------------------------------------------
*/

function formatLiveRef ($publication, $cstyle = 'vancouver')
{
	$output = "<div class='lrRef'>";
	switch ($cstyle)
	{
		case 'apa':
			$output .= empty($publication->author) ?  "" : "<span class='lrRefNormal'>" .$publication->author ."&nbsp;</span>";
			$output .= empty($publication->ubdate) ?  "" : "<span class='lrRefNormal'>(" .$publication->pubdate .")</span>";
			$output .= "<span class='lrRefBlock lrRefVSpacer'>&nbsp;</span>";						
			$output .= "<span class='lrRefBlock lrRefItalic lrRefBigger'>" .$publication->title ."</span>";
			$output .= empty($publication->subtitle) ? "<span class='lrRefBlock lrRefVSpacer'>&nbsp;</span>" : "<span class='lrRefItalic lrRefBlock'>" .$publication->subtitle ."</span>";
			$output .= empty($publication->journal) ?  "" : "<span class='lrRefNormal'>" .$publication->journal ."</span>";
			$output .= empty($publication->url) ?  "" : "<span class='lrRefBlock liverefURL'><a href='" .$publication->url ."' target='_blank'>" .$publication->id ."</a></span>";
			break;
		case 'vancouver':
			$output .= empty($publication->author) ?  "" : "<span class='lrRefNormal'>" .$publication->author ."</span>";
			$output .= "<span class='lrRefBlock lrRefVSpacer'>&nbsp;</span>";						
			$output .= "<span class='lrRefBlock lrRefItalic lrRefBigger'>" .$publication->title ."</span>";
			$output .= empty($publication->subtitle) ? "<span class='lrRefVSpacer'>&nbsp;</span>" : "<span class='lrRefItalic lrRefBlock'>" .$publication->subtitle ."</span>";
			$output .= "<span class='lrRefBlock lrRefVSpacer'>&nbsp;</span>";
			$output .= empty($publication->journal) ?  "" : "<span class='lrRefNormal'>" .$publication->journal .".&nbsp</span>";
			$output .= empty($publication->pubdate) ?  "" : "<span class='lrRefNormal'>" .$publication->pubdate ."</span>";
			$output .= empty($publication->url) ?  "" : "<span class='lrRefBlock liverefURL'><a href='" .$publication->url ."' target='_blank'>" .$publication->id ."</a></span>";

			break;
	}
	$output .= "</div>";
	return ($output);
}
?>