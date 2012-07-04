<?php
$RECDIR='/c/regza';
$MARKS=array(
  '' => '新⃞',
  '' => '字⃞',
  '' => 'デ⃞',
  '' => '終⃞',
  '' => 'Ｓ⃞',
  '' => 'Ⅱ',
  '' => 'Ⅱ',
  '' => '①',
  '' => '②',
  '' => '③',
  '' => '④',
);

function getDtvMeta($filename, $desc=false) {
  global $MARKS;
  $content = file_get_contents($filename.'.meta');
  $title = substr($content, 0x01B, 128);
  $title = mb_convert_encoding($title, 'UTF-8', 'eucjp-win');
  $bcdate = substr($content, 0x0EE, 20);
  $bctime = substr($content, 0x10E, 8);
  $rectime1 = substr($content, 0x14E, 8);
  $rectime2 = substr($content, 0x16E, 8);
  $station = substr($content, 0x0A3, 20);
  $station = mb_convert_encoding($station, 'UTF-8', 'eucjp-win');
  $size = exec('stat -c %s '. $filename);
  $description = null;
  if ($desc) {
    $description = substr($content, 0x5E0, 256);
    $description = mb_convert_encoding($description, 'UTF-8', 'eucjp-win');
  }

  if (($hsize = (double)$size / (1024*1024*1024)) > 1) {
    $hsize = sprintf("%.2fGB", $hsize);
  } else if (($hsize = (double)$size / (1024*1024)) > 1) { 
    $hsize = sprintf("%.2fMB", $hsize);
  } else if (($hsize = (double)$size / 1024) > 1) { 
    $hsize = sprintf("%.0fkB", $hsize);
  } else {
    $hsize = sprintf("%dB", $size);
  }

  return array (
    'bcdate' => strtr($bcdate, '_', ':'),
    'bctime' => strtr($bctime, '_', ':'),
    'rectime1' => strtr($rectime1, '_', ':'),
    'rectime2' => strtr($rectime2, '_', ':'),
    'title' => strtr($title, $MARKS),
    'station' => $station,
    'file' => basename($filename),
	'size' => $hsize,
    'dir' => false,
    'desc' => strtr($description, $MARKS),
  );
}

$subdir=filter_input(INPUT_GET, 's', FILTER_SANITIZE_STRING);
$subdir=preg_replace('/^\/+/', '', $subdir);
$subdir=preg_replace('/\.\.\//', '/', $subdir);

$file=filter_input(INPUT_GET, 'f', FILTER_SANITIZE_STRING);
$file=preg_replace('/^\/+/', '', $file);
$file=preg_replace('/\.\.\//', '/', $file);

$list = array();
$BASEDIR=$RECDIR.'/'.$subdir;
$is_dir=false;
$is_file=false;
if (!empty($file) && is_readable($BASEDIR.'/'.$file)) {
  $list = getDtvMeta($BASEDIR.'/'.$file, true); 
  $headlink =
      sprintf('<a href="%s">%s</a>', 
          $_SERVER['PHP_SELF'].'?s='.urlencode(substr($BASEDIR, strlen($RECDIR)+1)),
          htmlspecialchars($BASEDIR));
  $is_file=true;
} else if (is_dir($BASEDIR)) {
  $dh = opendir($BASEDIR);
  while (false !== ($entry = readdir($dh))) {
    if (preg_match('/^\./', $entry)) continue;
    if (!preg_match('/\.meta$/', $entry) && !is_dir($BASEDIR.'/'.$entry))
        continue;

    $filename = $BASEDIR.'/'.$entry;
    if (is_file($filename)) {
	  $dtvfile = preg_replace('/\.meta$/', '', $filename);
      $res = getDtvMeta($dtvfile);
    } else if (is_dir($filename)) {
      $res = array (
	    'bcdate' => '',
	    'bctime' => '',
	    'rectime1' => '',
	    'rectime2' => '',
	    'station' => '',
	    'title' => $entry,
	    'dir' => $subdir ? $subdir.'/'.$entry : $entry,
      );
    }
	$list[] = $res;
  }
  sort($list);
  $parentdir = dirname($BASEDIR);
  if (strcmp($parentdir, $RECDIR) >= 0) {
    $headlink =
        sprintf('<a href="%s">%s</a>%s', 
            $_SERVER['PHP_SELF'].'?s='.urlencode(substr($parentdir, strlen($RECDIR)+1)),
            htmlspecialchars($parentdir),
            '/'.substr($BASEDIR, strrpos($BASEDIR, '/')+1));
  } else {
    $headlink =
        sprintf('<a href="%s">%s</a>', $_SERVER['PHP_SELF'], htmlspecialchars($RECDIR));
  }
  $is_dir=true;
}
?><!DOCTYPE html>
<html>
<head>
  <title>REGZA Viewer</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>
<!--
<?php echo json_encode($MARKS), PHP_EOL;?>
-->
<h1><?php echo $headlink; ?></h1>
<table border="1" cellpadding="3" cellspacing="0">
<?php if ($is_dir) { ?>
<tr>
  <th>放送開始日時</th>
  <th>番組名</th>
  <th>放送時間</th>
  <th>録画時間</th>
  <th>放送局</th>
</tr>
<?php foreach($list as $entry) {?>
<tr>
  <td><?php echo htmlspecialchars($entry['bcdate']);?></td>
  <?php if ($entry['dir']) {?>
  <td><a href="<?php echo $_SERVER['PHP_SELF'],'?s=',urlencode($entry['dir']);?>"><?php echo htmlspecialchars($entry['title']);?></a></td>
  <?php } else { ?>
  <td><a href="<?php echo $_SERVER['PHP_SELF'],'?s=',urlencode($subdir),'&f=',urlencode($entry['file']);?>"><?php echo htmlspecialchars($entry['title']);?></a></td>
  <?php } ?>
  <td><?php echo htmlspecialchars($entry['bctime']);?></td>
  <td><?php echo htmlspecialchars($entry['rectime1']);?></td>
  <td><?php echo htmlspecialchars($entry['station']);?></td>
</tr>
<?php } ?>
<?php } else { ?>
<tr>
  <th width="120px">放送開始日時</th>
  <td><?php echo htmlspecialchars($list['bcdate']);?></td>
  <th width="120px">放送時間</th>
  <td><?php echo htmlspecialchars($list['bctime']);?></td>
  <th width="120px">放送局</th>
  <td><?php echo htmlspecialchars($list['station']);?></td>
</tr>
<tr>
  <th>番組名</th>
  <td colspan="5"><?php echo htmlspecialchars($list['title']);?></td>
</tr>
<tr>
  <th>番組概要</th>
  <td colspan="5"><?php echo nl2br(htmlspecialchars($list['desc']));?></td>
</tr>
<tr>
  <th>録画時間</th>
  <td><?php echo htmlspecialchars($list['rectime1']);?></td>
  <th>ファイルサイズ</th>
  <td colspan="3"><?php echo htmlspecialchars($list['size']);?></td>
</tr>
<?php } ?>
</table>
</body>
<html>
