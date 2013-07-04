<?php
include_once("pfor_tools.inc");

Class IniFile{
	var $ready=false;
	var $defname="_default";

	function __construct($file){
		if(!$file){
			trace("IniFile:: need filename to initialize");
			return false;
		}
		if(!file_exists($file)){
			trace("IniFile:: cannot find file [$file]");
			return false;
		}
		$this->data=parse_ini_file($file,true);
		if(!$this->data){
			trace("IniFile:: cannot interpret INI file [$file] (is it a valid INI file?)");
			return false;
		}
		//trace($this->data);
		$this->ready=true;
		return true;
	}

	function get_sections(){
		if(!$this->ready){
			trace("IniFile::get_value - obj not properly initilaized");
			return false;
		}
		$answer=Array();
		foreach($this->data as $sect_name => $sect_data){
			if($sect_name != $this->defname AND substr($sect_name,0,1) <> "_"){
				$answer[]=$sect_name;
			}
		}
		return $answer;
	}

	function get_value($section,$param,$defval=false){
		$return=false;
		if(!$this->ready){
			trace("IniFile::get_value - obj not properly initilaized");
			return false;
		}
		if(!$param){
			trace("IniFile::get_value - need param name to get [$section,$param]");
			return false;
		}
		if(isset($this->data[$section][$param])){
			$return=$this->data[$section][$param];
			trace("get_value: [$section][$param] -> [$return]");
			return $return;
		}
		if(isset($this->data[$this->defname][$param])){
			$return=$this->data[$this->defname][$param];
			trace("get_value: [$this->defname][$param] -> [$return]");
			return $return;
		}
		if(isset($defval)){
			$return=$defval;
			trace("get_value: [$section][$param] -> [$return] (default)");
			return $return;
		}
		trace("get_value: [$section][$param] -> (empty)");
		return false;
	}
}



?>