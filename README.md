papararazzo.cmd
===============
### details
+   Function: adds photographer watermark to photos - see [examples][1]
+   Author: Peter Forret / www.tangopaparazzo.com
+   Platform: Windows XP / Vista / 7
+   Requires: [ImageMagick][2] for Windows - (script uses convert.exe and identify.exe)


### Procedure
+ you process your originals and export your finished selection to a folder `/X` at resolution e.g. 2400px
+ you throw the script paparazzo.cmd into the folder `/X`
+ you start the script (double-click on the icon)
+ default: will create folder `/X/marked` with new, watermarked files
+ default photographer credit: your Windows username (change if necessary) - appears on top right
+ default album subscript: '[folder name]'
+ default new size: 1200px longest side
+ default: add black vignette (better for readability of text, but much slower)

[1]: http://tangopaparazzo.com/2011/08/marathon-del-chocolate-aug-2011/	"Tangopaparzzo.com"
[2]: http://www.imagemagick.org	"ImageMagick"