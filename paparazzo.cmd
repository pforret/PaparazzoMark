@echo off
setlocal
set VERSION=paparazzo.cmd v 1.0 - Oct 2011
set MAGICK=convert.exe
set IDENTIFY=identify.exe
set MAG_COLOR=-background #0003 -fill #FFF8

set FONTNAME="Verdana"
set COPY_FONT=-gravity NorthEast -fill #FFF4 -font %FONTNAME% -pointsize 24
set SUBS_FONT=-gravity SouthEast -fill #FFF8 -font %FONTNAME% -pointsize 36
::set MAG_VIGN=-unsharp 3
set SRC=%1
if "%1" == "" set SRC=%CD%
cls
echo =============
echo --- IN  = %SRC%
for /F %i in (%SRC%) do set NAME=%%~ni

call :inputvar DEST 		%SRC%\marked	"Destination folder"
if not exist %DEST%\. mkdir %DEST%
echo --- OUT = %DEST%

call :inputvar COPYRIGHT 	"%USERNAME%"	"Photographer credit"

call :inputvar SUBSCRIPT 	"%NAME%"		"Subscript of album"

call :inputvar MAXPIX    	1200 			"Max size (pixels)"

call :inputvar VIGNETTE    	Y 				"Add dark vignetting (slower!)"
if "%VIGNETTE%" == "Y" set MAG_VIGN=-background #0008 -vignette 0x300-30-30%%

pushd %SRC%
for %%f in ( *.jpg ) do (
	call :markone "%%f"
)

popd


goto :eof

:usage

echo ==== %VERSION%

goto :eof

:inputvar
:: call :inputvar VARNAME DEFVALUE [QUESTION]
set TEMPVAR=
set QUESTION=%3
set QUESTION=%QUESTION:"=%
if [%3] == [] set QUESTION=What is the value of %1?
set DEFVALUE=%2
set DEFVALUE=%DEFVALUE:"=%
if [%2] == [] set DEFVALUE=0
echo Question: %QUESTION%
echo Default = %DEFVALUE%
set /p TEMPVAR=........  
set %1=%TEMPVAR%
if "%TEMPVAR%" == "" set %1=%DEFVALUE%


goto :eof

:markone

set INFILE=%1
call :getimgtime %1

set OUTFILE=PFOR_%IMGDAY%_%IMGHOUR%_%IMGID%.jpg
set OUTPUT=%DEST%\%OUTFILE:"=%

set TXT_COPY="   Photo: %IMGYEAR% %COPYRIGHT%   "
set TXT_SUBS="   %SUBSCRIPT%   "
::@echo on
if not exist "%OUTPUT%" (
	echo %TIME%	creating %OUTFILE% ...
	%MAGICK% %1 -resize %MAXPIX%x%MAXPIX% %MAG_VIGN% %COPY_FONT% -annotate 0 %TXT_COPY% %SUBS_FONT% -annotate 0 %TXT_SUBS% -quality 90 "%OUTPUT%"
	)
@echo off

goto :eof

:getimgtime
for /f "usebackq delims=|" %%c in (`%IDENTIFY% -format %%[EXIF:DateTime] %1`) do (
	set IMGTIME=%%c
)
set IMGYEAR=%IMGTIME:~0,4%
set IMGDAY=%IMGTIME:~5,5%
set IMGDAY=%IMGDAY::=%

set IMGHOUR=%IMGTIME:~11,5%
set IMGHOUR=%IMGHOUR::=%

set IMGID=%1
set IMGID=%IMGID:_=%
set IMGID=%IMGID: =%
set IMGID=%IMGID:"=%
set IMGID=%IMGID:IMG=%
set IMGID=%IMGID:_MG=%
set IMGID=%IMGID:.jpg=%
set IMGID=%IMGID:.JPG=%
set IMGID=%IMGID:~-4%

goto :eof