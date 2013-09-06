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

	function CmdAnnotate($html,$style=false,$rotation=0,$padding=5){
	/*
	let's try to imitate this: make text into image and overlay that
	set BLUR=-channel RGBA -blur 0x3
	set  BACK=-gravity West -fill #000F -pointsize %SIZE2% -annotate 0x0+%HALFB%+3 %COPYRIGHT% -gravity East -pointsize %SIZE1% -annotate 0x0+%HALFB%+3 %TXT_SUBS% -channel RGBA -blur 0x2
	set BACK2=-gravity West -fill #000F -pointsize %SIZE2% -annotate 0x0+%HALFB%+2 %COPYRIGHT% -gravity East -pointsize %SIZE1% -annotate 0x0+%HALFB%+2 %TXT_SUBS% -channel RGBA -blur 0x2
	set FRONT=-gravity West -fill #FFFF -pointsize %SIZE2% -annotate 0x0+%HALFB%+3 %COPYRIGHT% -gravity East -pointsize %SIZE1% -annotate 0x0+%HALFB%+3 %TXT_SUBS%

	convert -size %DOUBLE%x%HEIGHT% canvas:#0000 %FONTSTYLE% %BACK% %BACK2% %FRONT% -quality 96 %WATERMARK%
	*/
		$encoded=html_entity_decode($html,null, 'ISO-8859-1');

		// let's make a temp image
		$tmp_txt="./_" . md5("$html"). ".png";
		$this->TmpFiles[]=$tmp_txt;

		$format="-font \"$this->FontFam\" -pointsize $this->FontSize";
		$line="-size 2000x2000 canvas:#0000 -channel RGBA $format -gravity Center ";
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
		if($this->FontSize > 60){
			$shadow=(int)($this->FontSize/30);
		}
		switch(true){
			case contains($style,"shadow"):
				$scolor=$this->CalcShowColor($this->FontFill);
				$line.="-fill $scolor";
				$line.="-annotate ${rotation}x${angle}+0+$shadow       \"$encoded\" ";
				$line.="-annotate ${rotation}x${angle}+$shadow+$shadow \"$encoded\" ";
				$line.="-annotate ${rotation}x${angle}+$shadow+0       \"$encoded\" ";
				$line.="-blur 0x1 ";
				$line.="-fill $this->FontFill ";
				$line.="-annotate ${rotation}x${angle}+0+0             \"$encoded\" ";
				break;
			case contains($style,"outline"):
				$scolor=$this->CalcShowColor($this->FontFill);
				$line.="-fill $scolor";
				$line.="-annotate ${rotation}x${angle}+$shadow+$shadow \"$encoded\" ";
				$line.="-annotate ${rotation}x${angle}-$shadow+$shadow \"$encoded\" ";
				$line.="-annotate ${rotation}x${angle}+$shadow-$shadow \"$encoded\" ";
				$line.="-annotate ${rotation}x${angle}-$shadow-$shadow \"$encoded\" ";
				//$line.="-blur 0x1 ";
				$line.="-fill $this->FontFill ";
				$line.="-annotate ${rotation}x${angle}+0+0             \"$encoded\" ";
				break;
			default:
				$line.="-fill $this->FontFill ";
				$line.="-annotate ${rotation}x${angle}+0+0             \"$encoded\" ";
		};
		if($rotation){
			//$line.="-rotate $rotation ";
		}
		// remove excess borders, and add transparent padding border
		$line.="-trim +repage -bordercolor #0000 -border $padding -quality 99 ";
		$this->RunMagick(" $line \"$tmp_txt\"");
		if(!file_exists($tmp_txt)){
			trace("Cannot create TXT image for [$html]","ERROR");
		}
		trace("TEXT    : [$html]","INFO");
		return("-gravity $this->Gravity \"$tmp_txt\" -composite");
	}

	function CmdImprint($file,$resize=false,$padding=5){
		$file=realpath($file);
		if(!file_exists($file)){
			trace("Image [$file] can not be found","WARNING");
			return false;
		};
		$format="-gravity $this->Gravity ";
		// create unique temp file
		$tmp_img="./_" . md5("$file"). ".png";
		$this->TmpFiles[]=$tmp_img;
		$line="";
		if($resize)	$line.="-resize $resize ";
		if($padding)	$line.="-bordercolor #0000 -border $padding ";
		$line.="-quality 99 ";
		$this->RunMagick("\"$file\" $line \"$tmp_img\"");
		if(!file_exists($tmp_img)){
			trace("Cannot create image for [$file]","ERROR");
		}
		trace("IMAGE   : [$file]","INFO");
		return("-gravity $this->Gravity \"$tmp_img\" -composite");
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
		return("-font \"$this->FontFam\" -gravity $this->Gravity -fill $this->FontFill -undercolor $this->Undercolor -pointsize $this->FontSize");
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