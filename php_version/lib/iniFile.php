<?php

include_once 'Tools.php';

class IniFile
{
    public bool $ready = false;

    public string $defname = '_default';

    private array|false $data;

    public function find_file($arr_folders = false, $filename = 'pmark.ini'): string
    {

        $ifile = false;
        $ini = false;

        trace('SOURCE  : ['.shorten_path(getcwd()).']', 'INFO');
        foreach ($arr_folders as $arr_folder) {
            $inipath = "$arr_folder/$filename";
            if (! $ifile) {
                if (file_exists($inipath)) {
                    $ifile = realpath($inipath);
                }
            }
        }
        if (! $ifile) {
            trace('No INI instructions found', 'ERROR');

            return '';
        } else {
            trace('INI FILE: ['.shorten_path($ifile).']', 'INFO');

            return $ifile;
        }
    }

    public function read_file($file): bool
    {
        if (! $file) {
            trace('IniFile:: need filename to initialize');

            return false;
        }
        if (! file_exists($file)) {
            trace("IniFile:: cannot find file [$file]");

            return false;
        }
        $this->data = parse_ini_file($file, true);
        if (! $this->data) {
            trace("IniFile:: cannot interpret INI file [$file] (is it a valid INI file?)");

            return false;
        }
        // trace($this->data);
        $this->ready = true;

        return true;
    }

    public function get_sections(): array
    {
        if (! $this->ready) {
            trace('IniFile::get_value - obj not properly initilaized');

            return [];
        }
        $answer = [];
        foreach ($this->data as $sect_name => $sect_data) {
            if ($sect_name != $this->defname and substr($sect_name, 0, 1) != '_') {
                $answer[] = $sect_name;
            }
        }

        return $answer;
    }

    public function get_value($section, $param, $defval = false)
    {
        $return = false;
        if (! $this->ready) {
            return false;
        }
        if (! isset($this->data[$section])) {
            return false;
        }
        if (! $param) {
            return false;
        }
        if (isset($this->data[$section][$param])) {
            return $this->data[$section][$param];
        }
        if (isset($this->data[$this->defname][$param])) {
            return $this->data[$this->defname][$param];
        }
        if (isset($defval)) {
            return $defval;
        }

        return false;
    }

    public function get_all_values($section): array
    {
        if (! $this->ready) {
            return [];
        }
        if (! isset($this->data[$section])) {
            return [];
        }
        $keylist = [];
        foreach ($this->data[$section] as $key => $val) {
            $keylist[$key] = $key;
        }
        if (isset($this->data[$this->defname])) {
            foreach ($this->data[$this->defname] as $key => $val) {
                $keylist[$key] = $key;
            }
        }
        sort($keylist);
        $all_values = [];
        foreach ($keylist as $param) {
            $val = $this->get_value($section, $param);
            $all_values[$param] = $val;
        }
        if ($all_values) {
            return $all_values;
        } else {
            return [];
        }
    }
}
