<?php
/*
This is version 1.6 of Josh's indexer!
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

// set this to wherever your root dir for pics is (at a filesystem level)
$prefix = "/var/www/htdocs/pics";

//set this to your relative path (what a user would see in the address bar after your server name)
$relative = "/pics";

//this is the name that goes into the title for the site
$site_name = "Your site here";

//this is the path to convert (`which convert`)
$convert = '/usr/bin/convert';
// if that dosen't work, try this line, and change the path  vvv here
#$convert = 'LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/usr/local/lib /usr/local/bin/convert';

//this is the sorting algorithm to use: the choices are:
// jdircmp: sorts directories in the form "MM-DD-YYYY (Description)"
// ndircmp: sorts directories by natural case-insensitive comparison (e.g.: 1 2 3 a B c)
// dircmp: sorts directories by timestamp
$cmpfunc = "jdircmp";

$bgcolor = "#000000";
$textColor = "#FFFFFF";
$linkColor = "#ccaaaa";
$alinkColor = "#eecccc";
$vlinkColor = "#aa8888";
$tdBgcolor = "#222222";
$tdBgcolor2 = "#333333";
$footTextColor = "#888888";

$fontFace = "Verdana";
$fontSize = "-2";
$titleFontSize = "+2";
$imageTitleFontSize = "-1";

$thumbWidth = 120;
$thumbHeight = 90;
$lowWidth = 640;
$lowHeight = 480;
$pv_thumbWidth = 100;
$pv_thumbHeight = 75;

//this is the line that's shown at the top of each page
$titleLine = "<p><center><font size=\"$titleFontSize\" face=\"$fontFace\">Camera Pictures</font></center></p>";

####################################################################
#### CONFIGURATION ENDS HERE #######################################
####################################################################
if(!(bool) ini_get('register_gobals')){
if (!empty($HTTP_GET_VARS)) while(list($inname, $invalue) = each($HTTP_GET_VARS)) $$inname = $invalue;
if (!empty($HTTP_POST_VARS)) while(list($inname, $invalue) = each($HTTP_POST_VARS)) $$inname = $invalue;
if (!empty($HTTP_SERVER_VARS)) while(list($inname, $invalue) = each($HTTP_SERVER_VARS)) $$inname = $invalue;
}
ignore_user_abort(true);

$footerLine="
<center><img src=http://haven.loki.ws/artistic.gif></center><br>
<center><font face=\"$fontFace\" color=\"$footTextColor\">This page best viewed with a recent browser.</font></center><br>
<font size=-2 face=\"$fontFace\" color=\"$footTextColor\"><center>This php thing &copy; <a href=\"mailto:php@loki.ws\">Joshua Szmajda</a> 2002</center></font>
<script language=javascript src=\"http://loki.ws/awstats_misc_tracker.js\"></script>
</body></html>";
// if we didn't get a path, use the default one of just the prefix
if(!$where){
	$rwhere = $relative;
	$where = $prefix;
	$add = '';
} else {
	$where = preg_replace("/\\\\/","",$where);
	if(strstr($where,"..")) $where = "";
	if(!is_dir($prefix.'/'.$where)) $where = "";
	$add = $where;
	$rwhere = $relative . '/' . $where;
	$where = $prefix . '/' . $where;
}
$where .= '/'; //need / for find's weirdness

function checkfiletypes($file){
	$filetypes = array ("wpd", "txt", "avi", "rm", "mpg", "mpeg", "url", "mov", "wmv");

	foreach($filetypes as $type){
		if(stristr($file,".$type") and !(stristr($file,"description.txt"))){
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

function cvt($filename,$dir,$dest,$x,$y) {
	global $convert;
        $cmd = "$convert -resize $x".'x'."$y \"$dir/$filename\" \"$dir/$dest/$filename\"";
	#$STUFF = `echo `;
 
	if(stristr($filename,".jpg") or stristr($filename,".png")){
                `$cmd`;
		chmod("$dir/$dest/$filename",0777);
                return $filename;
        } else if (stristr($filename,".gif")){
                $cmd = "$convert -resize $x".'x'."$y \"$dir/$filename\" \"$dir/$dest/$filename.jpg\"";
                `$cmd`;
                `mv $dir/$dest/$filename.jpg.0 $dir/$dest/$filename.jpg`;
                `rm -f $dir/$dest/$filename.jpg.*`;
                $cmd = "$convert -resize $x".'x'."$y \"$dir/$dest/$filename.jpg\" \"$dir/$dest/$filename.jpg\"";
                `$cmd`;
		chmod("$dir/$dest/$filename.jpg",0777);
                return "$filename.jpg";
}	}

function genpreview($dir,$reldir,$add,$link){
	global $convert,$relative,$pv_thumbWidth,$pv_thumbHeight;
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
		if(!file_exists("$dir/pv_thumb.jpg") or (filectime($source) > filectime("$dir/pv_thumb.jpg"))){
			$cmd = "$convert -resize $pv_thumbWidth"."x"."$pv_thumbHeight \"$source\" \"$dir/pv_thumb.jpg\"";
			`$cmd`;
			$cmd = "chmod a+rwx \"$dir/pv_thumb.jpg\"";
			`$cmd`;
		}
		if(file_exists("$dir/pv_thumb.jpg")){
			print "<a href=\"$link\">";
			if($add != ""){
				print "<img border=0 width=\"$pv_thumbWidth\" height=\"$pv_thumbHeight\" src=\"$relative/$add/$reldir/pv_thumb.jpg\">";
			} else {
				print "<img border=0 width=\"$pv_thumbWidth\" height=\"$pv_thumbHeight\" src=\"$reldir/pv_thumb.jpg\">";
			}
			print "</a>";
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
?>
<html>
<head><title>Pictures at <? echo $site_name ?>
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
	$file = array_pop($parts);
	$origdir = join("/",$parts);
	array_shift($parts);
	if(strlen($relative) > 2){
		array_shift($parts);
	}
	$dir = join("/",$parts);
	$dir = preg_replace("/\\\\/","",$dir);
	$origdir = preg_replace("/\\\\/","",$origdir);

	$wpts = preg_split("/\//",$dir);
	if(ereg("Low",$wpts[count($wpts) - 1])){
		array_pop($wpts);
	}
	$wheredir = join("/",$wpts);

	$tfl = preg_replace("/\\\\/","",$file);
	$timg = preg_replace("/\\\\/","",$img);
	$pics = getPixArray($prefix.$dir);
	$idx = array_search($tfl,$pics);
	$next = $idx+1;
	$prev = $idx-1;
	$size = count($pics) - 1;
	if($next > $size) $next = 0;
	if($prev < 0) $prev = $size;
	$nfile = $pics[$next];
	$pfile = $pics[$prev];
	$nfile = rawurlencode($origdir."/".$nfile);
	$pfile = rawurlencode($origdir."/".$pfile);
	$nlink = "$PHP_SELF?mode=single&img=$nfile";
	$plink = "$PHP_SELF?mode=single&img=$pfile";
	print "</title>";
	$autolink = $nlink."&auto=true&timer=$timer";
	$autolink = preg_replace("/ /","%20",$autolink);
	$backlink = $PHP_SELF."?where=".rawurlencode($wheredir);
	if($auto == "true"){
		print "<meta HTTP-EQUIV=\"Refresh\" Content=\"$timer;URL=$autolink\">";
	}
	print "</head>";
	print "<body bgcolor=\"$bgcolor\" text=\"$textColor\" link=\"$linkColor\" alink=\"$alinkColor\" vlink=\"$vlinkColor\">";
	print $titleLine;
	print "<center><font size=\"$fontSize\" face=\"$fontFace\"><a href=\"$backlink\">Back</a></font></center><br>\n";
	print "<table align=center border=0>";
	print "<tr><td colspan=3 align=center>";
	$imsz = getimagesize($prefix.$dir."/".$tfl);
	$w = $imsz[0];
	$h = $imsz[1];
	$fontstuff = "<font face=\"$fontFace\">";
	print "<img src=\"$timg\"";
	if($w > 0) print "width=$w height=$h";
	print ">\n";
	print "</td></tr>";
	print "<tr><td align=center>$fontstuff";
	print "<a href=\"$plink\">Prev</a> </font>";
	print "</td><td align=center>$fontstuff";
	//print "<A HREF=\"javascript:history.go(-1)\">Back</a>\n";
	print "<a href=\"$backlink\">Back</a></font>\n";
	print "</td><td align=center>$fontstuff";
	print " <a href=\"$nlink\">Next</a> </font>";
	print "</td></tr>";
        print "<tr><td colspan=3 align=center>$fontstuff".($idx+1)." / ".($size+1)." </font></td></tr>";
	if($auto == "true"){
		print "<tr><td colspan=3 align=center>$fontstuff<a href=\"$PHP_SELF?mode=single&img=".rawurlencode($img)."&auto=false\">Turn Auto Off</a></font></td></tr>";
	} else {
		print "<tr><td colspan=3 align=center>$fontstuff";
		print "Auto Slideshow<br>";
		print "Seconds Delay: ";
		print "<a href=\"$PHP_SELF?mode=single&img=".rawurlencode($img)."&auto=true&timer=1\">1</a> ";
		print "<a href=\"$PHP_SELF?mode=single&img=".rawurlencode($img)."&auto=true&timer=2\">2</a> ";
		print "<a href=\"$PHP_SELF?mode=single&img=".rawurlencode($img)."&auto=true&timer=3\">3</a> ";
		print "<a href=\"$PHP_SELF?mode=single&img=".rawurlencode($img)."&auto=true&timer=4\">4</a> ";
		print "<a href=\"$PHP_SELF?mode=single&img=".rawurlencode($img)."&auto=true&timer=5\">5</a> ";
		print "</font></td></tr>";
	}
	print "</table>";
	print $footerLine;
	exit();
}
?>
</title></head>
<body bgcolor="<?echo $bgcolor?>" text="<?echo $textColor?>" link="<?echo $linkColor?>" alink="<?echo $alinkColor?>" vlink="<?echo $vlinkColor?>">
<?echo $titleLine?>

<?php
if(strlen($add) > 2){
$dpts = preg_split("/\//",$add);
array_pop($dpts);
//array_pop($dpts);
$updir = join("/",$dpts);
if(strlen($updir) > 2){
	$uplink = $PHP_SELF."?where=".rawurlencode($updir);
	if($viewdate) $uplink .= "&viewdate=yes";
} else {
	$uplink = $PHP_SELF;
	if($viewdate) $uplink .= "?viewdate=$viewdate";
}
?><center><font size="<?echo $fontSize?>" face="<?echo $fontFace?>"><a href="<?echo $uplink?>">up one directory</a></font></center><?php
}

if(file_exists($where."/description.txt")){
        $fh = fopen($where."description.txt","r");
        $data = fread($fh,filesize($where."description.txt"));
        fclose($fh);
	print "<p align=center>";
	print preg_replace("/\n/","<br>\n",$data);
	print "</p>";
}


// this section process pictures in the current dir
$flag = 1;

$pics = getPixArray($where);

if(file_exists("$where"."tn/index.lst")){
	$upic = file("$where"."tn/index.lst");
	$count = 0;
	foreach($upic as $asdf){
		$upic[$count] = substr($asdf,0,-1); // chomp
		$count++;
	}
} else {
	$upic = "";
}
if(file_exists("$where"."Low/index.lst")){
	$vpic = file("$where"."Low/index.lst");
	$count = 0;
	foreach($vpic as $asdf){
		$vpic[$count] = substr($asdf,0,-1); // chomp
		$count++;
	}
} else {
	$vpic = "";
}

// check for thumbs
if((!file_exists("$where"."tn") or ($pics != $upic)) and $pics){
	$flag = 0;
	if(!file_exists("$where"."doingthumbs")){
		touch("$where"."doingthumbs");
		//make thumbs
		@mkdir("$where"."tn",0777);
		if($file = fopen("$where"."tn/index.lst","w+")){
			foreach($pics as $pic){
				fwrite($file,$pic);
				fwrite($file,"\n");
			}
		} else {
			print "error indexing thumbnails<br>\n";
		}
		fclose($file);
		chmod("$where"."tn/index.lst",0777);
		foreach ($pics as $pic){
			@unlink("$where"."tn/$pic");
			$size = getimagesize("$where"."$pic");
			if(($size[0] > $size[1]) or (!$size)){
				$filegot = cvt($pic,$where,"tn",$thumbWidth,$thumbHeight);
			} else {
				$filegot = cvt($pic,$where,"tn",$thumbHeight,$thumbWidth);
			}
		}

		unlink("$where"."doingthumbs");
		$flag = 1;
	} else {
		print "making thumbnails, please wait<br>\n";
		print "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"5;URL=$PHP_SELF";
		print "?where=$add";
		if ($viewdate) print "&viewdate=yes";
		print "\">\n";
	}
}


// check for lows
if((!file_exists("$where"."Low") or ($pics != $vpic)) and $pics){
	$flag = 0;
	if(!file_exists("$where"."doinglows")){
		touch("$where"."doinglows");
		//make thumbs
		@mkdir("$where"."Low",0777);
		if($file = fopen("$where"."Low/index.lst","w+")){
			foreach($pics as $pic){
				fwrite($file,$pic);
				fwrite($file,"\n");
			}
		} else {
			print "error indexing thumbnails<br>\n";
		}
		fclose($file);
		chmod("$where"."Low/index.lst",0777);
		foreach ($pics as $pic){
			@unlink("$where"."Low/$pic");
			$size = getimagesize("$where"."$pic");
			if(($size[0] > $size[1]) or (!$size)){
				$filegot = cvt($pic,$where,"Low",$lowWidth,$lowHeight);
			} else {
				$filegot = cvt($pic,$where,"Low",$lowHeight,$lowWidth);
			}
		}

		unlink("$where"."doinglows");
		$flag = 1;
	} else {
		print "making Low quality images, please wait<br>\n";
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
	print "<table border=0 cellspacing=5 cellpadding=3 align=center>\n<tr align=center>";
	foreach ($pics as $pic){
		if($pic == "preview.jpg" or $pic == "pv_thumb.jpg") continue;
		if($count % 4 == 0){
			print "</tr><tr align=center>\n";
		}
		$lpic = preg_replace("/ /","%20",$pic);
		if($first == "") $first = "$rwhere/Low/$lpic";
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
			print "<td bgcolor=\"$tdBgcolor\"><font $titlestuff>$pic</font><br><a href=\"$PHP_SELF?mode=single&img=".rawurlencode("$rwhere/Low/$lpn")."\">";
			$size = getimagesize("$where/tn/"."$pn");
			if ($size[0] > $mxSize or $size[1] > $mxSize){
				if($size[0] > $size[1]){
					print "<img border=0 src=\"$rwhere/tn/$lpn\" width=$thumbWidth height=$thumbHeight><br>";
				} else {
					print "<img border=0 src=\"$rwhere/tn/$lpn\" width=$thumbHeight height=$thumbWidth><br>";
				}
			} else {
				print "<img border=0 src=\"$rwhere/tn/$lpn\"><br>";
			}
			print "</a><a href=\"$PHP_SELF?mode=single&img=".rawurlencode("$rwhere/$lpic")."\"><font $fontstuff>High Quality</font></a> <a href=\"$PHP_SELF?mode=single&img=".rawurlencode("$rwhere/Low/$lpn")."\"><font $fontstuff>Low Quality</font></a></td>\n";

		} else {
			print "<td bgcolor=\"$tdBgcolor\"><font $titlestuff>$pic</font><br><a href=\"$PHP_SELF?mode=single&img=".rawurlencode("$rwhere/Low/$lpic")."\">";
			$size = getimagesize("$where/tn/"."$pic");
			if ($size[0] > $mxSize or $size[1] > $mxSize){
				if($size[0] > $size[1]){
					print "<img border=0 src=\"$rwhere/tn/$lpic\" width=$thumbWidth height=$thumbHeight><br>";
				} else {
					print "<img border=0 src=\"$rwhere/tn/$lpic\" width=$thumbHeight height=$thumbWidth><br>";
				}
			} else {
				print "<img border=0 src=\"$rwhere/tn/$lpic\"><br>";
			}
			print "</a><a href=\"$PHP_SELF?mode=single&img=".rawurlencode("$rwhere/$lpic")."\"><font $fontstuff>High Quality</font></a> <a href=\"$PHP_SELF?mode=single&img=".rawurlencode("$rwhere/Low/$lpic")."\"><font $fontstuff>Low Quality</font></a></td>\n";
		}
		$count++;
	}
	print "</tr></table>\n";

	//print "first is $first\n";
	$img = $first;
	$fontstuff="<font face=\"$fontFace\">";
	print "<table border=0 align=center>";
	print "<tr><td colspan=3 align=center>$fontstuff";
	print "Auto Slideshow<br>";
	print "Seconds Delay: ";
	print "<a href=\"$PHP_SELF?mode=single&img=".rawurlencode($img)."&auto=true&timer=1\">1</a> ";
	print "<a href=\"$PHP_SELF?mode=single&img=".rawurlencode($img)."&auto=true&timer=2\">2</a> ";
	print "<a href=\"$PHP_SELF?mode=single&img=".rawurlencode($img)."&auto=true&timer=3\">3</a> ";
	print "<a href=\"$PHP_SELF?mode=single&img=".rawurlencode($img)."&auto=true&timer=4\">4</a> ";
	print "<a href=\"$PHP_SELF?mode=single&img=".rawurlencode($img)."&auto=true&timer=5\">5</a> ";
	print "</font></td></tr>";
	print "</table>";
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

print "<table border=0 bgcolor=\"$tdBgcolor\" align=center><tr>\n";
$count = 0;
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
		if(file_exists("$where/$fnm.THM")){
			#print "<td bgcolor=\"$tdBgcolor2\"><img src=\"$relative/$add/$fnm.THM\" width=\"$thumbWidth\" height=\"$thumbHeight\"></td>";
			print "<td bgcolor=\"$tdBgcolor2\"><a href=\"".rawurlencode("$relative/$add/$file")."\"><img border=\"0\" src=\"$relative/$add/$fnm.THM\"></a></td>";
		} else { print "<td></td>"; }
		print "<td bgcolor=\"$tdBgcolor2\"><a href=\"".rawurlencode("$relative/$add/$file")."\">$file</a></td><td>$size</td>";
		print "</tr><tr>\n";
	}
}
print "</tr></table><br>\n";

?>

<table border=0 align=center bgcolor="<?echo $tdBgcolor?>" cellspacing=3>
<?php

$dirs = array ("");
if($han = opendir("$where")){
	while( false !== ($file = readdir($han))){
		if((filetype($where.$file) == "dir") and $file != "Low" and $file != "tn" and $file != "." and $file != ".." and $file != "CVS") array_push($dirs,$file);
	}
}
array_shift($dirs);

usort($dirs, $cmpfunc);

foreach ($dirs as $dir){
	$ldir = rawurlencode($dir);
	if ($viewdate){
		$stat = stat("$where/$dir");
		$date = date("l, F dS, Y",$stat[9]);
	}
	if ($viewdate){
		$link = "$PHP_SELF?viewdate=yes&where=$add/$ldir";
		print "<tr><td>";
		genpreview("$where/$dir","$ldir",$add,$link);
		print "</td><td bgcolor=\"$tdBgcolor2\"><font face=\"$fontFace\">";
		print "<a href=\"$link\">$dir</a>";
		print "</font></td>";
		print "<td bgcolor=\"$tdBgcolor2\"><font face=\"$fontFace\">$date</font></td></tr>\n";
	} else {
		$link = "$PHP_SELF?where=$add/$ldir";
		print "<tr><td>";
		genpreview("$where/$dir","$ldir",$add,$link);
		print "</td><td bgcolor=\"$tdBgcolor2\"><font face=\"$fontFace\">";
		print "<a href=\"$link\">$dir</a>";
		print "</font></td>";
		print "</tr>\n";
	}
}
?>
</table>
<?php
if($dirs){
	if($viewdate){
		print "<center><a href=\"$PHP_SELF?where=".rawurlencode($add)."\"><font face=\"$fontFace\">hide dates</font></a><br></center>\n";
	} else {
		print "<center><a href=\"$PHP_SELF?viewdate=yes&where=".rawurlencode($add)."\"><font face=\"$fontFace\">view dates</font></a><br></center>\n";
	}
}
?>
<?echo $footerLine?>
