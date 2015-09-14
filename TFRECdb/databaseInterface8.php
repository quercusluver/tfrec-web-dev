<?php
echo "<p>Note: Database Interface version A</p>\n";
include_once '/WebStuff/apacheSites/dbToolsV6c.php';
include_once '/WebStuff/apacheSites/cms3/CMSdbList6d.php';
//include '/WebStuff/apacheSites/cms3/methods/qOnFlyA.php';

//echo "<p>Classes: "; print_r(get_declared_classes()); echo "</p>\n";
//echo "<p>Methods: "; print_r(get_class_methods('QonFly')); echo "</p>\n";

function dataBaseInterface($dbAr) {
  // echo "<!-- \n";
  // echo print_r($dbAr);
  // echo " -->\n";
  $z=extract($dbAr);
  $indx=($index) ? $index : 'pid';
  //$tabl=new QonFly($database,$table,$indx,$display,array(),true);
  //echo "<!-- class file = $classfile -->\n";
  $tabl=($classfile=='qOnFlyA') ? 
      new QonFly($database,$table,$indx,$display,$subsetting,true) :
      new QExtended($database,$table,$indx,$display,$subsetting,true) ;
  if ($sortby) {$tabl->setSortBy($sortby);}
  if ($formtitle) {$tabl->setFormTitle($formtitle);}
  if ($bldInfo) {$tabl->setBuildInfo($bldInfo);}
  if ($xQueryInfo) {$tabl->setXQueryInfo($xQueryInfo);}
  if ($includedtables) {$tabl->includeTables($includedtables);}
  if ($formid) {$tabl->setFormId($formid);}
  if ($notifypage) {$tabl->setNotifyPage($notifypage);}
  if ($notifyuser) {$tabl->setNotifyUser($notifyuser);}
    else {$tabl->setNotifyUser(0);}
  if ($mode=='manage') {
    if ($notify) {$tabl->setNotifyList($notify);}
    $tabl->manageListing($pageref,array(),$varOrder);
  } elseif ($mode=='submit') {//echo "<p>Notify list=$notify</p>\n";
      $tabl->submitListing($pageref,$varOrder,$notify);
  } elseif ($mode=='export') {
    $tabl->exportListing($pageref,$varOrder,$notify);
  } elseif ($mode='table') {
    $tabl->tableListing();
  }
  return 1;
}

function adminDataBase($dbAr) {
  $z=extract($dbAr);
  //echo "<p>Database= $database</p>\n";
  $gofor=($_REQUEST['gofor']) ? $_REQUEST['gofor'] : '';
  if (!$gofor) {
    openFormA('','',$pageref,'POST','','','');
    echo "<p><strong>Table: ";
    radioInput('admintab','bldinfo');
    echo "Variable information ";
    radioInput('admintab','persons');
    echo "Authorized users ";
    echo "</p>\n";
    closeForm('Administrator','','');
  } else {
    $table=($_REQUEST['admintab']) ? $_REQUEST['admintab'] : $table;
    if ($table=='bldinfo') {$indx='vid'; $display="ctrlname||'_'||var AS row";}
      elseif ($table=='persons') {$indx='pid'; $display='wsuid';}
      else {$indx='';}
    $tabl=new Listing6($database,$table,$indx,$display,array(),true);
    $tabl->updateBuildInfo('ctrlname',99,'','Mode',0,
        "values=manage\tsubmit");
    $tabl->updateBuildInfo('var',12,'','Variable name',0,
        '');
    $tabl->manageListing($pageref,array('admintab'=>$table));
  }
  return 2;
}

/*if (class_exists('Listing6')) { echo "<p>Listing6 defined</p>\n";}
  else {echo "<p>No Listing6</p>\n";}
echo "<p>"; print_r($_REQUEST); echo "</p>\n";
echo "$includedDatabase\n";*/
$cmndAr=explode(';',stripTagsTool($includedDatabase));
$dbAr=array('classfile'=>'qOnFlyA');
$bldInfo=array();
$xQueryInfo=array();
$subseting=array();
$infoMode='';
foreach ($cmndAr as $dbEntry) {
  $item=explode('=',$dbEntry);
  $k=strtolower(trim($item[0]));
  if ($k=='build' OR $k=='browse') {
    if ($infoMode=='build') {
        $bldInfo[$var]=new TableItem($var,$ctrl,'',$titl,0,implode(';',$opts));}
    elseif ($infoMode=='browse') {
        $xQueryInfo[$var]=new TableItem($var,$ctrl,'',$titl,0,implode(';',$opts));}
    $infoMode=$k;
    $var=trim($item[1]);
    $opts=array();
  } elseif ($infoMode) {
    if ($k=='ctrl') {$ctrl=trim($item[1]);}
    elseif ($k=='titl') {$titl=trim($item[1]);}
    else {$opts[]=$dbEntry;}
  } else {
    $dbAr["$k"]=trim($item[1]);
  }
}
if ($infoMode=='build') {
    $bldInfo[$var]=new TableItem($var,$ctrl,'',$titl,0,implode(';',$opts));}
elseif ($infoMode=='browse') {
        $xQueryInfo[$var]=new TableItem($var,$ctrl,'',$titl,0,implode(';',$opts));}
$dbAr['bldInfo']=$bldInfo;

$dbAr['xQueryInfo']=$xQueryInfo;
$dbAr['subsetting']=$subsetting;
$classFile=($dbAr['classfile']) ? $dbAr['classfile'] : 'qOnFlyA';
include_once "/WebStuff/apacheSites/cms3/methods/$classFile.php";
if ($signMarker OR !$dbAr['signin']) {
//echo "<p>Calling with</p>\n<PRE>\n"; print_r($dbAr); echo "\n</PRE>\n";
  if ($dbAr['mode']=='admin') {$a=adminDataBase($dbAr);}
    else {$a=dataBaseInterface($dbAr);}
  //echo "<p>Result = $a </p>\n";
} else {
  echo "<p><strong><a href=\"",$dbAr['signin'],
      "\">Sign-in required</a> to view this page</strong></p>\n";
}
?>