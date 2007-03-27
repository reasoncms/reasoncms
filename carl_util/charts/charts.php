<?php
// charts.php v2.4
// ------------------------------------------------------------------------
// Copyright (c) 2004, maani.us
// ------------------------------------------------------------------------
// This file is part of "PHP/SWF Charts"
//
// PHP/SWF Charts is a shareware. See http://www.maani.us/charts/ for
// more information.
// ------------------------------------------------------------------------

function DrawChart($chart){

	if (file_exists("charts.swf")){$path="";}
	else{$path=str_replace("charts.php","",str_replace("\\", "/", str_replace(realpath($_SERVER["DOCUMENT_ROOT"]), "", __FILE__)));}

	//defaults
	if(!isset($chart[ 'canvas_bg' ]['width' ])){$chart[ 'canvas_bg' ]['width' ] =400;}
	if(!isset($chart[ 'canvas_bg' ]['height' ])){$chart[ 'canvas_bg' ]['height' ] =250;}
	if(!isset($chart[ 'canvas_bg' ]['color' ])){$chart[ 'canvas_bg' ]['color' ] ="666666";}
								
	$params=GetParams($chart);
	if(isset($chart[ 'param_file' ])){
		file_write($path.$chart[ 'param_file' ], $params);
		$params="param_file=".$chart[ 'param_file' ]."&";
	}		
?>

<OBJECT classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" WIDTH=<?php print $chart[ 'canvas_bg' ]['width' ]; ?> HEIGHT=<?php print $chart[ 'canvas_bg' ]['height' ]; ?> ID="charts" ALIGN="">
<PARAM NAME=movie VALUE="<?php print $path."charts.swf"; ?>?<?php print $params; ?>"> 
<PARAM NAME=quality VALUE=high>
<PARAM NAME=bgcolor VALUE=<?php print $chart[ 'canvas_bg' ]['color' ]; ?> >
 
<EMBED src="<?php print $path."charts.swf"; ?>?<?php print $params; ?>" swLiveConnect=false quality=high ID="charts" NAME="charts" ALIGN="" TYPE="application/x-shockwave-flash" PLUGINSPAGE="http://www.macromedia.com/go/getflashplayer" bgcolor=<?php print $chart[ 'canvas_bg' ]['color' ]; ?> WIDTH=<?php print $chart[ 'canvas_bg' ]['width' ]; ?> HEIGHT=<?php print $chart[ 'canvas_bg' ]['height' ]; ?> ></EMBED>
</OBJECT>

<?php
}

//===============================
function UpdateChart($chart){
	echo GetParams($chart);
	exit;
}
//===============================
function GetParams($chart){
	$params="l_u=1&";
	$Keys1= array_keys($chart);
	for ($i1=0;$i1<count($Keys1);$i1++){
		if(gettype($chart[$Keys1[$i1]])=="array" ){
			$Keys2=array_keys($chart[$Keys1[$i1]]);
			if(gettype($chart[$Keys1[$i1]][$Keys2[0]])=="array" ){
				for($i2=0;$i2<count($Keys2);$i2++){
					$Keys3=array_keys($chart[$Keys1[$i1]][$Keys2[$i2]]);
					if(gettype($chart[$Keys1[$i1]][$Keys2[0]][$Keys3[0]])=="array" ){
						for($i3=0;$i3<count($Keys3);$i3++){
							$Keys4=array_keys($chart[$Keys1[$i1]][$Keys2[$i2]][$Keys3[$i3]]);
							$params=$params.$Keys1[$i1]."_".$Keys2[$i2]."_".$Keys3[$i3]."=";
							for($i4=0;$i4<count($Keys4);$i4++){
								$params=$params.$Keys4[$i4].":".$chart[$Keys1[$i1]][$Keys2[$i2]][$Keys3[$i3]][$Keys4[$i4]];
								if($i4<count($Keys4)-1){$params=$params.";";}
							}
							$params=$params."&";
						}
					}else{
						$params=$params.$Keys1[$i1]."_".$Keys2[$i2]."=";
						for($i3=0;$i3<count($Keys3);$i3++){
							$params=$params.$Keys3[$i3].":".$chart[$Keys1[$i1]][$Keys2[$i2]][$Keys3[$i3]];
							if($i3<count($Keys3)-1){$params=$params.";";}
						}
						$params=$params."&";
					}
				}
			}else{
				$params=$params.$Keys1[$i1]."=";
				for($i2=0;$i2<count($Keys2);$i2++){
					$params=$params.$Keys2[$i2].":".$chart[$Keys1[$i1]][$Keys2[$i2]];
					if($i2<count($Keys2)-1){$params=$params.";";}
				}
				$params=$params."&";
			}
		}else{
			$params=$params.$Keys1[$i1]."=".$chart[$Keys1[$i1]]."&";
		}
	}
	return $params;
}
//===============================
function file_write($filename, $content) { 
	if (!file_exists($filename)){
		echo "File ($filename) doesn't exist.";
		exit;
	}
	if (!$fp = @fopen($filename, "w")) {
		echo "Cannot open file ($filename). Verify the file's permissions.";
		exit;
	}
	if (fwrite($fp, $content) === FALSE) {
		echo "Cannot write to file ($filename). Verify the file's permissions.";
		exit;
	} 
	if (!fclose($fp)) {
		echo "Cannot close file ($filename)";
		exit;
	}
} 
//===============================
?>