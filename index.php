<?php
/*
This is version 1.9 of Josh's indexer!
(C) 2003 Joshua Szmajda (php@loki.ws)
You're free to use and distribute this program, as long
as you send any code changes to me, and don't sell it
without my permission. This isn't a legal notice, as I'm
not a lawyer, so I reserve the right to sue you if I don't
get a piece of whatever pie you got into with this program ;)

To run this program you'll need 2 things: php and 
"convert". Convert is a part of the ImageMagick package 
(http://www.imagemagick.org).

INSTALLATION:
Place the index.php file wherever you have the root of 
your images. Open the file in a text editor, and then 
between the "CONFIGURATION STARTS HERE" and "CONFIGURATION 
ENDS HERE" bits there are some variables that need to be 
set. there are comments just above each, but if you run 
into any trouble, let me know and I'll try and help you out.

You'll need to set each directory that you want indexed 
world-writeable, or at least writeable by the webserver user. 
For example, you'll probably need to execute this command 
(as root) from the root of your images directory tree: 
"chmod -R a+w ." This is so that php can execute the convert 
command and create files and subdirectories for the thumbnail 
and low-quality versions of the images. You'll need to execute
that command, or something like it every time you add pictures.
(I wish you didn't need to do this, but such is the ways of
security ;)

A couple less-documented features:
1) you can place a file named "description.txt" in any indexed 
folder. The text file will then be shown at the top of the page, 
just under the title.
2) if you create a file called "preview.jpg" and place it in a 
folder, the indexer will use that image to make the thumbnail 
for the directory instead of the first image it finds. preview.jpg 
won't be indexed. I tend to pick whatever image I'd like to be 
the thumbnail, then just copy that to preview.jpg. (This creates a
file called pv_thumb.jpg, which is also unindexed.)

Good luck! Send any questions / comments / concerns to php@loki.ws
*/
###################################################################
#### CONFIGURATION STARTS HERE ####################################
###################################################################

//this is the name that goes into the title for the site
$site_name = "Your site here";

//Where to put generated files. You need to set this dir writable by the webserver user
$cacheDir = "iidx";

//this is the path to convert (`which convert`)
$convert = '/usr/bin/convert';
// if that dosen't work, try this line, and change the path  vvv here
#$convert = 'LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/usr/local/lib /usr/local/bin/convert';

//this is the sorting algorithm to use: the choices are:
// jdircmp: sorts directories in the form "MM-DD-YYYY (Description)"
// ndircmp: sorts directories by natural case-insensitive comparison (e.g.: 1 2 3 a B c)
// dircmp: sorts directories by timestamp
$cmpfunc = "jdircmp";

// standard or calendar (coming soon!)
// for calendar mode, you have to use jdircmp and name dirs in the form "MM-DD-YYYY (Description)"
$dirDisplayMode = "calendar";

// Which way do you want the thumbnails in a dir to be orgainzed? square or full?
// square uses thumbSize for both dimensions, and dosen't print a filename
// full uses thumbWidth and thumbHeight for the dimensions, and prints filename along with 'low quality' and 'high quality' links
$thumbMode = "square";

//These control the size and color of the general border around the thumbs in square mode
$squareBorderSize = 7;
$squareBorderColor = "#AAAAAA";

//Allow linking to full quality version? (true or false)
$fullQLink = true;

//Show 'details' link at bottom of main page? (true or false)
$viewDetailsLink = false;

//Number of pictures to show in a row on directory contents view
$dirContentsWidth = 5;

// Standard Display Mode Colors
// background color of the site
$bgcolor = "#CCCCCC";
// general text color
$textColor = "#000000";
// unvisited link color
$linkColor = "#222255";
// active link color
$alinkColor = "#444466";
// visited link color
$vlinkColor = "#551111";
// 'outer' table color
$tdBgcolor = "#888888";
// 'inner' table color
$tdBgcolor2 = "#CCCCCC";
// text color of the footer (copyright info etc)
$footTextColor = "#111111";
// background color for caption text
$captionBGColor = "#AAAAAA";

// general font face
$fontFace = "Verdana, Arial, Helvetica, sans-serif";
// general font size
$fontSize = "-2";
// title font size
$titleFontSize = "+2";
// image title font size
$imageTitleFontSize = "-1";

//dimentions for thumbnails
$thumbSize = 100;
$thumbWidth = 120;
$thumbHeight = 90;
// dimentions for low-quality images
$lowWidth = 800;
$lowHeight = 600;
// dimentions for directory previews
$pv_thumbWidth = 100;
$pv_thumbHeight = 75;

//Calendar Mode Color Definitions
$Calendar_borderTable_borderColor="#888888";
$Calendar_dates_fontColor="#000000";
$Calendar_link_fontColor="##222244";

//this is the line that's shown at the top of each page
$titleLine = "<p><center><font size=\"$titleFontSize\" face=\"$fontFace\">Camera Pictures</font></center></p>";

#########################################################################
#### ADVANCED CONFIG: only edit this if you know what you're doing! #####
#########################################################################
$Calendar_borderTable=".bordertable {  border: $Calendar_borderTable_borderColor; border-style: solid; border-top-width: 1px; border-right-width: 1px; border-bottom-width: 1px; border-left-width: 1px}";
$Calendar_dates=".dates {  font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 9px; color: $Calendar_dates_fontColor}";
$Calendar_link=".link {  font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 9px; color: $Calendar_link_fontColor; text-decoration: none}";
$extraStyle=".mainthumb {border: 1px solid #444444;}";
$Styles="$Calendar_borderTable\n$Calendar_dates\n$Calendar_link\n$extraStyle";

####################################################################
#### CONFIGURATION ENDS HERE #######################################
####################################################################
//No more register globals hack
/*
if(!(bool) ini_get('register_gobals')){
  if (!empty($_REQUEST)) while(list($inname, $invalue) = each($_REQUEST)) $$inname = $invalue;
  #if (!empty($HTTP_POST_VARS)) while(list($inname, $invalue) = each($HTTP_POST_VARS)) $$inname = $invalue;
  if (!empty($_SERVER)) while(list($inname, $invalue) = each($_SERVER)) $$inname = $invalue;
}
*/
ignore_user_abort(true);
$auto = $_REQUEST{'auto'};
$img = $_REQUEST{'img'};
$mode = $_REQUEST{'mode'};
$timer = $_REQUEST{'timer'};
$viewdate = $_REQUEST{'viewdate'};
$where = $_REQUEST{'where'};
$PHP_SELF = $_SERVER{'PHP_SELF'};
$prefix = $_SERVER{'SCRIPT_FILENAME'};
$ppsar = preg_split("/\//",$prefix);
array_pop($ppsar);
$prefix = implode("/",$ppsar);
$ppsar = preg_split("/\//",$PHP_SELF);
array_pop($ppsar);
$relative = implode("/",$ppsar);

$footerLine="
<!-- <center><img src=http://haven.loki.ws/artistic.gif></center><br> -->
<font size=-2 face=\"$fontFace\" color=\"$footTextColor\"><center>This php thing &copy; <a href=\"mailto:php@loki.ws\">Joshua Szmajda</a> 2002 - 2005</center></font>
<script language=javascript src=\"http://loki.ws/awstats_misc_tracker.js\"></script>
</body></html>";
// if we didn't get a path, use the default one of just the prefix
$add = '';
if(!$where){
	$rwhere = $relative;
	$where = $prefix;
	$add = '';
} else {
        $where = preg_replace("/\\\\/","",$where); #strip backslashes
        if(strstr($where,"..")) $where = "";
        if(!is_dir($prefix.'/'.$where)) $where = "";
        $add = $where;
        if(!preg_match("/^\/$/",$relative)){
            $rwhere = $relative . '/' . $where;
        } else {
            $rwhere = $where;
        }
        $where = $prefix . ((!preg_match("/^\//",$where))?'/':'') . $where;
}
$add = preg_replace("/^\//","",$add); //strip initial slashes
$rwhere = preg_replace("/\/\//","/",$rwhere);
$where .= '/'; //need / for find's weirdness
if($dirDisplayMode=="calendar" && $cmpfunc != "jdircmp") $dirDisplayMode="standard";
$changedDDM=0;
$oldDDM=$dirDisplayMode;
if($ddMode){
	$dirDisplayMode=$ddMode;
	$changedDDM=1;
}

function checkfiletypes($file){
	$filetypes = array ("wpd", "txt", "avi", "rm", "mpg", "mpeg", "url", "mov", "wmv");

	foreach($filetypes as $type){
		//if(stristr($file,".$type") and !(stristr($file,"description.txt")) and !(stristr($file,"captions.txt"))){
		if(preg_match("/$type$/i",$file) and !(stristr($file,"description.txt")) and !(stristr($file,"captions.txt"))){
			return 1;
		}
	}
	return 0;
}

function dircmp($a, $b){
	global $where;
	$astat = stat("$where"."$a");
	$bstat = stat("$where"."$b");

	if($astat[9] == $bstat[9]) return 0;
	return($astat[9] > $bstat[9] ? -1 : 1);
}


function jdircmp($a, $b){
	$olda = $a;
	$oldb = $b;
        $a = substr($a, 0, 10);
        $b = substr($b, 0, 10);
 
        $fulla = split("-",$a);
        $fullb = split("-",$b);
 
        if($fulla[2] == $fullb[2]){
                if($fulla[0] == $fullb[0]){
                        if($fulla[1] == $fullb[1]){
				if((strlen($olda) > 10) && (strlen($oldb) > 10)){
					$na = substr($olda,11,strlen($olda));
					$nb = substr($oldb,11,strlen($oldb));
					return -1 * strnatcasecmp($na,$nb);
				} else {
                                	return 0;
				}
                        } else {
                                return ($fulla[1] > $fullb[1]) ? -1 : 1;
                        }
                } else {
                        return ($fulla[0] > $fullb[0]) ? -1 : 1;
                }
        } else {
                return ($fulla[2] > $fullb[2]) ? -1 : 1;
        }
}

function ndircmp($a, $b){
	return -1 * strnatcasecmp($a,$b);
}

function junker($val){
	return ((!strstr($val,"Low")) and (!strstr($val,"tn")));
}

		
function array_filter_it($array, $callback) {
	$farray = array ();
  	while(list($key,$val) = each($array))
   	if ($callback($val))
    		$farray[$key] = $val;
 	return $farray;
}

function deprefix($array,$where){
	// hack off the prefixes from each element in our list
	$count = 0;
	foreach($array as $e){
		$stuff = split("/",$e);
		$array[$count] = array_pop($stuff);;
		$count++;
	}

	return $array;
}

function ckdir($dir){
    $dir = preg_replace("/\/\//","/",$dir);
    if(is_dir($dir)) return true;
    $stack = array(basename($dir));
    $path = null;
    while ( ($d = dirname($dir) ) ) {
	if ( !is_dir($d) ) {
	    $stack[] = basename($d);
	    $dir = $d;
	} else {
	    $path = $d;
	    break;
	}
    }

    if ( ( $path = realpath($path) ) === false )
	return false;

    $created = array();
    for ( $n = count($stack) - 1; $n >= 0; $n-- ) {
	$s = $path . '/'. $stack[$n];                                     
	if ( !mkdir($s) ) {
	    for ( $m = count($created) - 1; $m >= 0; $m-- )
		rmdir($created[$m]);
	    return false;
	}
	$created[] = $s;     
	$path = $s;
    }
    return true;
}

function cvt($filename,$dir,$dest,$x,$y) {
	global $convert,$cacheDir,$add,$prefix;
	if(!is_writable("$prefix/$cacheDir")){ print "can't create thumbnails, $prefix/$cacheDir is not writable<br>\n"; exit(0); }
	ckdir("$prefix/$cacheDir/$add/$dest");
	$odir = $dir;
	$dir = "$prefix/$cacheDir/$add";
        $cmd = "$convert -resize $x".'x'."$y \"$odir/$filename\" \"$dir/$dest/$filename\"";
	`umask 0000`;
 
	if(stristr($filename,".jpg") or stristr($filename,".png")){
                `$cmd`;
		chmod("$dir/$dest/$filename",0777);
                return $filename;
        } else if (stristr($filename,".gif")){
                $cmd = "$convert -resize $x".'x'."$y \"$odir/$filename\" \"$dir/$dest/$filename.jpg\"";
                `$cmd`;
                `mv $dir/$dest/$filename.jpg.0 $dir/$dest/$filename.jpg`;
                `rm -f $dir/$dest/$filename.jpg.*`;
                $cmd = "$convert -resize $x".'x'."$y \"$dir/$dest/$filename.jpg\" \"$dir/$dest/$filename.jpg\"";
                `$cmd`;
		chmod("$dir/$dest/$filename.jpg",0777);
                return "$filename.jpg";
}	}

function sqcvt($filename,$dir,$dest,$x,$y) {
        global $convert,$thumbSize,$cacheDir,$add,$prefix;
        if(!is_writable("$prefix/$cacheDir")){ print "can't create thumbnails, $prefix/$cacheDir is not writable<br>\n"; exit(0); }
	ckdir("$prefix/$cacheDir/$add/$dest");
	$odir = $dir;
	$dir = "$prefix/$cacheDir/$add";
        $cmd = "$convert -resize $x".'x'."$y \"$odir/$filename\" \"$dir/$dest/$filename\"";
	`umask 0000`;

        if (stristr($filename,".gif")){
                $cmd = "$convert -resize $x".'x'."$y \"$odir/$filename\" \"$dir/$dest/$filename.jpg\"";
                `$cmd`;
                `mv $dir/$dest/$filename.jpg.0 $dir/$dest/$filename.jpg`;
                `rm -f $dir/$dest/$filename.jpg.*`;
                $filename = "$filename.jpg";
        }
        if($x <= $thumbSize or $y <= $thumbSize){
		$trandint = rand(1000,9999);
		$tmpFile = "$dir/$dest/tmp-$trandint.jpg";
                #first resize to a temp file, then crop to the dest
                $rcmd = "";
                $info = getimagesize("$odir/$filename");
                if($info[0] > $info[1]){
                        $rcmd = "99999x$thumbSize";
                } else {
                        $rcmd = $thumbSize."x99999";
                }
                $cmd = "$convert -resize $rcmd \"$odir/$filename\" \"$tmpFile\"";
                #print "c1: $cmd<br>";
                `$cmd`;
                //determine new size info
                $info = getimagesize("$tmpFile");
                $width = $info[0];
                $height = $info[1];
                #print "new w:$width h:$height<br>";
                $cropcmd = $thumbSize."x$thumbSize+";
                if($width > $height){
                        $offset = (int)(($width - $thumbSize) / 2);
                        $cropcmd .= $offset."+0";
                } else {
                        $offset = (int)(($height - $thumbSize) / 2);
                        $cropcmd .= "0+$offset";
                }
                $cmd = "$convert -crop $cropcmd \"$tmpFile\" \"$dir/$dest/$filename\"";
                #print "c2: $cmd<br>";
                `$cmd`;
		$cmd = "rm -f \"$tmpFile\"";
		`$cmd`;
        } else {
                `$cmd`;
        }
	chmod("$dir/$dest/$filename",0777);
        return $filename;
}

function genpreview($dir,$reldir,$add,$link,$imgonly){
	global $convert,$relative,$pv_thumbWidth,$pv_thumbHeight,$prefix,$cacheDir,$add;
	$source = "";
	#print "checking $dir/preview.jpg\n<br>";
	if(file_exists("$dir/preview.jpg")){
		$source = "$dir/preview.jpg";
	} else {
		$pics = array ("");
		if($han = opendir("$dir")){
        		while( false !== ($file = readdir($han))){
                		if(stristr($file,".jpg") or stristr($file,".png") or stristr($file,".gif")){
					if($file != "preview.jpg" and $file != "pv_thumb.jpg"){
						array_push($pics,$file);
					}
		                }
		        }
		}
		natcasesort($pics);
		array_shift($pics);
		$source = "$dir/".$pics[0];
	}
	if($source != ""){
		$flip=0;
		$size = @getimagesize($source);
		if($size && $size[1] > $size[0]){ $flip=1; }
		$cdir = "$prefix/$cacheDir/" . urldecode("$add/$reldir");
		ckdir($cdir);
		if(!file_exists("$cdir/pv_thumb.jpg") or (filectime($source) > filectime("$cdir/pv_thumb.jpg"))){
			# here is where to check
			$cmd = "$convert -resize $pv_thumbWidth"."x"."$pv_thumbHeight \"$source\" \"$cdir/pv_thumb.jpg\"";
			if($flip){
				$cmd = "$convert -resize $pv_thumbHeight"."x"."$pv_thumbWidth \"$source\" \"$cdir/pv_thumb.jpg\"";
			}
			`$cmd`;
			$cmd = "chmod a+rwx \"$cdir/pv_thumb.jpg\"";
			`$cmd`;
		}
		if(file_exists("$cdir/pv_thumb.jpg")){
			$x=$pv_thumbWidth;
			$y=$pv_thumbHeight;
			if($flip){
				$x=$y;
				$y=$pv_thumbWidth;
			}
			if(!$imgonly) print "<a href=\"$link\"><center>";
			$im = "";
			if($add != ""){
				$im = "$relative/$cacheDir/$add/$reldir/pv_thumb.jpg";
			} else {
				$im = "$cacheDir/$reldir/pv_thumb.jpg";
			}
			$im = preg_replace("/\/\//","/",$im);
			print "<img class=\"mainthumb\" border=\"0\" width=\"$x\" height=\"$y\" src=\"$im\">";
			if(!$imgonly) print "</center></a>";
		}
	}
}

function getPixArray($where){
	$pics = array ("");
	if($han = opendir("$where")){
		while( false !== ($file = readdir($han))){
			if(stristr($file,".jpg") or stristr($file,".png") or stristr($file,".gif")){
				if($file != "preview.jpg" and $file != "pv_thumb.jpg"){
					array_push($pics,$file);
				}
			}
		}
	}
		

	natcasesort($pics);
	array_shift($pics);
	return $pics;
}

function crumnav($add,$imnm){
	global $fontSize,$fontFace,$viewdate,$PHP_SELF;
	if(!preg_match("/^\//",$add)){
		$add="/$add";
	}
	$fsu="<font size=\"$fontSize\" face=\"$fontFace\">";
	print "<tr><td><table border=0><tr><td>$fsu"."You are here:</font></td><td>$fsu<a href=\"$PHP_SELF";
	if($viewdate) print "?viewdate=$viewdate";
	print "\" accesskey=\"h\"><b>H</b>ome</a></font></td>";
	$dpts = preg_split("/\//",$add);
	$oarr=array();
	while(count($dpts) > 0){
		array_push($oarr,"<td>$fsu/</font></td>");
		$updir = join("/",$dpts);
		if(strlen($updir) > 2){
			$uplink = $PHP_SELF."?where=".runc($updir);
			if($viewdate) $uplink .= "&viewdate=yes";
		} else {
			$uplink = $PHP_SELF;
			if($viewdate) $uplink .= "?viewdate=$viewdate";
		}
		if(strlen($updir)>1){
			$uppts = preg_split("/\//",$updir);
			$updir=array_pop($uppts);
			array_push($oarr,"<td>$fsu<a href=\"$uplink\">$updir</a></font></td>");
		}
		array_pop($dpts);
	}
	$oarr=array_reverse($oarr);
	array_pop($oarr);
	foreach ($oarr as $oa){
		print $oa;
	}
	if($imnm){
		print "<td>$fsu/</font></td><td>$fsu"."$imnm</font></td>";
	}
	print "</tr></table></td><tr>\n";
}

function runc($tx){
	$tx = rawurlencode($tx);
	$tx = preg_replace("/\%2F/","/",$tx);
	return $tx;
}

function getCaption($filename,$path){
    if(file_exists($path."/captions.txt")){
	$fh = fopen($path."/captions.txt","r");
	if($fh){
	    while(!feof($fh)){
		$line = fgets($fh,4096);
		if(preg_match("/^$filename/i",$line)){
		    return substr($line,strpos($line," "));
		}
	    }
	}
    }
}
?>
<html>
<head>
<style>
<?echo $Styles?>
</style>
<title>Pictures at <? echo $site_name ?>
<?php

if($where){
	
	if( ereg("\(",$where) ){
		$stuff = split("\(",$where);
		$title = substr($stuff[1],0,-2);
		print " - ";
		print $title;
	}
}
?>
<?
if($mode == "single"){
	$parts = preg_split("/\//",$img);
	array_shift($parts);
	$file = array_pop($parts);
	$origdir = join("/",$parts);
	#array_shift($parts);
	if(strlen($relative) > 2){
		#array_shift($parts);
		if(preg_match("/$parts[0]/",$relative)){
			array_shift($parts);
		}
	}
	$dir = join("/",$parts);
	$dir = preg_replace("/\\\\/","",$dir);
	$origdir = preg_replace("/\\\\/","",$origdir);

	$wpts = preg_split("/\//",$dir);
	if(ereg("Low",$wpts[count($wpts) - 1])){
		array_pop($wpts);
	}
	$wheredir = join("/",$wpts);
	$dir="/$dir";

	$tfl = preg_replace("/\\\\/","",$file);
	$tfl=preg_replace("/%20/"," ",$tfl);
	$timg = preg_replace("/\\\\/","",$img);
	$timg = preg_replace("/'/","%27",$timg);
	$timg = preg_replace("/,/","%2C",$timg);
	$timg = preg_replace("/\\(/","%28",$timg);
	$timg = preg_replace("/\\)/","%29",$timg);
	$timg = preg_replace("/\/\//","/",$timg);
	$pics = getPixArray($prefix."/".$cacheDir.$dir);
	$idx = array_search($tfl,$pics);
	$next = $idx+1;
	$prev = $idx-1;
	$size = count($pics) - 1;
	if($next > $size) $next = 0;
	if($prev < 0) $prev = $size;
	$nfile = $pics[$next];
	$pfile = $pics[$prev];
	$nfile = runc($cacheDir."/".$origdir."/".$nfile);
	$pfile = runc($cacheDir."/".$origdir."/".$pfile);
	$nlink = "$PHP_SELF?mode=single&img=$nfile";
	$plink = "$PHP_SELF?mode=single&img=$pfile";
	print "</title>";
	$autolink = $nlink."&auto=true&timer=$timer";
	#$autolink = preg_replace("/ /","%20",$autolink);
	$backlink = $PHP_SELF."?where=".runc($wheredir);
	if($auto == "true"){
		print "<meta HTTP-EQUIV=\"Refresh\" Content=\"$timer;URL=$autolink\">";
	}
	print "<style>\n";
	print "#imaage {border: 2px solid #444444;}\n";
	print "</style>\n";
	print "</head>";
	print "<body bgcolor=\"$bgcolor\" text=\"$textColor\" link=\"$linkColor\" alink=\"$alinkColor\" vlink=\"$vlinkColor\">";
	print $titleLine;
	print "<table border=0 align=\"center\">\n";
	#print "<center><font size=\"$fontSize\" face=\"$fontFace\"><a href=\"$backlink\">Back</a></font></center><br>\n";
	crumnav($wheredir,$tfl);
	print "<tr><td><table align=center border=0>";
	print "<tr><td colspan=3 align=center>";
	$imsz = getimagesize($prefix."/".$cacheDir.$dir."/".$tfl);
	$w = $imsz[0];
	$h = $imsz[1];
	$fontstuff = "<font face=\"$fontFace\">";
	if($fullQLink){
	    print "<a href=\"$wheredir/$tfl\" target=\"_new\">";
	}
	print "<img id=\"imaage\" border=\"0\" src=\"$timg\"";
	if($w > 0) print "width=$w height=$h";
	print ">";
	if($fullQLink){
	    print "</a>";
	}
	print "</td></tr>\n";
	print "<tr><td align=center>$fontstuff";
	print "<a href=\"$plink\" accesskey=\"p\"><b>P</b>rev</a></font>";
	print "</td><td align=center>$fontstuff";
	//print "<A HREF=\"javascript:history.go(-1)\">Back</a>\n";
	print "<a href=\"$backlink\" accesskey=\"b\"><b>B</b>ack</a></font>\n";
	print "</td><td align=center>$fontstuff";
	print " <a href=\"$nlink\" accesskey=\"n\"><b>N</b>ext</a><img width=\"0\" height=\"0\" src=\"$nfile\"/></font>";
	print "</td></tr>";
	$capt = getCaption($tfl,$prefix."/".$wheredir);
	if(strlen($capt) > 0){
	    print "<tr><td colspan=\"3\" align=\"center\" bgcolor=\"$captionBGColor\" id=\"captHolder\" style=\"width:$lowWidth"."px;padding-bottom:10px;padding-top:10px;\">$fontstuff$capt</font></td></tr>\n";
	}
        print "<tr><td colspan=3 align=center>$fontstuff".($idx+1)." / ".($size+1)." </font></td></tr>";
	if($auto == "true"){
		print "<tr><td colspan=3 align=center>$fontstuff<a href=\"$PHP_SELF?mode=single&img=".runc($img)."&auto=false\">Turn Auto Off</a></font></td></tr>";
	} else {
		print "<tr><td colspan=3 align=center>$fontstuff";
		print "Slideshow Delay: ";
		print "<a href=\"$PHP_SELF?mode=single&img=".runc($img)."&auto=true&timer=1\">1</a> ";
		print "<a href=\"$PHP_SELF?mode=single&img=".runc($img)."&auto=true&timer=2\">2</a> ";
		print "<a href=\"$PHP_SELF?mode=single&img=".runc($img)."&auto=true&timer=3\">3</a> ";
		print "<a href=\"$PHP_SELF?mode=single&img=".runc($img)."&auto=true&timer=4\">4</a> ";
		print "<a href=\"$PHP_SELF?mode=single&img=".runc($img)."&auto=true&timer=5\">5</a> ";
		print "</font></td></tr>";
	}
	print "</table></td></tr></table>";
	?>
<script language="javascript">
<!--
function keyhandler(e){
    if(e.keyCode == 37){ //left
	window.location = "<?=$plink?>";
    } else if(e.keyCode == 39 || e.keyCode == 32){ //right
	window.location = "<?=$nlink?>";
    } else if(String.fromCharCode(e.keyCode) == "b" || String.fromCharCode(e.keyCode) == "B"){
	window.location = "<?=$backlink?>";
    } else if(String.fromCharCode(e.keyCode) == "h" || String.fromCharCode(e.keyCode) == "H"){
	window.location = "<?=$PHP_SELF?>";
    } //else {
	//alert(e.keyCode);
    //}
}
document.onkeyup = keyhandler;

var i = document.getElementById('imaage');
var c = document.getElementById('captHolder');

var buffSize = 300;
var hi = screen.availHeight;
if(hi < ( i.height + buffSize )){
    var maxH = hi - buffSize;
    var nw = (maxH * i.width) / i.height;
    i.width = nw;
    i.height = maxH;
    c.style.width = nw;
}
//-->
</script>
	<?
	print $footerLine;
	exit();
}
?>
</title></head>
<body bgcolor="<?echo $bgcolor?>" text="<?echo $textColor?>" link="<?echo $linkColor?>" alink="<?echo $alinkColor?>" vlink="<?echo $vlinkColor?>">
<?echo $titleLine?>

<table border=0 align="center">
<?php
if(strlen($add) > 2){
	crumnav($add,"");
}

if(file_exists($where."/description.txt")){
        $fh = fopen($where."description.txt","r");
        $data = fread($fh,filesize($where."description.txt"));
        fclose($fh);
	print "<tr><td align=center>";
	print preg_replace("/\n/","<br>\n",$data);
	print "</td></tr>";
}


// this section process pictures in the current dir
$flag = 1;

$pics = getPixArray($where);

if(file_exists("$prefix/$cacheDir/$add/tn/index.lst")){
	$upic = file("$prefix/$cacheDir/$add/tn/index.lst");
	$count = 0;
	foreach($upic as $asdf){
		$upic[$count] = substr($asdf,0,-1); // chomp
		$count++;
	}
} else {
	$upic = "";
}
if(file_exists("$prefix/$cacheDir/$add/Low/index.lst")){
	$vpic = file("$prefix/$cacheDir/$add/Low/index.lst");
	$count = 0;
	foreach($vpic as $asdf){
		$vpic[$count] = substr($asdf,0,-1); // chomp
		$count++;
	}
} else {
	$vpic = "";
}

// check for thumbs
ckdir("$prefix/$cacheDir/$add");
if((!file_exists("$prefix/$cacheDir/$add/tn") or ($pics != $upic)) and $pics){
	if(!is_writable("$prefix/$cacheDir")){ print "can't create thumbnails, $prefix/$cacheDir is not writable<br>\n"; exit(0); }
	$flag = 0;
	if(!file_exists("$prefix/$cacheDir/$add/doingthumbs")){
		touch("$prefix/$cacheDir/$add/doingthumbs");
		//make thumbs
		ckdir("$prefix/$cacheDir/$add/tn");
		if($file = fopen("$prefix/$cacheDir/$add/tn/index.lst","w+")){
			foreach($pics as $pic){
				fwrite($file,$pic);
				fwrite($file,"\n");
			}
		} else {
			print "error indexing thumbnails<br>\n";
		}
		fclose($file);
		chmod("$prefix/$cacheDir/$add/tn/index.lst",0777);
		foreach ($pics as $pic){
			@unlink("$prefix/$cacheDir/$add/tn/$pic");
			if($thumbMode == "square"){ 
			    $filegot = sqcvt($pic,$where,"tn",$thumbSize,$thumbSize);
			} else {
			    $size = getimagesize("$where"."$pic");
			    if(($size[0] > $size[1]) or (!$size)){
				$filegot = cvt($pic,$where,"tn",$thumbWidth,$thumbHeight);
			    } else {
				$filegot = cvt($pic,$where,"tn",$thumbHeight,$thumbWidth);
			    }
			}
		}

		unlink("$prefix/$cacheDir/$add/doingthumbs");
		$flag = 1;
	} else {
		print "</table>making thumbnails, please wait<br>\n";
		print "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"5;URL=$PHP_SELF";
		print "?where=$add";
		if ($viewdate) print "&viewdate=yes";
		print "\">\n";
	}
}


// check for lows
if((!file_exists("$prefix/$cacheDir/$add/Low") or ($pics != $vpic)) and $pics){
	if(!is_writable("$prefix/$cacheDir")){ print "can't create thumbnails, $prefix/$cacheDir is not writable<br>\n"; exit(0); }
	$flag = 0;
	if(!file_exists("$prefix/$cacheDir/$add/doinglows")){
		touch("$prefix/$cacheDir/$add/doinglows");
		//make thumbs
		ckdir("$prefix/$cacheDir/$add/Low");
		if($file = fopen("$prefix/$cacheDir/$add/Low/index.lst","w+")){
			foreach($pics as $pic){
				fwrite($file,$pic);
				fwrite($file,"\n");
			}
		} else {
			print "error indexing thumbnails<br>\n";
		}
		fclose($file);
		chmod("$prefix/$cacheDir/$add/Low/index.lst",0777);
		foreach ($pics as $pic){
			@unlink("$prefix/$cacheDir/$add/Low/$pic");
			$size = getimagesize("$where"."$pic");
			if(($size[0] > $size[1]) or (!$size)){
				$filegot = cvt($pic,$where,"Low",$lowWidth,$lowHeight);
			} else {
				$filegot = cvt($pic,$where,"Low",$lowHeight,$lowWidth);
			}
		}

		unlink("$prefix/$cacheDir/$add/doinglows");
		$flag = 1;
	} else {
		print "</table>making Low quality images, please wait<br>\n";
		print "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"5;URL=$PHP_SELF";
		print "?where=$add";
		if ($viewdate) print "&viewdate=yes";
		print "\">\n";
	}
}

$first = "";
// if $flag is 1, print table of images
$fontstuff = "size=\"$fontSize\" face=\"$fontFace\"";
$titlestuff = "size=\"$imageTitleFontSize\" face=\"$fontFace\"";
if($flag == 1 and $pics){
	$count = 0;
	if($thumbMode == "square"){
	    print "<tr><td><table border=\"0\" cellspacing=\"1\" cellpadding=\"2\" align=\"center\" style=\"border:$squareBorderSize"."px solid $squareBorderColor\">\n<tr align=\"center\">";
	} else {
	    print "<tr><td><table border=\"0\" cellspacing=\"5\" cellpadding=\"3\" align=\"center\">\n<tr align=\"center\">";
	}
	foreach ($pics as $pic){
		if($pic == "preview.jpg" or $pic == "pv_thumb.jpg") continue;
		if($count % $dirContentsWidth == 0){
			print "</tr><tr align=center>\n";
		}
		$lpic = preg_replace("/ /","%20",$pic);
		if($first == "") $first = "$prefix/$cacheDir/$add/Low/$lpic";
		if($thumbMode == "square"){
		    print "<td bgcolor=\"$tdBgcolor\"><a href=\"$PHP_SELF?mode=single&img=".runc("$cacheDir/$add/Low/$lpic")."\">";
		    $size = getimagesize("$prefix/$cacheDir/$add/tn/"."$pic");
		    #print "<img style=\"border:1px solid #000000\" src=\"$rwhere/tn/$lpic\" width=\"$thumbSize\" height=\"$thumbSize\"><br>";
		    print "<img border=\"0\" src=\"$cacheDir/$add/tn/$lpic\" width=\"$thumbSize\" height=\"$thumbSize\"><br>";
		    print "</a></td>\n";
		} else {
		    $mxSize=0;
		    if($thumbWidth > $thumbHeight){
			    $mxSize = $thumbWidth;
		    } else {
			    $mxSize = $thumbHeight;
		    }
		    if(stristr($pic,".gif")){
			    $pn = $pic;
			    $pn .= '.jpg';
			    $lpn = preg_replace("/ /","%20",$pn);
			    print "<td bgcolor=\"$tdBgcolor\"><font $titlestuff>$pic</font><br><a href=\"$PHP_SELF?mode=single&img=".runc("$cacheDir/$add/Low/$lpn")."\">";
			    $size = getimagesize("$prefix/$cacheDir/$add/tn/"."$pn");
			    if ($size[0] > $mxSize or $size[1] > $mxSize){
				    if($size[0] > $size[1]){
					    print "<img border=\"0\" src=\"$cacheDir/$add/tn/$lpn\" width=\"$thumbWidth\" height=\"$thumbHeight\"><br>";
				    } else {
					    print "<img border=\"0\" src=\"$cacheDir/$add/tn/$lpn\" width=\"$thumbHeight\" height=\"$thumbWidth\"><br>";
				    }
			    } else {
				    print "<img border=0 src=\"$cacheDir/$add/tn/$lpn\"><br>";
			    }
			    print "</a><a href=\"$PHP_SELF?mode=single&img=".runc("$rwhere/$lpic")."\"><font $fontstuff>High Quality</font></a> <a href=\"$PHP_SELF?mode=single&img=".runc("$cacheDir/$add/Low/$lpn")."\"><font $fontstuff>Low Quality</font></a></td>\n";

		    } else {
			    print "<td bgcolor=\"$tdBgcolor\"><font $titlestuff>$pic</font><br><a href=\"$PHP_SELF?mode=single&img=".runc("$cacheDir/$add/Low/$lpic")."\">";
			    $size = getimagesize("$prefix/$cacheDir/$add/tn/"."$pic");
			    if ($size[0] > $mxSize or $size[1] > $mxSize){
				    if($size[0] > $size[1]){
					    print "<img border=0 src=\"$cacheDir/$add/tn/$lpic\" width=$thumbWidth height=$thumbHeight><br>";
				    } else {
					    print "<img border=0 src=\"$cacheDir/$add/tn/$lpic\" width=$thumbHeight height=$thumbWidth><br>";
				    }
			    } else {
				    print "<img border=0 src=\"$cacheDir/$add/tn/$lpic\"><br>";
			    }
			    print "</a><a href=\"$PHP_SELF?mode=single&img=".runc("$rwhere/$lpic")."\"><font $fontstuff>High Quality</font></a> <a href=\"$PHP_SELF?mode=single&img=".runc("$cacheDir/$add/Low/$lpic")."\"><font $fontstuff>Low Quality</font></a></td>\n";
		    }
		}
		$count++;
	}
	print "</tr></table></td></tr>\n";

	//print "first is $first\n";
	$img = $first;
	$fontstuff="<font face=\"$fontFace\">";
	print "<tr><td><table border=0 align=center>";
	print "<tr><td colspan=3 align=center>$fontstuff";
	print "Slideshow Delay: ";
	print "<a href=\"$PHP_SELF?mode=single&img=".runc($img)."&auto=true&timer=1\">1</a> ";
	print "<a href=\"$PHP_SELF?mode=single&img=".runc($img)."&auto=true&timer=2\">2</a> ";
	print "<a href=\"$PHP_SELF?mode=single&img=".runc($img)."&auto=true&timer=3\">3</a> ";
	print "<a href=\"$PHP_SELF?mode=single&img=".runc($img)."&auto=true&timer=4\">4</a> ";
	print "<a href=\"$PHP_SELF?mode=single&img=".runc($img)."&auto=true&timer=5\">5</a> ";
	print "</font></td></tr>";
	print "</table></td></tr>";
}


?>

<?php
// this section adds additional non-thumbnailable filetypes
// need wpd, txt, avi, rm, mpg, mpeg

$files = array ("");
if($han = opendir("$where")){
	while( false !== ($file = readdir($han))){
		if(checkfiletypes($file)) array_push($files,$file);
	}
}
array_shift($files);

print "<tr><td><table border=0 bgcolor=\"$tdBgcolor\" align=center><tr>\n";
$count = 0;
$vc = 0;
foreach($files as $file){
	if(ereg("url",$file)){
		//url case
		$parts = preg_split("/\./",$file);
		array_pop($parts);
		$name = join(".",$parts);
		$fh = fopen("$where/$file","r");
		$furl = fread($fh,filesize("$where/$file"));
		fclose($fh);
		$ft="<font size=-1 face=\"Verdana\">";
		$sf="</font>";
		print "<td bgcolor=\"$tdBgcolor2\">$ft"."URL:$sf</td><td bgcolor=\"$tdBgcolor2\">$ft<a href=\"$furl\">$name</a>$sf</td></tr><tr>\n";
	} else {
		$stat = stat("$where/$file");
		$bytes = $stat[7];
		if($bytes >= 1024){
			$kbytes = $bytes / 1024;
			if($kbytes >= 1024){
				$mbytes = $kbytes / 1024;
				$size = ((int) $mbytes)."M ".($kbytes % 1024)."k ".($bytes % 1024)."b";
			} else {
				$size = ((int) $kbytes)."K ".($byes % 1024)."b";
			}
		} else {
			$size = $bytes."B";
		}
		$parts = preg_split("/\./",$file);
		array_pop($parts);
		$fnm = join(".",$parts);

		$madd=runc($add);
		#$madd=preg_replace("/%2F/","/",$madd);
		if(file_exists("$where/$fnm.THM")){
			print "<td bgcolor=\"$tdBgcolor2\"><a href=\"$relative/$madd/".runc($file)."\"><img border=\"0\" src=\"$relative/$madd/$fnm.THM\"></a></td>";
		} else { print "<td></td>"; }
		$tfn = "$relative/$madd/".runc($file);
		$tfn = preg_replace("/\/\//","/",$tfn);
		print "<td bgcolor=\"$tdBgcolor2\"><a href=\"$tfn\">$file</a></td><td>$size</td>";
		print "</tr><tr>\n";
	}
}
print "</tr></table></td></tr>\n";

?>

<tr><td>
<?php
$dirs = array ("");
if($han = opendir("$where")){
	while( false !== ($file = readdir($han))){
		if((filetype($where.$file) == "dir") and $file != "Low" and $file != "tn" and $file != "." and $file != ".." and $file != "CVS" and $file != "js" and $file != $cacheDir) array_push($dirs,$file);
	}
}
array_shift($dirs);
usort($dirs, $cmpfunc);

if($dirDisplayMode == "standard"){
?>
<table border=0 align=center bgcolor="<?echo $tdBgcolor?>" cellspacing=3>
<?
	foreach ($dirs as $dir){
		$ldir = runc($dir);
		if ($viewdate){
			$stat = stat("$where/$dir");
			$date = date("l, F dS, Y",$stat[9]);
		}
		if ($viewdate){
			$link = "$PHP_SELF?viewdate=yes&where=$add/$ldir";
			print "<tr><td>";
			genpreview("$where/$dir","$ldir",$add,$link,0);
			print "</td><td bgcolor=\"$tdBgcolor2\"><font face=\"$fontFace\">";
			print "<a href=\"$link\">$dir</a>";
			print "</font></td>";
			print "<td bgcolor=\"$tdBgcolor2\"><font face=\"$fontFace\">$date</font></td></tr>\n";
		} else {
			$link = "$PHP_SELF?where=$add/$ldir";
			print "<tr><td>";
			genpreview("$where/$dir","$ldir",$add,$link,0);
			print "</td><td bgcolor=\"$tdBgcolor2\"><font face=\"$fontFace\">";
			print "<a href=\"$link\">$dir</a>";
			print "</font></td>";
			print "</tr>\n";
		}
	}
} else if($dirDisplayMode == "calendar"){
	if(count($dirs) > 0){
	if($cmpfunc == "jdircmp"){ //ok, can continue
		$unsups = array();
		$lmon="";
		$lyr="";
		$ct=0;
		foreach ($dirs as $dir){
			$ldir=runc($dir);
			if(!preg_match("/..-..-....( \\(.*\\))?/",$dir)){
				array_push($unsups,$dir);
			} else {
				$date = substr($dir, 0, 10);
				$stuff = split("\(",$dir);
	                	$desc = substr($stuff[1],0,-1);
				$sdesc = $desc;
				$mxlen=20;
				if(strlen($desc) > $mxlen){
					$sdesc = substr($desc,0,$mxlen)."...";
				}
				$dpts = split("-",$date);
				$year=$dpts[2];
				$mon=$dpts[0];
				$day=$dpts[1];
				$vmon=date("F",strtotime("$year-$mon-$day"));
				if($lyr!=$year){
					//new year
					$lyr=$year;
				}
				if($lmon!=$mon){
					//new month..
					if($lmon==""){
						//draw begin borders only
						?>
</td></tr></table>
 <table width="625" border="0" class="bordertable" align="center">
  <tr valign="top"> 
   <td><font face="Verdana, Arial, Helvetica, sans-serif" size="-1"><?echo $vmon." ".$year?></font></td>
  </tr>
  <tr valign="top">
   <td>
    <table width="100%" border="0">
     <tr valign="top">
<?
					} else {
						//draw table end/begin borders
		if($ct<4){
			for($i=0;$i<(5-$ct);$i++){
				print "<td width=\"20%\" height=\"89\">&nbsp;</td>\n";
			}
		}
?>
     </tr>
    </table>
   </td>
  </tr>
 </table>
<br>
 <table width="625" border="0" class="bordertable" align="center">
  <tr valign="top">
   <td><font face="Verdana, Arial, Helvetica, sans-serif" size="-1"><?echo $vmon." ".$year?></font></td>
  </tr>
  <tr valign="top">
   <td>
    <table width="100%" border="0">
     <tr valign="top">
<?
					}
					$lmon=$mon;
					$ct=0;
				}
				if($ct>4){
					print "</tr><tr valign=\"top\">\n";
					$ct=0;
				}
                        	$link = "$PHP_SELF?where=$add/$ldir";
            			print "<td width=\"20%\" height=\"89\"><div align=\"center\"><a href=\"$link\" title=\"$desc\">";
				genpreview("$where/$dir","$ldir",$add,$link,1);
				print "</a><br>";
				print "<font class=\"dates\">$mon-$day :<font color=\"#FFCC99\"><a href=\"$link\" title=\"$desc\" style=\"text-decoration: none\"><span class=\"link\"> $sdesc</span></a></font></font>\n";
				print "</div></td>\n";
				$ct++;
			}
		}
		if($ct<5){
			for($i=0;$i<(5-$ct);$i++){
				print "<td width=\"20%\" height=\"89\">&nbsp;</td>\n";
			}
		}
?>
     </tr>
    </table>
   </td>
  </tr>
 </table>
<?
	}
?>
<br>
<table width="15%" border="0" align="center">
<?
	if(count($unsups) > 0){
		//display 'archives'
?>
    <tr> 
      <td><font face="Verdana, Arial, Helvetica, sans-serif"><b>Archives:</b></font></td>
    </tr>
<?
		foreach ($unsups as $dir){
                        $ldir=runc($dir);
			$link = "$PHP_SELF?where=$add/$ldir";
    			print "<tr>\n";
      			print "<td><font face=\"Verdana, Arial, Helvetica, sans-serif\" size=\"-1\"><a href=\"$link\">$dir</a></font></td>\n";
    			print "</tr>\n";
		}

	}
	$detlnk = $PHP_SELF."?ddMode=standard&where=".runc($add);
?>
<?if($viewDetailsLink){?>
    <tr>
      <td align="center"><font face="Verdana, Arial, Helvetica, sans-serif" size="-1"><a href="<?echo $detlnk;?>">view 
        details</a></font></td>
    </tr>
<?}?>
  </table>
<?
}
}
?>
</table>
</td></tr>
<?php
if($dirs && $dirDisplayMode != "calendar"){
	if($viewdate){
		print "<tr><td align=center><a href=\"$PHP_SELF?where=".runc($add)."\"><font face=\"$fontFace\">hide dates</font></a><br></td></tr>\n";
	} else {
		print "<tr><td align=center><a href=\"$PHP_SELF?viewdate=yes&where=".runc($add)."\"><font face=\"$fontFace\">view dates</font></a><br></td></tr>\n";
	}
}
if($changedDDM){
	print "<tr><td align=center><a href=\"$PHP_SELF?where=".runc($add)."\"><font face=\"$fontFace\">hide details</font></a><br></td></tr>\n";
}
?>
</table>
<?echo $footerLine?>
