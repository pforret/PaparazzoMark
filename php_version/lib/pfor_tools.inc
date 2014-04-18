<?php
date_default_timezone_set("UTC");
function contains($haystack,$needle){
	if(!$needle) return false;
	$pos=strpos($haystack,$needle);
	if($pos>0) return true; // in haystack
	if($pos===false) return false; // not in
	if($pos===0) return true; // starts with
	return false; //normally never
}

function trace($input,$type="DEBUG"){
	global $debug;
	
	$time=date("H:m:s");
	switch($type){
	case "DEBUG":
		// only show if in debug mode
		if($debug){
			echo "   $time: ";
			if(is_array($input)){
				echo "\n";
				print_r($input);
			} else {
				print($input);
			}
			echo "\n";
		}
		break;
	case "INFO":
		echo "   $time: ";
		if(is_array($input)){
			echo "\n";
			print_r($input);
		} else {
			print($input);
		}
		echo "\n";
		break;
	case "STAY":
		echo "   $time: ";
		if(is_array($input)){
			//
		} else {
			print($input);
		}
		echo "                     \r";
		break;
	case "WARNING":
		// always show
		echo "*  $time: WARNING: ";
		if(is_array($input)){
			echo "\n";
			print_r($input);
		} else {
			print($input);
		}
		echo "\n";
		break;
	case "ERROR":
		// always show and then stop script
		echo "!! $time: ERROR: ";
		if(is_array($input)){
			echo "\n";
			print_r($input);
		} else {
			print($input);
		}
		echo "\n";
		exit(1);
		break;
	default:
		// wrong status type
		echo "trace: unknown type [$type]\n";
	}
	return true;
}


function listfiles($folder,$ext="",$recursive=false){
	//trace("listfiles: Reading [$ext] files from folder [$folder]");
	if(!$folder){
		trace("listfiles WARNING: start folder cannot be empty");
		return false;
	}
	if(!file_exists($folder)){
		trace("listfiles WARNING: folder [$folder] does not exist");
		return false;
	}
	if(!is_dir($folder)){
		trace("listfiles WARNING: [$folder] is not a folder");
		return false;
	}	
	$selection=Array();
	$cfiles=scandir($folder);
	if($cfiles){
		foreach($cfiles as $cfile){
			$cfull="$folder/$cfile";
			$selected=true;
			if(!is_file($cfull)) $selected=false;
			if($ext AND strpos($cfile,$ext)<1) $selected=false;
			//trace("listfiles: [$cfull] : $selected");
			if($selected)	$selection[]=$cfull;
		}
		trace("listfiles: [$ext] files in [$folder] = " . count($selection));
	} else {
		trace("WARNING: no files found in [$folder]");
	}
	return $selection;
}

function listfolders($folder){
	if(!$folder){
		trace("listfolders WARNING: start folder cannot be empty");
		return false;
	}
	if(!file_exists($folder)){
		trace("listfolders WARNING: folder [$folder] does not exist");
		return false;
	}
	if(!is_dir($folder)){
		trace("listfolders WARNING: [$folder] is not a folder");
		return false;
	}
	trace("listfolders: Reading subfolders from folder [$folder]");
	$selection=Array();
	$cfiles=scandir($folder);
	if($cfiles){
		trace("listfolders: nb items in folder = " . count($cfiles));
		foreach($cfiles as $cfile){
			$cfull="$folder/$cfile";
			$selected=false;
			if(is_dir($cfull)) $selected=true;
			if($cfile == ".")  $selected=false;
			if($cfile == "..")  $selected=false;
			if($selected)	$selection[]=$cfull;
		}
	}
	return $selection;
}

function cmdline($cmd,$folder=false){
	$output=Array();
	if($folder)	$cmd="cd \"$folder\"; $cmd";
	$return=exec($cmd,$output);
	trace("cmdline: [$cmd], returned " . count($output) . " lines");
	return $output;
}

function do_if_necessary($sources,$outputs){
	// purpose: define if $outputs have to be recreated because one of the $sources is more recent
	if(!$outputs)	return true;	// could be e.g. a list of all JPG files in 1 folder, and that list could be empty
	if(!$sources)	return true;	// it's not logical that $sources would be empty, but still

	// make sure $sources is always an array
	if(!is_array($sources))	$sources=Array($sources);
	// make sure $outputs is always an array
	if(!is_array($outputs))	$outputs=Array($outputs);

	$newest_in=0;
	$oldest_out=time();
	foreach($outputs as $output){
		// if an output does not exist, return TRUE right away
		if(!file_exists($output))	return true;
		if(file_exists($output)){
			$mtime=filemtime($output);
			if($mtime < $oldest_out)	
				$oldest_out=$mtime;
		}
	}	
	// all $outputs exist and $oldest_out is the time the oldest was created
	foreach($sources as $source){
		if(file_exists($source)){
			$mtime=filemtime($source);
			if($mtime > $newest_in)	
				$newest_in=$mtime;
		}
	}
	// $newest_in is the time the most recent $source was created

	if($newest_in > $oldest_out)	return true;
	return false;
	
}

function shorten_path($path,$length=60){
	trace("shorten_path: INPUT = $path");
	$clean=realpath($path);
	if($clean) $path=$clean;
	trace("shorten_path: CLEAN = $path");
	if(strlen($path)>$length){
		$plen=strlen($path);
		trace("shorten_path: shorten from $plen to $length");
		// if 
		return substr($path,0,3) . "..." . substr($path,$plen-$length+5);
	} else {
		return $path;
	}
}
?>