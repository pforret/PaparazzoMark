<?php
include_once("pfor_tools.inc");

Class PrepMagick{
	var $Gravity="center";
	var $FontFam="Gabriola";
	var $FontSize="50";
	var $FontFill="#FFFF";
	var $Undercolor="#0000";

	function CmdAnnotate($html,$position="0"){
		$encoded=html_entity_decode($html,null, 'ISO-8859-1');
		$format=$this->GetTextFormat();
		return("$format -annotate $position \" $encoded \"");
	}

	function CmdImprint($file,$resize){
		if(!file_exists($file)){
			trace("Image [$file] can not be found","WARNING");
			return false;
		};
		return("-gravity $this->Gravity \"$file\" -composite");
	}


	//---------------
	function GetTextFormat(){
		return("-font \"$this->FontFam\" -gravity $this->Gravity -fill $this->FontFill -undercolor $this->Undercolor -pointsize $this->FontSize");
	}

}



?>