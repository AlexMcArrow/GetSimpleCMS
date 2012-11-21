<?php 
/**
 * Edit Theme
 *
 * Allows you to edit a theme file
 *
 * @package GetSimple
 * @subpackage Theme
 */

global $TEMPLATE_FILE;

# setup inclusions
$load['plugin'] = true;
include('inc/common.php');

# variable settings
login_cookie_check();
$theme_options 		= ''; 
$TEMPLATE_FILE 		= ''; 
$template 			= ''; 
$theme_templates 	= '';

# were changes submitted?
if (isset($_GET['t'])) {
	$_GET['t'] = strippath($_GET['t']);
	if ($_GET['t']&&is_dir(GSTHEMESPATH . $_GET['t'].'/')) {
		$TEMPLATE = $_GET['t'];
	}
}
if (isset($_GET['f'])) {
	$_GET['f'] = strippath($_GET['f']);
	if ($_GET['f']&&is_file(GSTHEMESPATH . $TEMPLATE.'/'.$_GET['f'])) {
		$TEMPLATE_FILE = $_GET['f'];
	}
}

# check for form submission
if((isset($_POST['submitsave']))){
	
	# check for csrf
	if (!defined('GSNOCSRF') || (GSNOCSRF == FALSE) ) {
		$nonce = $_POST['nonce'];
		if(!check_nonce($nonce, "save")) {
			die("CSRF detected!");
		}
	}
	
	# save edited template file
	$SavedFile = $_POST['edited_file'];
	$FileContents = get_magic_quotes_gpc() ? stripslashes($_POST['content']) : $_POST['content'];	
	$fh = fopen(GSTHEMESPATH . $SavedFile, 'w') or die("can't open file");
	fwrite($fh, $FileContents);
	fclose($fh);
	$success = sprintf(i18n_r('TEMPLATE_FILE'), $SavedFile);
}

# if no template is selected, use the default
if (! $TEMPLATE_FILE) {
	$TEMPLATE_FILE = 'template.php';
}

# create themes dropdown
$themes_path = GSTHEMESPATH;
$themes_handle = opendir($themes_path);
$theme_options .= '<select class="text" name="t" id="theme-folder" >';	
while ($file = readdir($themes_handle)) {
	$curpath = $themes_path .'/'. $file;
	if( is_dir($curpath) && $file != "." && $file != ".." ) {
		$theme_dir_array[] = $file;
		$sel="";
		
		if (file_exists($curpath.'/template.php')){
			if ($TEMPLATE == $file){ 
				$sel="selected"; 
			}
			
			$theme_options .= '<option '.$sel.' value="'.$file.'" >'.$file.'</option>';
		}
	}
}
$theme_options .= '</select> ';

# check to see how many themes are available
if (count($theme_dir_array) == 1){ $theme_options = ''; }

# if no template is selected, use the default
if ($template == '') { $template = 'template.php'; }
$templates = directoryToArray(GSTHEMESPATH . $TEMPLATE . '/', true);

$directory = GSTHEMESPATH . $TEMPLATE . '/';

$theme_templates .= '<span id="themefiles"><select class="text" id="theme_files" style="width:425px;" name="f" >';
$allowed_extensions=array('php','css','js','html','htm','txt','');

$theme_templates .= createFileDropdown($templates);


//////////////////////////////////////////////////
// File Manager
//////////////////////////////////////////////////


function createFileDropdown($templates){
	GLOBAL $TEMPLATE_FILE,$TEMPLATE,$allowed_extensions;
	
	$theme_templates = '';

	foreach ($templates as $file){
	  $extension=pathinfo($file,PATHINFO_EXTENSION);
	  if (in_array($extension, $allowed_extensions)){
	  $filename=pathinfo($file,PATHINFO_BASENAME);
	  $filenamefull=substr(strstr($file,'/theme/'.$TEMPLATE.'/'),strlen('/theme/'.$TEMPLATE.'/'));   
	  if ($TEMPLATE_FILE == $filenamefull){ 
	          $sel="selected"; 
	  } else { 
	          $sel="";
	  }
	  if ($filename == 'template.php'){ 
	          $templatename=i18n_r('DEFAULT_TEMPLATE'); 
	  } else { 
	          $templatename=$filenamefull; 
	  }
	  $theme_templates .= '<option '.$sel.' value="'.$templatename.'" >'.$templatename.'</option>';
	  }
	}
	$theme_templates .= "</select></span>";
	return $theme_templates;
}

function array2ul($array) {
	GLOBAL $allowed_extensions,$TEMPLATE_FILE,$TEMPLATE;
    
	$cnt = 0;

    $out="<ul>";
    foreach($array as $key => $elem){
    	
    	// todo: replace this with an actual loop counter for valid filetypes.
    	if(strtolower($key) == 'images') continue;

        if(!is_array($elem['value'])){
    		$ext = lowercase(pathinfo($elem['value'], PATHINFO_EXTENSION));
    		
    		// Is a file
    		if( in_array($ext,$allowed_extensions) ){

    			$filename = $elem['value'];
					$filepath = $elem['path'];    			
					$filenamefull=substr(strstr($filepath.DIRECTORY_SEPARATOR.$filename,'/theme/'.$TEMPLATE.'/'),strlen('/theme/'.$TEMPLATE.'/')); 

    			$open = fileIsOpen($elem['path'],$elem['value']) ? ' open' : '';
  				if ($filename == 'template.php'){
  					$ext = 'theme';
  					$filename=i18n_r('DEFAULT_TEMPLATE');        			
  				}	
				
				$link = myself(false).'?t='.$TEMPLATE.'&amp;f='.$filenamefull;

				$out.='<li><a href="'.$link.'"class="file ext-'.$ext.$open.'">'.$filename."</a></li>";
        	}
        }
        else {
        	// Is a folder
        	$out.='<li><a class="directory">'.$key.'</a>'.array2ul($elem['value']).'</li>';
        }	
    }
    $out=$out."</ul>";
    return $out; 
}

function fileIsOpen($path,$file){
	GLOBAL $TEMPLATE,$TEMPLATE_FILE;
    $file = $path.DIRECTORY_SEPARATOR.$file;
    $filename=pathinfo($file,PATHINFO_BASENAME);
    $filenamefull=substr(strstr($file,'/theme/'.$TEMPLATE.'/'),strlen('/theme/'.$TEMPLATE.'/')); 
	# _debugLog($file,$TEMPLATE_FILE,$filename,$filenamefull);
	return $TEMPLATE_FILE == $filenamefull;
}

function compareOrder($a, $b)
{
		$atype = $a['type'];
		$btype = $b['type'];

		// directories first
    if ($atype!=$btype){
    	return strcmp($atype,$btype);
    }

    // sort directories by key
    if($atype == 'directory' and $btype == 'directory'){
    	return strcmp(key($a['value']),key($b['value']));
    } 
    
    // sort files by value
    if($atype == 'file' and $btype == 'file'){
    	return strcmp($a['value'],$b['value']);
    } 
}


$files = directoryToMultiArray($directory);
#_debugLog($TEMPLATE_FILE);
#_debugLog($files);
uasort($files, 'compareOrder');
# uksort($files, 'compareOrder');
$fileList = array2ul($files);

if (!defined('GSNOHIGHLIGHT') || GSNOHIGHLIGHT!=true){
	register_script('codemirror', $SITEURL.'admin/template/js/codemirror/lib/codemirror-compressed.js', '0.2.0', FALSE);
	
	register_style('codemirror-css',$SITEURL.'admin/template/js/codemirror/lib/codemirror.css','screen',FALSE);
	register_style('codemirror-theme',$SITEURL.'admin/template/js/codemirror/theme/default.css','screen',FALSE);
	
	queue_script('codemirror', GSBACK);
	
	queue_style('codemirror-css', GSBACK);
	queue_style('codemirror-theme', GSBACK);

}

get_template('header', cl($SITENAME).' &raquo; '.i18n_r('THEME_MANAGEMENT')); 
?>

<?php include('template/include-nav.php');

if (!defined('GSNOHIGHLIGHT') || GSNOHIGHLIGHT!=true){

	switch (pathinfo($TEMPLATE_FILE,PATHINFO_EXTENSION)) {
		case 'css':
			$mode = 'text/css';
			break;
		case 'js':
			$mode = 'text/javascript';
			break;
		case 'html':
			$mode = 'text/html';
			break;
		default:
			$mode = 'application/x-httpd-php';
	}

?>

<script>
window.onload = function() {
	  var foldFunc = CodeMirror.newFoldFunction(CodeMirror.braceRangeFinder);
	  function keyEvent(cm, e) {
	    if (e.keyCode == 81 && e.ctrlKey) {
	      if (e.type == "keydown") {
	        e.stop();
	        setTimeout(function() {foldFunc(cm, cm.getCursor().line);}, 50);
	      }
	      return true;
	    }
	  }
	  function toggleFullscreenEditing()
	    {
	        var editorDiv = $('.CodeMirror-scroll');
	        if (!editorDiv.hasClass('fullscreen')) {
	            toggleFullscreenEditing.beforeFullscreen = { height: editorDiv.height(), width: editorDiv.width() }
	            editorDiv.addClass('fullscreen');
	            editorDiv.height('100%');
	            editorDiv.width('100%');
	            editor.refresh();
	        }
	        else {
	            editorDiv.removeClass('fullscreen');
	            editorDiv.height(toggleFullscreenEditing.beforeFullscreen.height);
	            editorDiv.width(toggleFullscreenEditing.beforeFullscreen.width);
	            editor.refresh();
	        }
	    }
      var editor = CodeMirror.fromTextArea(document.getElementById("codetext"), {
        lineNumbers: true,
        matchBrackets: true,
        indentUnit: 4,
        indentWithTabs: true,
        enterMode: "keep",
        mode:"<?php echo $mode; ?>",
        tabMode: "shift",
        theme:'default',
    	onGutterClick: foldFunc,
    	extraKeys: {"Ctrl-Q": function(cm){foldFunc(cm, cm.getCursor().line);},
    				"F11": toggleFullscreenEditing, "Esc": toggleFullscreenEditing},
        onCursorActivity: function() {
		   	editor.setLineClass(hlLine, null);
		   	hlLine = editor.setLineClass(editor.getCursor().line, "activeline");
		}
      	});
     var hlLine = editor.setLineClass(0, "activeline");
    
     }
     
</script>
<?php 
}
?>
<div class="bodycontent clearfix">
	
	<div id="maincontent">
		<div class="main">
		<h3><?php i18n('EDIT_THEME'); ?></h3>
		
		<!-- float wrapper -->
		<div id="theme_edit_wrap">

			<!-- left nav  -->
			<div id="theme_edit_nav">

				<!-- Theme Selector -->
				<div id="theme_edit_select">
					<div class="well"><?php echo $theme_options; ?>	</div>
				</div>

				<!-- File Tree -->
				<div id="theme_filemanager">
					<?php echo $fileList; ?>
				</div>
			</div>

			<div id="theme_edit_code">
				
<!-- 				<form action="<?php myself(); ?>" method="get" accept-charset="utf-8" >
				<p><?php echo $theme_options; ?><?php echo $theme_templates; ?>&nbsp;&nbsp;&nbsp;<input class="submit" type="submit" name="s" value="<?php i18n('EDIT'); ?>" /></p>
				</form> -->
				

				<div class="well">
				<?php i18n('EDITING_FILE'); ?>: <?php echo $SITEURL.'theme/'. tsl($TEMPLATE) .'<b>'. $TEMPLATE_FILE .'</b>'; ?>
				<?php $content = file_get_contents(GSTHEMESPATH . tsl($TEMPLATE) . $TEMPLATE_FILE); ?>
				</div>

				<form action="<?php myself(); ?>?t=<?php echo $TEMPLATE; ?>&amp;f=<?php echo $TEMPLATE_FILE; ?>" method="post" >
					<input id="nonce" name="nonce" type="hidden" value="<?php echo get_nonce("save"); ?>" />
					<textarea name="content" id="codetext" wrap='off' ><?php echo htmlentities($content, ENT_QUOTES, 'UTF-8'); ?></textarea>
					<input type="hidden" value="<?php echo tsl($TEMPLATE) . $TEMPLATE_FILE; ?>" name="edited_file" />
					<?php exec_action('theme-edit-extras'); ?>
					<p id="submit_line" >
						<span><input class="submit" type="submit" name="submitsave" value="<?php i18n('BTN_SAVECHANGES'); ?>" /></span> &nbsp;&nbsp;<?php i18n('OR'); ?>&nbsp;&nbsp; <a class="cancel" href="theme-edit.php?cancel"><?php i18n('CANCEL'); ?></a>
					</p>
				</form>
			
			</div>
			
			<!-- float clear -->
			<div class="clear"></div>
			
			</div>
		</div>	
	</div>
	
	<div id="sidebar" >
		<?php include('template/sidebar-theme.php'); ?>
	</div>
</div>
<?php get_template('footer'); ?>
