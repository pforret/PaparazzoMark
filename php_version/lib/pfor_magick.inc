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

	function CmdAnnotate($html,$position="0"){
		$encoded=html_entity_decode($html,null, 'ISO-8859-1');
		$format=$this->GetTextFormat();
		return("$format -annotate $position \" $encoded \"");
	}

	function CmdImprint($file,$resize=false,$padding=false){
		$file=realpath($file);
		if(!file_exists($file)){
			trace("Image [$file] can not be found","WARNING");
			return false;
		};
		$format="-gravity $this->Gravity ";
		if($resize OR $padding){
			$tmp_img="./_" . md5("$file"). ".png";
			$this->TmpFiles[]=$tmp_img;
			$line="";
			if($resize)	$line.="-resize $resize ";
			if($padding)	$line.="-bordercolor #0000 -border $padding ";
			$this->RunMagick("\"$file\" $line \"$tmp_img\"");
			trace("CREATE: watermark $file");
			return("$format \"$tmp_img\" -composite");
		} else {
			return("$format \"$file\" -composite");
		}
	}


	function RunMagick($line){
		$cmd="\"$this->Magick\" $line 2>&1";
		return cmdline($cmd);
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

}



?>