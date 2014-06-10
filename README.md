livereference
=============

# in-text citation tool

Livereference revolutionizes the obscure field of scholarly paper presentation. It is a simple jQuery/PHP based referencing tool designed for web developers and on-line publishers that makes literature citing easy and improves reading experience.
Publication can be referenced by ISBN or PMID id or local TSV or EndNote file. Referenced publication appears in tooltip like window.

## Example and usage


### HTML

```html
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>live reference project</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script src="http://code.jquery.com/jquery-latest.js"></script>
<link rel="stylesheet" media="screen" href="css/livereference.css" />
<script type="text/javascript" src="js/livereference.jquery.js"></script>

<script language="javascript">
$(document).ready(function() {
	$('.liverefLinkClass').livereference({ 
		sAjaxSource: 'php/livereference.php',
		sTSVSource: '../livereference.tsv', // relative to php module directory
		sEndNoteSource: '../endnote.xml',
		sWidgetsSource: '../widgets.xml',
	});
});
</script>
</head>
<body>
<ul>
	<li><span class="liverefLinkClass" data-lrerf="ISBN:0805843000">[1]</span> -  Google Books reference</li>
	<li><span class="liverefLinkClass" data-lrerf="PMID:12740104">[2]</span> - PubMed reference</li>
	<li><span class="liverefLinkClass" data-lrerf="TSV:2388089">[3]</span> - TSV file reference</li>
	<li><span class="liverefLinkClass" data-lrerf="ENDNOTE:15450156">[4]</span> - EndNote reference</li>
	<li><span class="liverefLinkClass" data-lrerf="WIDGET:1">[5]</span> -  Amazon.com widget</li>
</ul>
</body>
</html>
```

Demo and more examples in "examples" folder and in [http://www.livereference.org/livereference/](http://www.livereference.org/livereference/)
