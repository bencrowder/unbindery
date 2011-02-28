<?php

include_once('include/config.php');
include_once('include/Alibaba.class.php');
include_once('Database.class.php');
include_once('User.class.php');

Alibaba::forceAuthentication();

// make sure they're admin and can upload items

$slug = $_GET["slug"];

$includes = "<link href='$SITEROOT/lib/uploadify/uploadify.css' type='text/css' rel='stylesheet' />\n";
$includes .= "<script type='text/javascript' src='$SITEROOT/lib/uploadify/swfobject.js'></script>\n";
$includes .= "<script type='text/javascript' src='$SITEROOT/lib/uploadify/jquery.uploadify.v2.1.4.min.js'></script>\n";
$includes .= "<script type='text/javascript'>\n";
$includes .= "	$(document).ready(function() {\n";
$includes .= "		$('#file_upload').uploadify({\n";
$includes .= "			'uploader'  : '$SITEROOT/lib/uploadify/uploadify.swf',\n";
$includes .= "			'script'    : '$SITEROOT/admin/upload_backend/',\n";
$includes .= "			'cancelImg' : '$SITEROOT/lib/uploadify/cancel.png',\n";
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
			<input type="hidden" name="project_slug" id="project_slug" value="<?php echo $slug; ?>" />
		</div>
	</div>

<?php include('include/footer.php'); ?>