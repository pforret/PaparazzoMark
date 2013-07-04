<?php
$text=html_entity_decode('&copy; I&ntilde;t&euml;rn&acirc;ti&ocirc;n&agrave;liz&aelig;ti&oslash;n',null, 'ISO-8859-1');
//$text="test";
$bname=basename($_SERVER['PHP_SELF'],".php");
$output="$bname.png";
echo "TEXT = [$text]\n";
echo "OUTPUT = [$output]\n";
// See more at: http://www.phmagick.org/examples/examples/add-a-watermark-to-your-images#sthash.jDhBqN6S.dpuf

$format="-font Gabriola -gravity Center -fill #F60F -pointsize 50 ";
$cmd="convert.exe -verbose logo: $format -annotate 0 \"$text\" $output 2>&1";
echo "$cmd\n";
exec($cmd,$stdout);
print_r($stdout);
?>