<?php

include_once 'Tools.php';

class PrepMagick
{
    public string $Gravity = 'center';

    public string $FontFam = 'Gabriola';

    public string $FontSize = '50';

    public string $FontFill = '#FFFF';

    public string $Undercolor = '#0000';

    public array $TmpFiles = [];

    public string $Magick = 'magick.exe';

    public string $Identify = 'identify.exe';

    public string $TmpDir = '.temp';

    public function __construct()
    {
        if (! file_exists("$this->TmpDir/.")) {
            trace("PrepMagick: create temp folder [$this->TmpDir]");
            mkdir($this->TmpDir);
            sleep(1);
            if (! file_exists("$this->TmpDir/.")) {
                trace('PrepMagick: temp folder could not be created]', 'error');
            }
        } else {
            trace("PrepMagick: using temp folder [$this->TmpDir]");
        }
    }

    public function overlay_text(array $parameters): string
    {
        $text = $this->getvalue($parameters, 'text', '');
        $font = $this->getvalue($parameters, 'text_font', 'Courier');
        $size = $this->getvalue($parameters, 'text_size', 50);
        $color = $this->getvalue($parameters, 'text_color', '#FFF');
        $style = $this->getvalue($parameters, 'text_effect', '');
        $padding = $this->getvalue($parameters, 'padding', 0);
        $grav = $this->getvalue($parameters, 'gravity', 'Center');
        $background = $this->getvalue($parameters, 'background', '#0000');
        $undercolor = $this->getvalue($parameters, 'undercolor', false);
        $alpha = $this->getvalue($parameters, 'alpha', 100);
        $rotation = $this->getvalue($parameters, 'rotation', 0);

        $encoded = html_entity_decode($text, ENT_QUOTES, 'ISO-8859-1');

        // let's make a temp image
        $begin = preg_replace('#[^a-zA-Z0-9]*#', '', $encoded);
        $begin = substr($begin, 0, 8);
        $tmp_txt = "$this->TmpDir/_$begin.$size.".substr(md5(serialize($parameters)), 0, 8).'.png';
        $this->TmpFiles[] = $tmp_txt;

        $format = '';
        $line = "-size 2000x2000 canvas:#0000 -channel RGBA -font \"$font\" -pointsize $size -gravity $grav ";
        $style = strtolower($style);
        $angle = 0;
        if (str_contains($style, 'italic')) {
            $angle = 10;
        }
        if ($rotation) {
            // $line.="-rotate $rotation ";
            $angle += $rotation;
        }
        $shadow = 1;
        if ($size > 60) {
            $shadow = round($size / 30);
            trace("Using shadow distance of $shadow");
        }
        switch (true) {
            case str_contains($style, 'shadow'):
                // add a shadow on the right and bottom
                $scolor = $this->CalcShowColor($color);
                $line .= "-fill \"$scolor\" ";
                $line .= "-annotate {$rotation}x{$angle}+0+$shadow \"$encoded\" ";
                $line .= "-annotate {$rotation}x{$angle}+$shadow+$shadow \"$encoded\" ";
                $line .= "-annotate {$rotation}x{$angle}+$shadow+0 \"$encoded\" ";
                $line .= '-blur 0x1 ';
                $line .= "-fill \"$color\" ";
                if ($undercolor) {
                    $line .= "-undercolor $undercolor ";
                }
                break;
            case str_contains($style, 'outline'):
                // like a shadow all around
                $scolor = $this->CalcShowColor($color);
                $line .= "-fill \"$scolor\" ";
                $line .= "-annotate {$rotation}x{$angle}+$shadow+$shadow \"$encoded\" ";
                $line .= "-annotate {$rotation}x{$angle}-$shadow+$shadow \"$encoded\" ";
                $line .= "-annotate {$rotation}x{$angle}+$shadow-$shadow \"$encoded\" ";
                $line .= "-annotate {$rotation}x{$angle}-$shadow-$shadow \"$encoded\" ";
                // $line.="-blur 0x1 ";
                $line .= "-fill \"$color\" ";
                if ($undercolor) {
                    $line .= "-undercolor \"$undercolor\" ";
                }
                break;
            default:
                $line .= "-fill \"$color\" ";
                if ($undercolor) {
                    $line .= "-undercolor \"$undercolor\" ";
                }
        }
        $line .= "-annotate {$rotation}x{$angle}+0+0 \"$encoded\" ";
        // remove excess borders, and add transparent padding border
        if ($alpha < 100) {
            $line .= ' -alpha on -channel a -evaluate multiply '.round($alpha / 100, 2).' ';
        }
        $line .= "-trim +repage -bordercolor \"$background\" -border $padding -quality 99 ";
        $this->RunMagick(" $line \"$tmp_txt\"");
        //        if (! file_exists($tmp_txt)) {
        //            trace("Cannot create TXT image for [$html]", 'ERROR');
        //        }
        trace("MARK TXT: [$begin]", 'INFO');

        return "-gravity $grav \"$tmp_txt\" -composite";
    }

    public function overlay_image(array $parameters): string
    {
        $filePath = $parameters['image'];
        if (! file_exists($filePath)) {
            trace("Image [$filePath] can not be found", 'WARNING');

            return false;
        }
        $filePath = realpath($filePath);
        $fileName = basename($filePath);

        $padding = $this->getvalue($parameters, 'padding', 0);
        $back = $this->getvalue($parameters, 'background', '#0000');
        $resize = $this->getvalue($parameters, 'resize', false);
        $grav = $this->getvalue($parameters, 'gravity', 'Center');
        $alpha = $this->getvalue($parameters, 'alpha', '100');
        $format = "-gravity $grav ";
        // create unique temp file
        $begin = preg_replace('#[^a-zA-Z0-9]*#', '', basename($filePath));
        $begin = substr($begin, 0, 8);
        $tmp_img = "$this->TmpDir/_img.$begin.".substr(md5(serialize($parameters)), 0, 8).'.png';
        $this->TmpFiles[] = $tmp_img;
        $line = '';
        if ($resize) {
            $line .= "-resize $resize ";
        }
        if ($alpha < 100) {
            $line .= ' -alpha on -channel a -evaluate multiply '.round($alpha / 100, 2).' ';
        }
        if ($padding) {
            $line .= "-bordercolor \"$back\" -border $padding ";
        }
        $line .= '-quality 99 ';
        $this->RunMagick("\"$filePath\" $line \"$tmp_img\"");
        if (! file_exists($tmp_img)) {
            trace("Cannot create image for [$filePath]", 'ERROR');
        }
        trace("MARK IMG: [$fileName]", 'INFO');

        return "-gravity $grav \"$tmp_img\" -composite";
    }

    public function set_tags(array $parameters)
    {
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
        $tmp_txt = "$this->TmpDir/_iptc.".substr(md5(serialize($parameters)), 0, 8).'.txt';
        $iptc_tags = [];
        if ($parameters['contact_name']) {
            $iptc_tags[] = $this->format_iptc('2#110#Credit', $parameters['contact_name']);
        }
        if ($parameters['contact_email']) {
            $iptc_tags[] = $this->format_iptc('2#118#Contact', $parameters['contact_email']);
        }
        if ($parameters['event']) {
            $iptc_tags[] = $this->format_iptc('2#05#Object Name', $parameters['event']);
        }
        if ($parameters['tags']) {
            $tags = explode(' ', $parameters['tags']);
            foreach ($tags as $tag) {
                $iptc_tags[] = $this->format_iptc('2#25#Keyword', $tag);
            }
        }
        if ($parameters['caption']) {
            $iptc_tags[] = $this->format_iptc('2#120#Caption', $parameters['caption']);
        }
        if ($parameters['contact_url']) {
            $iptc_tags[] = $this->format_iptc('2#80#By-line', $parameters['contact_url']);
            $iptc_tags[] = $this->format_iptc('2#116#Copyright Notice', $parameters['contact_url']);
            // $iptc_tags[]=$this->format_iptc("2#120#Caption",$aparams["contact_url"]);
        }
        if ($iptc_tags) {
            sort($iptc_tags);
            $iptc_text = implode("\r\n", $iptc_tags);
            file_put_contents($tmp_txt, $iptc_text);
            $this->TmpFiles[] = $tmp_txt;
            trace('SET TAGS: ['.$parameters['contact_name'].']', 'INFO');

            return "-strip -profile 8BIMTEXT:$tmp_txt";
        }

    }

    public function format_iptc($key, $val): string
    {
        return "$key=\"$val\"";
    }

    public function getvalue($avalues, $key, $default): mixed
    {
        return $avalues[$key] ?? $default;
    }

    public function RunMagick($line): array
    {
        $cmd = "\"$this->Magick\" $line 2>&1";
        $result = cmdline($cmd);
        if ($result) {
            trace($result);
        }

        return $result;
    }

    public function Cleanup(): void
    {
        foreach ($this->TmpFiles as $tmpfile) {
            unlink($tmpfile);
        }
    }

    public function CalcShowColor($original)
    {
        return match ($original) {
            '#FFF', '#FFFF' => '#0008',
            '#000', '#000F' => '#FFF8',
            default => '#0008',
        };
    }
}
