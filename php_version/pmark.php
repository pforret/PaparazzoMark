<?php
include("lib/pfor_inifile.inc");
include("lib/pfor_magick.inc");
// currently at version 0.1 - alpha i.e. not working yet
$debug=false;


$inipaths=Array(
	getcwd()."/pmark.ini", // this folder
	dirname(getcwd())."/pmark.ini",	// parent folder
	__DIR__."/pmark.ini", // script folder
	);

$ifile=false;
$ini=false;

trace("SOURCE  : [" . shorten_path(getcwd()) . "]","INFO");
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
	trace("INI FILE: [" . shorten_path($ifile) . "]","INFO");
}

$bdebug=$ini->get_value("_export","debug",0);


$pm=New PrepMagick;
$AddMagick=Array();

$magick=$ini->get_value("_export","prog_im_convert","convert.exe");
$identify=$ini->get_value("_export","prog_im_identify","identify.exe");

// do resize of source picture
$height=$ini->get_value("_export","export_height",800);
$width=(int)$height * 1.5;
$AddMagick[]="-resize ${width}x${height}";

// add border if necessary
$b_size=$ini->get_value("_export","border_size",0);
if($b_size){
	$b_color=$ini->get_value("_export","border_color","#000F");
	$AddMagick[]="-bordercolor $b_color -border $b_size";
}

$output_dir=$ini->get_value("_export","export_folder",realpath(getcwd()."/marked/"));
$output_dir=resolve_dir($output_dir);
trace("OUTPUT  : [" . shorten_path($output_dir) . "]","INFO");

if(!file_exists($output_dir)){
	mkdir($output_dir,0777,true);
	if(!file_exists($output_dir)){
		trace("Cannot create output folder [$output_dir]","ERROR");
	} else {
		trace("Output folder [$output_dir] (created)");
	}
} else {
		trace("Output folder [$output_dir] (exists)");
}


////---------------------------------------------------------
//// PROCESS ALL THE TEXT/IMAGE OPERATIONS
////---------------------------------------------------------
$sections=$ini->get_sections();
if($sections) foreach($sections as $section){
	$text=$ini->get_value($section,"text");
	$image=$ini->get_value($section,"image");
	if($text){
		$pm->FontFam	=$ini->get_value($section,"text_font","Georgia");
		$pm->FontSize 	=$ini->get_value($section,"text_size",4*round($height/100));
		$pm->FontFill	=$ini->get_value($section,"text_color","#FFFF");
		$pm->Gravity 	=$ini->get_value($section,"gravity","Center");
		$pm->Undercolor =$ini->get_value($section,"undercolor","#0000");
		$position		=$ini->get_value($section,"position","0");

		$AddMagick[]=$pm->CmdAnnotate($text,$position);
	}
	if($image){
		$image 		=$ini->get_value($section,"image");
		$resize 	=$ini->get_value($section,"resize");
		$pm->Gravity 	=$ini->get_value($section,"gravity","Center");
		
		$AddMagick[]=$pm->CmdImprint($image,$resize);

	}
}

// get export quality - default 95%
$quality=$ini->get_value("_export","export_quality",95);
$outfmt=$ini->get_value("_export","export_format","jpg");
$wildcard=$ini->get_value("_export","source_format","jpg");
$outpre=$ini->get_value("_export","export_prefix","PM");
$overwrite=$ini->get_value("_export","overwrite",false);
$AddMagick[]="-quality $quality";

////---------------------------------------------------------
//// FIND ALL THE IMAGE FILES
////---------------------------------------------------------

if(contains($wildcard,",")){
	// several sets of *.$wildcard files
	$types=explode(",",$wildcard);
	$imgfiles=Array();
	foreach($types as $type){
		$collection=glob("*.$type");
		if($collection){
			foreach($collection as $onepic){
				$imgfiles[$onepic]=basename($onepic,".$type");
			}
		}
	}
} else {
	// just 1 set of *.$wildcard files
	$collection=glob("*.$wildcard");
	foreach($collection as $onepic){
		$imgfiles[$onepic]=basename($onepic,".$wildcard");
	}
}
if(!$imgfiles){
	trace("No image files found - nothing to export","ERROR");
}

////---------------------------------------------------------
//// PROCESS ALL THE IMAGE FILES
////---------------------------------------------------------

ksort($imgfiles);
//print_r($imgfiles);
$totimgs=count($imgfiles);
trace("IMAGES: $totimgs found");
$i=0;
foreach($imgfiles as $imgfile => $imgname){
	$i++;
	$cmd_magick=implode(" ",$AddMagick);
	$outfile="$imgname.$outfmt";
	$outpath="$output_dir/$outfile";
	if(!file_exists($outpath) OR $overwrite OR filemtime($imgfile) > filemtime($outpath)){
		trace("PMARK: [" . shorten_path($imgfile,20) . "] -> [" . shorten_path($outpath,30) . "] (" . round($i*100/$totimgs) . "%)","INFO");
		$cmd="\"$magick\" \"$imgfile\" $cmd_magick \"$outpath\" 2>&1";
		cmdline($cmd);
	}
}

////---------------------------------------------------------
//// SHOW OUTPUT FOLDER
////---------------------------------------------------------

cmdline("explorer \"" . realpath($output_dir) . "\"");


// ---------------- SUPPORTING FUNCTIONS
function resolve_dir($folder,$file=false){
	global $identify;
	// ;	$basename 	= "TestFolder123"
	// ;	$yearnow	= "2013"
	// ;	$yearimg	= "2010"
	// ;	$datenow	= "2013-06-14"
	// ;	$dateimg	= "2010-10-01"
	$replace['$basename']=basename(getcwd());
	$replace['$nowyear']=date("Y");
	$replace['$nowmonth']=date("Y-m");
	$replace['$nowdate']=date("Y-m-d");
	/*
	date:create=2013-06-29T19:35:26+02:00
	date:modify=2013-06-12T01:13:39+02:00
	*/
	if(contains($folder,'$img')){
		if(!$file){
			$imgfiles=glob("*");
			$file=$imgfiles[0];
		}
		$lines=cmdline("\"$identify\" -format \"%[date:*]\" \"$file\"");
		$date_modif="";
		foreach($lines as $line){
			if(contains($line,"date:modify")){
				list($key,$date_modif)=explode("=",$line,2);
			}
		}
		if(!$date_modif){
			$time_modif=time();
		} else {
			$time_modif=strtotime($date_modif);
		}
		$replace['$imgyear']=date("Y",$time_modif);
		$replace['$imgmonth']=date("Y-m",$time_modif);
		$replace['$imgdate']=date("Y-m-d",$time_modif);
	}
	//print_r($replace);
	$return=str_replace(array_keys($replace), array_values($replace), $folder);
	trace("resolve_dir: [$folder] -> [$return]");
	return $return;
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