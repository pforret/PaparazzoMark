@echo off
setlocal

:: requirements for this to work:
::    *** PHP ***
::    * PHP should be installed
::    * php.exe should appear in the %PATH%
::    * Verify:
::       >php -v
::       PHP 5.3.13 (cli) (built: May 14 2012 02:50:59)
::       Copyright (c) 1997-2012 The PHP Group
::       Zend Engine v2.3.0, Copyright (c) 1998-2012 Zend Technologies
php -v > NULL
if %ERRORLEVEL% <> 0 goto :nophp
::    *** IMAGEMAGICK ***
::    * ImageMagick shuld be installed
::    * convert.exe / identify.exe should appear in the %PATH%
::    * Verify:
::       >identify -version
::       Version: ImageMagick 6.8.5-9 2013-05-30 Q16 http://www.imagemagick.org
::       Copyright: Copyright (C) 1999-2013 ImageMagick Studio LLC
::       Features: DPC OpenMP
::       Delegates: bzlib fontconfig freetype jng jp2 jpeg lcms lzma png ps tiff x xml zlib
::
::       >magick -version
::       Version: ImageMagick 6.6.1-5 2010-04-23 Q16 http://www.imagemagick.org
::       Copyright: Copyright (C) 1999-2010 ImageMagick Studio LLC
::       Features: OpenMP
identify -version > NULL
if %ERRORLEVEL% <> 0 goto :nomagick
magick -version > NULL
if %ERRORLEVEL% <> 0 goto :nomagick

:: GITHUB is defined as my development root folder, where all Github projects are stored
set SCRIPT=%GITHUB%\PaparazzoMark\php_version\watermark.php
php.exe %SCRIPT%





:nophp
echo ERROR: PHP should be installed
goto :eof

:nomagick
echo ERROR: ImageMagick should be installed
goto :eof