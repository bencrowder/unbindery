<?php

include_once('include/config.php');
include_once('include/Alibaba.class.php');
include_once('Database.class.php');

Alibaba::forceAuthentication();

// make sure they're admin and can upload items

$slug = $_GET["slug"];

$includes = "<link href='$SITEROOT/uploadify/uploadify.css' type='text/css' rel='stylesheet' />\n";
$includes .= "<script type='text/javascript' src='$SITEROOT/uploadify/swfobject.js'></script>\n";
$includes .= "<script type='text/javascript' src='$SITEROOT/uploadify/jquery.uploadify.v2.1.4.min.js'></script>\n";
$includes .= "<script type='text/javascript'>\n";
$includes .= "	$(document).ready(function() {\n";
$includes .= "		$('#file_upload').uploadify({\n";
$includes .= "			'uploader'  : '$SITEROOT/uploadify/uploadify.swf',\n";
$includes .= "			'script'    : '$SITEROOT/upload_items_backend/',\n";
$includes .= "			'cancelImg' : '$SITEROOT/uploadify/cancel.png',\n";
$includes .= "			'folder'    : '/images/$slug',\n";
$includes .= "			'fileDataName' : 'items',\n";
$includes .= "			'removeCompleted' : false,\n";
$includes .= "			'multi'     : true,\n";
$includes .= "			'auto'      : true,\n";
$includes .= "			'onAllComplete' : function(event, data) {\n";
$includes .= "				load_items_for_editing(event, data);\n";
$includes .= "			}\n";
$includes .= "		});\n";
$includes .= "	});\n";
$includes .= "</script>\n";

include_once('include/header.php');

?>
	<div id="main">
		<h2>Upload Items</h2>

		<div id="uploadcol">
			<input id="file_upload" name="file_upload" type="file" />
		</div>

		<div id="items">
			<form id="save_items" action="<?php echo $SITEROOT; ?>/save_items_backend/" method="post">
				<div id="itemlist"></div>
				<input type="submit" class="button" value="Save Changes" />
				<input type="hidden" id="project_slug" name="project_slug" value="<?php echo $slug; ?>" />
			</form>
		</div>
	</div>
</body>
</html>
