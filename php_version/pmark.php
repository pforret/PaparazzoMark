<?php
include("lib/tools.inc");
include("lib/inifile.inc");
// currently at version 0.1 - alpha i.e. not working yet
$debug=true;


$inipaths=Array(
	getcwd()."/pmark.ini",
	dirname(getcwd())."/pmark.ini",
	__DIR__."/pmark.ini",
	);

$ifile=false;
$ini=false;

foreach($inipaths as $inipath){
	if(!$ifile){
		if(file_exists($inipath)){
			$ifile=realpath($inipath);
			$ini=New IniFile($ifile);
		}
	}
}
if(!$ifile){
	trace("No INI instructions found","ERROR");
	//print_r($ini);
} else {
	trace("Using INI file: [$ifile]");
}

$sections=$ini->get_sections();
$height=$ini->get_value($section,"export_height");
$width=(int)$height * 2;
foreach($sections as $section){
	$text=$ini->get_value($section,"text");
	$image=$ini->get_value($section,"image");
	if($text){

	}
	if($image){

	}
}
/*
	set  BACK=-gravity West -fill #000F -pointsize %SIZE2% -annotate 0x0+%HALFB%+3 @%COPYRIGHT% -gravity East -pointsize %SIZE1% -annotate 0x0+%HALFB%+3 @%TXT_SUBS% -channel RGBA -blur 0x1
	set FRONT=-gravity West -fill #FFFF -pointsize %SIZE2% -annotate 0x0+%HALFB%+3 @%COPYRIGHT% -gravity East -pointsize %SIZE1% -annotate 0x0+%HALFB%+3 @%TXT_SUBS%

	magick -size 3000x80 canvas:#0000 -font "Gabriola" %BACK% %BACK% %FRONT% -quality 99 %WATERMARK%

	magick %1 -resize 1500x1000 
		-gravity SouthWest %LOGO% -composite 
		-gravity SouthEast %WATERMARK% -composite 
		-quality 95 %OUTPUT%

*/
?>