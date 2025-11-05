<?php
$files = scandir( "./SAVED-FILES" , SCANDIR_SORT_DESCENDING);
foreach( $files as $file ){
$size = filesize("./SAVED-FILES/".$file);
  if($file ==  '..'){
	$thelist .= '<li><a href="./SAVED-FILES/'.$file.'"><h2>Return to Chart</h2></a></li>';  
  } else {
	if($file == '.'){
	} else {
   $thelist .= '<li><a href="./SAVED-FILES/'.$file.'">'.$file.' -- filesize -- '.round(($size / 1024)).' KB</a></li>';
    }
  }
}
?>
<h1>List of files:</h1>
<ul><li><a href="./SAVED-FILES/.."><h2>Return to Chart</h2></a></li></ul>
<ul><?php echo $thelist; ?></ul>

