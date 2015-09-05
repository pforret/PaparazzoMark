<?php
include("lib/pfor_inifile.inc");
include("lib/pfor_magick.inc");
// currently at version 0.1 - alpha i.e. not working yet
$debug=false;

trace("PROGRAM : PMARK - PaparazzoMark v1.2","INFO");

$inipaths=Array(
	getcwd(), // this folder
	dirname(getcwd()),	// parent folder
	__DIR__, // script folder
	);

$ini=New IniFile($ifile);
$ifile=$ini->find_file($inipaths,"pmark.ini");
if(!ifile){
	trace("Cannot find INI file","ERROR");
}
$ini->read_file($ifile);
$debug=$ini->get_value("_export","debug",0);


$pm=New PrepMagick;
$AddMagick=Array();

$magick=$ini->get_value("_export","prog_im_convert","convert.exe");
$pm->Magick=$magick;
$identify=$ini->get_value("_export","prog_im_identify","identify.exe");
$pm->Identify=$identify;

// do resize of source picture
$height=$ini->get_value("_export","export_height",800);
$width=(int)$height * 1.5;
$width=$ini->get_value("_export","export_width",$width);
$AddMagick[]="-resize ${width}x${height}";
trace("RESIZE  : $width x $height","INFO");

$output_def=realpath(getcwd()."/_pmark/");
trace("DEF OUT : [" . shorten_path($output_def) . "]");

$output_dir=$ini->get_value("_export","export_folder",$output_def);
$output_dir=resolve_dir($output_dir);

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
trace("OUTPUT  : [" . shorten_path($output_dir) . "]","INFO");


////---------------------------------------------------------
//// PROCESS ALL THE TEXT/IMAGE OPERATIONS
////---------------------------------------------------------
$sections=$ini->get_sections();
if($sections) foreach($sections as $section){
	$text=$ini->get_value($section,"text");
	$image=$ini->get_value($section,"image");
	$border=$ini->get_value($section,"border_size");
	if($text){
		$AddMagick[]=$pm->overlay_text($ini->get_all_values($section));
	}
	if($border){
		$b_color=$ini->get_value($section,"border_color","#000F");
		$AddMagick[]="-bordercolor \"$b_color\" -border $border";
	}
	if($image){
		$AddMagick[]=$pm->overlay_image($ini->get_all_values($section));
	}
}

$outfmt=$ini->get_value("_export","export_format","jpg");
$wildcard=$ini->get_value("_export","source_format","jpg");
$outpre=$ini->get_value("_export","export_prefix","PM");
$overwrite=$ini->get_value("_export","overwrite",false);

if($ini->get_value("_export","contact_name")){
	$AddMagick[]=$pm->set_tags($ini->get_all_values("_export"));

}


// get export quality - default 95%
$quality=$ini->get_value("_export","export_quality",95);
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
trace("IMAGES  : $totimgs to process","INFO");
$i=0;
$new=0;
$b_processed=0;
$t_start=microtime(true);
foreach($imgfiles as $imgfile => $imgname){
	$i++;
	$bname=basename($imgfile);
	$cmd_magick=implode(" ",$AddMagick);
	$outfile="$imgname.$outfmt";
	$outpath="$output_dir/$outfile";
	if(!file_exists($outpath) OR $overwrite OR filemtime($imgfile) > filemtime($outpath)){
		if($new > 3){
			$elapsed=microtime(true)-$t_start;
			$pps=round($new/$elapsed,1);
			$mbps=round($b_processed/(1000000*$elapsed),1);
			trace("PMARK IT: [$bname] (" . round($i*100/$totimgs) . "% - $pps pics/sec - $mbps MB/s)","STAY");	
		} else {
			trace("PMARK IT: [$bname] (" . round($i*100/$totimgs) . "%)","STAY");	
		}
		$pm->RunMagick("\"$imgfile\" $cmd_magick \"$outpath\"");
		$new++;
		$b_processed+=filesize($imgfile);
	}
}
$elapsed=microtime(true)-$t_start;
$pps=round($new/$elapsed,1);
$mbps=round($b_processed/(1000000*$elapsed),1);
echo "\n";
trace("FINISHED: $new pics converted at $pps pics/sec","INFO");
if(!$debug) $pm->Cleanup();
sleep(2);
////---------------------------------------------------------
//// SHOW OUTPUT FOLDER
////---------------------------------------------------------

cmdline("explorer \"" . realpath($output_dir) . "\""); // for win
cmdline("open \"" . realpath($output_dir) . "\""); // for mac
if($debug){
	trace("FINISHED: waiting 5 seconds");
	sleep(5);
}


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
		$lines=cmdline("\"$identify\" -format \"%[exif:*]\" \"$file\"");
		trace("EXIF DATA:" . implode("\n",$lines));
		$date_modif="";
		foreach($lines as $line){
			if(contains($line,"exif:DateTimeOriginal")){
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