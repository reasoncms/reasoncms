<?php

// from fordiman at gmail dot com's comment at <http://www.php.net/manual/de/class.dir.php>
// e.g.: $files = preg_ls("../../loki/js", true, "/.*\.js$/i");
function preg_ls ($path=".", $rec=false, $pat="/.*/") {
   $pat=preg_replace ("|(/.*/[^S]*)|s", "\\1S", $pat);
   while (substr ($path,-1,1) =="/") $path=substr ($path,0,-1);
   if (!is_dir ($path) ) $path=dirname ($path);
   if ($rec!==true) $rec=false;
   $d=dir ($path);
   $ret=Array ();
   while (false!== ($e=$d->read () ) ) {
       if ( ($e==".") || ($e=="..") ) continue;
       if ($rec && is_dir ($path."/".$e) ) {
           $ret=array_merge ($ret,preg_ls($path."/".$e,$rec,$pat));
           continue;
       }
       if (!preg_match ($pat,$e) ) continue;
       //$ret[]=$path."/".$e; // NF: we don't want the path
       $ret[]=$e;
   }
   return (empty ($ret) && preg_match ($pat,basename($path))) ? Array ($path."/") : $ret;
}

?>

