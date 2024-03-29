<?php
include_once("pfor_tools.inc");

Class PrepMagick{
	var $Gravity="center";
	var $FontFam="Gabriola";
	var $FontSize="50";
	var $FontFill="#FFFF";
	var $Undercolor="#0000";
	var $TmpFiles=Array();
	var $Magick="magick.exe";
	var $Identify="identify.exe";
	var $TmpDir=".temp";

	function __construct(){
		if(!file_exists("$this->TmpDir/.")){
			trace("PrepMagick: create temp folder [$this->TmpDir]");
			mkdir($this->TmpDir);
			sleep(1);
			if(!file_exists("$this->TmpDir/.")){
				trace("PrepMagick: temp folder could not be created]","error");
			}
		} else {
			trace("PrepMagick: using temp folder [$this->TmpDir]");
		}
	}

	function overlay_text($aparams){
	/*
	(
	    [background] => #0003
	    [gravity] => East
	    [padding] => 20
	    [text] =>  blablabla
	    [text_color] => #FFFF
	    [text_effect] => outline
	    [text_font] => Gabriola
	    [text_size] => 30
	)
	*/
		$text=$this->getvalue($aparams,"text","");
		$font=$this->getvalue($aparams,"text_font","Courier");
		$size=$this->getvalue($aparams,"text_size",50);
		$color=$this->getvalue($aparams,"text_color","#FFF");
		$style=$this->getvalue($aparams,"text_effect","");
		$padding=$this->getvalue($aparams,"padding",0 );
		$grav=$this->getvalue($aparams,"gravity","Center" );
		$back=$this->getvalue($aparams,"background","#0000" );
		$ucol=$this->getvalue($aparams,"undercolor",false );
		$alpha=$this->getvalue($aparams,"alpha",100);
		$rotation=$this->getvalue($aparams,"rotation",0);

		$encoded=html_entity_decode($text,null, 'ISO-8859-1');

		// let's make a temp image
		$begin=preg_replace("#[^a-zA-Z0-9]*#","",$encoded);
		$begin=substr($begin,0,8);
		$tmp_txt="$this->TmpDir/_$begin.$size." . substr(md5(serialize($aparams)),0,8) . ".png";
		$this->TmpFiles[]=$tmp_txt;

		$format="";
		$line="-size 2000x2000 canvas:#0000 -channel RGBA -font \"$font\" -pointsize $size -gravity $grav ";
		$style=strtolower($style);
		$angle=0;
		if(contains($style,"italic")){
			$angle=10;
		}
		if($rotation){
			//$line.="-rotate $rotation ";
			$angle+=$rotation;
		}
		$shadow=1;
		if($size > 60){
			$shadow=round($size/30);
			trace("Using shadow distance of $shadow");
		}
		switch(true){
			case contains($style,"shadow"):
				// add a shadow on the right and bottom
				$scolor=$this->CalcShowColor($color);
				$line.="-fill \"$scolor\" ";
				$line.="-annotate ${rotation}x${angle}+0+$shadow \"$encoded\" ";
				$line.="-annotate ${rotation}x${angle}+$shadow+$shadow \"$encoded\" ";
				$line.="-annotate ${rotation}x${angle}+$shadow+0 \"$encoded\" ";
				$line.="-blur 0x1 ";
				$line.="-fill \"$color\" ";
				if($ucol)	$line.="-undercolor $ucol ";
				$line.="-annotate ${rotation}x${angle}+0+0 \"$encoded\" ";
				break;
			case contains($style,"outline"):
				// like a shadow all around
				$scolor=$this->CalcShowColor($color);
				$line.="-fill \"$scolor\" ";
				$line.="-annotate ${rotation}x${angle}+$shadow+$shadow \"$encoded\" ";
				$line.="-annotate ${rotation}x${angle}-$shadow+$shadow \"$encoded\" ";
				$line.="-annotate ${rotation}x${angle}+$shadow-$shadow \"$encoded\" ";
				$line.="-annotate ${rotation}x${angle}-$shadow-$shadow \"$encoded\" ";
				//$line.="-blur 0x1 ";
				$line.="-fill \"$color\" ";
				if($ucol)	$line.="-undercolor \"$ucol\" ";
				$line.="-annotate ${rotation}x${angle}+0+0 \"$encoded\" ";
				break;
			default:
				$line.="-fill \"$color\" ";
				if($ucol)	$line.="-undercolor \"$ucol\" ";
				$line.="-annotate ${rotation}x${angle}+0+0 \"$encoded\" ";
		};
		if($rotation){
			//$line.="-rotate $rotation ";
		}
		// remove excess borders, and add transparent padding border
		if($alpha < 100)	$line.=" -alpha on -channel a -evaluate multiply " . round($alpha/100,2) . " "; 
		$line.="-trim +repage -bordercolor \"$back\" -border $padding -quality 99 ";
		$this->RunMagick(" $line \"$tmp_txt\"");
		if(!file_exists($tmp_txt)){
			trace("Cannot create TXT image for [$html]","ERROR");
		}
		trace("MARK TXT: [$begin]","INFO");
		return("-gravity $grav \"$tmp_txt\" -composite");
	}

	function overlay_image($aparams){
		$file=$aparams["image"];
		if(!file_exists($file)){
			trace("Image [$file] can not be found","WARNING");
			return false;
		};
		$file=realpath($file);
		$bname=basename($file);

		$padding=$this->getvalue($aparams,"padding",0);
		$back=   $this->getvalue($aparams,"background","#0000");
		$resize= $this->getvalue($aparams,"resize",false);
		$grav=   $this->getvalue($aparams,"gravity","Center");
		$alpha=  $this->getvalue($aparams,"alpha","100");
		$format="-gravity $grav ";
		// create unique temp file
		$begin=preg_replace("#[^a-zA-Z0-9]*#","",basename($file));
		$begin=substr($begin,0,8);
		$tmp_img="$this->TmpDir/_img.$begin." . substr(md5(serialize($aparams)),0,8) . ".png";
		$this->TmpFiles[]=$tmp_img;
		$line="";
		if($resize)		$line.="-resize $resize ";
		if($alpha < 100)	$line.=" -alpha on -channel a -evaluate multiply " . round($alpha/100,2) . " "; 
		if($padding)	$line.="-bordercolor \"$back\" -border $padding ";
		$line.="-quality 99 ";
		$this->RunMagick("\"$file\" $line \"$tmp_img\"");
		if(!file_exists($tmp_img)){
			trace("Cannot create image for [$file]","ERROR");
		}
		trace("MARK IMG: [$bname]","INFO");
		return("-gravity $grav \"$tmp_img\" -composite");
	}

	function set_tags($aparams){
		/*
		contact_name="Tango Paparazzo"
		contact_email="tangopaparazzo@gmail.com"
		contact_url="http://tangopaparazzo.com"

		2#05#Object Name="::Object Name"
		2#5#Image Name="GSPCA kittens"
		2#25#Keyword="Kittens"
		2#25#Keyword="GSPCA"
		2#55#Date Created="::Date Created"
		2#80#By-line="::By-line"
		2#110#Credit="::Credit"
		2#115#Source="::Source"
		2#116#Copyright Notice="::Copyright Notice"
		2#118#Contact="::Contact"
		2#120#Caption="::Caption"

		What is automatically used in Facebook:
		::Object Name
		::Caption ::Copyright Notice

		What is automatically used in Flickr:
		::Object Name
		::Caption ::Copyright Notice
		*/
		$tmp_txt="$this->TmpDir/_iptc." . substr(md5(serialize($aparams)),0,8) . ".txt";
		$iptc_tags=Array();
		if($aparams["contact_name"]){
			$iptc_tags[]=$this->format_iptc("2#110#Credit",$aparams["contact_name"]);
		}
		if($aparams["contact_email"]){	$iptc_tags[]=$this->format_iptc("2#118#Contact",$aparams["contact_email"]);		}
		if($aparams["event"]){
			$iptc_tags[]=$this->format_iptc("2#05#Object Name",$aparams["event"]);	
		}
		if($aparams["tags"]){
			$tags=explode(" ",$aparams["tags"]);
			foreach($tags as $tag){
				$iptc_tags[]=$this->format_iptc("2#25#Keyword",$tag);		
			}
		}
		if($aparams["caption"]){
			$iptc_tags[]=$this->format_iptc("2#120#Caption",$aparams["caption"]);	
		}
		if($aparams["contact_url"]){	
			$iptc_tags[]=$this->format_iptc("2#80#By-line",$aparams["contact_url"]);	
			$iptc_tags[]=$this->format_iptc("2#116#Copyright Notice",$aparams["contact_url"]);
			//$iptc_tags[]=$this->format_iptc("2#120#Caption",$aparams["contact_url"]);	
		}
		if($iptc_tags){
			sort($iptc_tags);
			$iptc_text=implode("\r\n",$iptc_tags);
			file_put_contents($tmp_txt,$iptc_text);
			$this->TmpFiles[]=$tmp_txt;
			trace("SET TAGS: [" . $aparams["contact_name"] ."]","INFO");
			return("-strip -profile 8BIMTEXT:$tmp_txt");
		}

	}

	function format_iptc($key,$val){
		return "$key=\"$val\"";
	}

	function getvalue($avalues,$key,$default){
		if(isset($avalues[$key])){
			return  $avalues[$key];
		} else {
			return $default;
		}
	}


	function RunMagick($line){
		$cmd="\"$this->Magick\" $line 2>&1";
		$result=cmdline($cmd);
		if($result){
			trace($result);
		}
		return $result;
	}
	
	function RunIdentify($line){
		$cmd="\"$this->Identify\" $line 2>&1";
		return cmdline($cmd);
	}

	function Cleanup(){
		foreach($this->TmpFiles as $tmpfile){
			unlink($tmpfile);
		}
	}
	
	//---------------
	function GetTextFormat(){
		return("-font \"$this->FontFam\" -gravity $this->Gravity -fill $this->FontFill -undercolor \"$this->Undercolor\" -pointsize $this->FontSize");
	}

	function CalcShowColor($original){
		switch($original){
			case "#FFF":
			case "#FFFF":
				return "#0008";
				break;
			case "#000":
			case "#000F":
				return "#FFF8";
				break;
			default:
				return "#0008";
		}
	}

}



?>