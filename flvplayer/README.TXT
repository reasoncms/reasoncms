HI GUYS

thanks for downloading my flash flv player. this flv player can be 
used standalone, without the need for flash mx 2004. the player 
allows you to show your videos more controlled and to a broader 
audience as with quicktime, windows media or real media. if you do 
work with flash mx 2004, this player is also smaller and easier to 
adjust than the components that accompany flash mx 2004.



INSTALLING

if you only have one flv file, name it "video.flv" and put it in 
the same directory as your webpage and the flvplayer.swf. now put
this code in your html page on the place you want the player to 
appear:

  <object type="application/x-shockwave-flash" width="400" height="220" 
    wmode="transparent" data="flvplayer.swf">
  <param name="movie" value="flvplayer.swf" />
  <param name="wmode" value="transparent" />
  </object>

you can adjust the width and height values to suit your video 
dimensions. the width should be the width of your video and the
height should be your video's width + 20 (for the controlbar). 



VARIABLES

you can give the flash movie two variables, both through the html 
code. the first one is the video file you want to play. if you 
want to play another file than the default video. for example if 
you have a movie "holiday.flv" inside the "movies" subdirectory, 
you can tell this to the flvplayer:

  <object type="application/x-shockwave-flash" width="400" height="220" 
    wmode="transparent data="flvplayer.swf?file=movies/holiday.flv">
  <param name="movie" value="flvplayer.swf?file=movies/holiday.flv" />
  <param name="wmode" value="transparent" />
  </object>

you can play movies from another website if you include the entire 
http:// path. great for saving bandwidth !

the second variable is autostart. the movie starts automatically 
by default, but you might want the user to press the play button. 
this is the code: 

  <object type="application/x-shockwave-flash" width="400" height="220" 
    wmode="transparent" data="flvplayer.swf?autoStart=false">
  <param name="movie" value="flvplayer.swf?autoStart=false" />
  <param name="wmode" value="transparent" />
  </object>

when you've set the autoStart to false, the video display shows a 
black screen with the words "click to play". if you put a jpg 
file with the same name and in the same folder as your flv file
online, that jpg file will be shown as a placehoder instead. the
default is, as with the flv, the name video.jpg

last but not least you can combine these variables if you put a 
"&" sign in between them. example:

  <object type="application/x-shockwave-flash" width="400" height="220" wmode="transparent"
    data="flvplayer.swf?file=movies/holiday.flv&autoStart=false">
  <param name="movie" value="flvplayer.swf?file=movies/holiday.flv&autoStart=false" />
  <param name="wmode" value="transparent" />
  </object>




INSTALLING INSIDE A FLASH MOVIE

if you have flash mx 2004, you can also choose to embed the player 
into a flash project. just copy-paste the movieclip from 
flvplayer.fla to your movie. at the top of the actionscript in the 
movieclip you can set the variables for the filename, autostart 
(true/false), width and height of the movie. you can also easily 
change the look and colors of the player, as long as you keep the 
symbol nesting intact.



SOME HINTS

you need flash mx 2004 with the 7.2 update from macromedia if you 
want to open the .fla file ! i cannot save this to a lower version 
since some scripting is only supported by this version.

you can encode flv video with the free flv encoder at 
www.rivavx.com. an article with some hints and pittfalls regarding 
flv compression can be found at my website. 

the jpg file cannot be saved as "progressive jpg". flash is unable
to load this type of jpegs.

the various versions of the flv video exporter that comes with flash 
mx 2004, as well as sorenson squeeze, do not export proper duration 
and screensize meta tags with the flv file. so the player doesn't 
know how long the file is and is unable to build a progress bar. a 
small tool that can be found at www.buraks.com/flvmdi/ fixes this 
error by inserting the proper duration metadata in the flv file. 
please try it if you have problems with proper scrubbing and 
autorewinding of the movie.

it is not possible to stop the automatic streaming of the video file 
(in theory it is, but flash has some bugs on the netStream.close() 
method). if you have many videos and want to reduce bandwidth, just 
use a jpg screenshot of the movie that is replaced by the video when 
you click on it.



LICENSING

This script is licensed under a Creative Commons License. 
(http://creativecommons.org/licenses/by-nc-sa/2.5/) It allows 
you to use, modify and redistribute the script as long as you use 
it non-commercially and credit me as the author. For commercial use, 
I distribute licenses of the script at a fee of 15 euros. Please 
contact me if you'd like to obtain one !



UPDATES

2.0: the fullscreen button has been discarded. All mediacomponent 
     dependencies have been removed.
2.1: I set the width and height of the video to be the same size 
     as the html object. all repositioning and rescaling based upon 
     the flv metadata are gone.
2.2: the script now doesn't automatically preload a video when the 
     autoStart is set to false. it shows a 'click to play' screen 
     instead. A jpg image is automatically detected and loaded instead.
2.3: After finishing the jpg cover is shown again. Script for resizing
     based on metadata is reinserted, but commented out.


SUPPORT

Check out my Flash Video Forums for questions or hints regarding 
this script and Flash Video.



GOOD LUCK

www.jeroenwijering.com
mail@jeroenwijering.com
