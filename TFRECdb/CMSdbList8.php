<?php  //echo "<p>Listing classes ";

function createJSarray($name,$ar) {
  foreach ($ar as &$st) {$st="'".$st."'";}
  unset($st);
  return "var $name = [".implode(',',$ar)."];";
}

class Listing6 extends ExPages6 {

protected $displayText;
protected $displayQuery;
protected $arTextFlds;
protected $formid;
protected $notifyPage;
protected $sortby;
protected $formTitle;
protected $notifyList;
protected $notifyUser;

protected $xQueryInfo;

protected $includedTablAr; //array of IncludedTabls objects


function __construct($dbName,$tablName,$indx,$displayText,$ar_subset,$rw) {
   global $allDBConns;
   echo "<!-- Constructing $tablName -->\n";
   $this->dbName=$dbName;
   if (!$allDBConns) {$allDBConns=new DBConnManager();}
   $this->dbConn=$allDBConns->getConn($this->dbName,$rw);
   $tabl=$tablName;
   $this->tabl=$tabl;
   $r=$this->xQuery("SELECT * FROM $tabl LIMIT 1;",0);
   $ar=pg_fetch_assoc($r);
   pg_free_result($r);
   if (!$ar) {echo "<p><strong>Warning: database may need ",
       "intializing</strong></p>\n";}
   $this->colNames=array_keys($ar);
   if ($indx AND !in_array($indx,$this->colNames)) {$indx='';}
   if (!$indx) {echo "<p> Index (",$this->getIndx(),") not found <br />\n";
     print_r($this->colNames);echo "</p>\n";}
   $this->indx=$indx;
   if (in_array('obid',$this->colNames) and in_array('active',$this->colNames)
       //and in_array('lastdate',$this->colNames)
       ) {$wiki=1;}
     else {$wiki=0;}
   $this->useWiki=$wiki;
   if ($this->useWiki) {
     $this->colNames=array_diff($this->colNames,
       array('obid','active','lastdate'));
   }
   $this->ar_subset=$ar_subset;
   $this->itemInfo=$this->buildInfo();
   //echo "<p>display = $displayText</p>\n";
   if ($p=strpos($displayText,' AS ')) {
     //echo "<p>parsing display...</p>\n";
     $this->displayQuery=substr($displayText,0,$p);
     $this->displayText=substr($displayText,$p+4);
   } elseif(strpos($displayText,',')) {
     $this->displayQuery=$this->buildDisplayQuery($displayText);
     $this->displayText='_display';
   } else {
     $this->displayQuery='';
     $this->displayText=$displayText;
   }
   
   $this->arTextFlds=array();
   $this->formid='';
   $this->itemInfo=$this->buildInfo();
   $this->xQueryInfo=array();
   $this->includedTablAr=array();
   echo "<!-- $tablName constructed -->\n";
}

function buildDisplayQuery($strng) {
  $ar=explode(',',$strng);
  $vars=implode("||':'||",$ar);
  return $vars;    
}

function setXQueryInfo($xQueryInfo) {
  $this->xQueryInfo=$xQueryInfo;
  return 1;
}

function setFormId($id) {
  $this->formid=$id;
  return 1;
}

function setNotifyPage($url) {
  $this->notifyPage=$url;
  return 1;
}

function setNotifyList($list) {
  $this->notifyList=$list;
  return 1;
}

function setNotifyUser($iParm) {
  echo "<!-- notifyUser set to $iParm -->\n";
  $this->notifyUser=$iParm;
  return 1;
}

function getDbRow($indx) {
  if ($this->useWiki) {$useAct=' AND active>0 ;';} else {$useAct=';';}
  if ($this->displayQuery) {$dQ=','.$this->displayQuery.' AS '.
      $this->displayText;} else {$dQ='';}
  $rX=$this->xQuery("SELECT *"."$dQ FROM ".$this->tabl." WHERE ".
      $this->indx."='$indx' $useAct",0);
  $arX=pg_fetch_assoc($rX);
  pg_free_result($rX);
  $this->currentRow=$arX;
  return $arX;
}

function getRowList() {
  if ($this->useWiki) {$useAct=' WHERE active>0 ';} else {$useAct='';}
  $wh=$this->sqlSubset();
  if ($wh and !$useAct) {$useAct=' WHERE ';}
    elseif ($wh) {$useAct.=' AND ';}
    else {$nought=0;}
  if ($wh) {$useAct.=$wh;}
  $dQ=($this->displayQuery) ? $this->displayQuery.' AS '.$this->displayText :
      $this->displayText;
  $r=$this->xQuery("SELECT ".$this->indx.",$dQ FROM ".
    $this->tabl." $useAct ORDER BY ".$this->getDisplayText().";",1);
  $ids=pg_fetch_all_columns($r,0);
  $names=pg_fetch_all_columns($r,1);
  pg_free_result($r);
  return array_combine($ids,$names);
}

function getIndx() {
  return $this->indx;
}
function getTableName() {
  return $this->tabl;
}
function getDisplayText() {
  return $this->displayText;
}
function getDisplayQuery() {
  return $this->displayQuery;
}
function setArTextFlds($ar) {
  $this->arTextFlds=$ar;
  return 1;
}
function getArTextFlds() {
  return $this->arTextFlds;
}
function upLoadButton() {
  return 0;
}

function fillFields() {
  return 0;
}

function closerButton() {
  return '';
}

function setSortBy($sortby) {
  $this->sortby=$sortby;
}

function setFormTitle($formTitle) {
  $this->formTitle=$formTitle;
  return 1;
}

function cmsForm($tag,$name,$action,$method,$closeForm,$submit,$hiddenVars) {
  echo "<form action=\"$action\" method=\"$method\" ";
  if ($name) {echo "name=\"$name\" id=\"$name\" ";}
  echo ">\n";
  if ($tag) {echo "<b>$tag</b> ";}
  if ($_REQUEST['method']) {$hiddenVars['method']=$_REQUEST['method'];}
  //if ($_REQUEST['dottle']) {$hiddenVars['dottle']=$_REQUEST['dottle'];}
  reset($hiddenVars);
  while (list($k,$v)=each($hiddenVars)) {
    echo "<input type=\"hidden\" name=\"$k\" id =\"$k\" value=\"$v\" />\n";
  }
  if ($submit) {
    echo "<input type=\"submit\" name=\"gofor\" value=\"$submit\" />\n";
  }
  if ($closeForm>0) {echo "</form>\n";}
  return 1;
} //end cmsForm

function removeParaMarks(&$ar) {
  foreach ($ar AS $k => &$v) {
    if (in_array($k,$this->arTextFlds)) {$v=noParaMarks($v);
        echo "<!-- $k checked -->\n";}      
  }
  unset($v);
  return 0;
}


function notify($notify,$pid,$ar) {
  $qVars=array_intersect_key($ar,jt_fill_new($this->colNames,$i));
  $pageName=$this->notifyPage;  $outp="<html>\r\n<head>\r\n<title>".$this->formTitle."</title>\r\n</head>\r\n<body>\r\n";
  $outp.="<p><strong>A TFREC administrative database entry requires your ";
  $outp.="attention: <br />\r\n$pageName/$pid</strong></p>\r\n";
  $outp.="<hr />\r\n";
  //$ar=$this->getDbRow($pid);
  $outp.=str_replace("\n","\r\n",$this->displayListing($ar));
  $outp.="</body>\r\n</html>\r\n";
  /*$addlHeaders="From: Kevin Larson <kevin_larson@wsu.edu>\r\n".
      "Reply-To: kevin_larson@wsu.edu\r\n".
      "Cc: Kevin Larson <kevin_larson@wsu.edu>,".
      "Jerry Tangren <jmoreland@wsu.edu>\r\n";*/
  $addlHeaders="MIME-Version: 1.0\r\n";
  $addlHeaders.="Content-type: text/html; charset=iso-8859-1\r\n";
  if ($this->formTitle=='Maintenance Request') {
    $addlHeaders.="From: Jerry Moreland <jmoreland@wsu.edu>\r\n";
    $addlHeaders.="Reply-To: jmoreland@wsu.edu\r\n";  
  } else {
    $addlHeaders.="From: Christina Mayer <cmayer@wsu.edu>\r\n";
    $addlHeaders.="Reply-To: cmayer@wsu.edu\r\n";
  }
  $names=explode(',',$notify);
  $first=1;
  foreach($names as $name) {
    if ($first) {$addlHeaders.="Cc: $name@wsu.edu"; $first=0;}
      else {$addlHeaders.=",$name@wsu.edu";}
  }
  $addlHeaders.="\r\n";
  //echo "<p>$addlHeaders</p>\n";
  if (in_array($this->formTitle,array('Software Request','Request Software'))) {
    $toEmail='jtangren@wsu.edu'; }
  else {$toEmail='cmayer@wsu.edu'; }
  if (!mail($toEmail,
      'Administrative request needs attention',$outp,$addlHeaders)) {
    echo "<p><strong>Error in notifying those who need to know</strong></p>\n";
  }
  return 1;
}

function updNotify2($vars) {
  if ($addr=$vars['email']) {
    $qVars=array_intersect_key($ar,jt_fill_new($this->colNames,$i));
    $pageName=$this->notifyPage;
    $given='Administrative action on your request';
    $outp="<html>\r\n<head>\r\n<title>".$this->formTitle.
        "</title>\r\n</head>\r\n<body>\r\n";
    $outp.="<p><strong>$given</strong></p>\r\n";
    $outp.="<hr />\r\n";
    $outp.=str_replace("\n","\r\n",$this->displayListing($qVars));
    $outp.="</body>\r\n</html>\r\n";
    $addlHeaders="MIME-Version: 1.0\r\n";
    $addlHeaders.="Content-type: text/html; charset=iso-8859-1\r\n";
  if ($this->formTitle=='Maintenance Request') {
    $addlHeaders.="From: Jerry Moreland <jmoreland@wsu.edu>\r\n";
    $addlHeaders.="Reply-To: jmoreland@wsu.edu\r\n";  
  } else {
    $addlHeaders.="From: Christina Mayer <cmayer@wsu.edu>\r\n";
    $addlHeaders.="Reply-To: cmayer@wsu.edu\r\n";
  }
    if ($names=$this->notify) {
      $names=explode(',',$notify);
      $first=1;
      foreach($names as $name) {
        if ($first) {$addlHeaders.="Cc: $name@wsu.edu"; $first=0;}
          else {$addlHeaders.=",$name@wsu.edu";}
      }
      $addlHeaders.="\r\n";
    }
    if (!mail($addr,$given,$outp,$addlHeaders)) {
      echo "<p><strong>Error in notifying those who need to ",
          "know</strong></p>\n";
    }  
  }
  return 0;
}

function updNotify($vars) {
  echo "<!-- Update notify called; ",$vars['email'],' ',$this->notifyUser,
      " -->\n";
  if ($addr=$vars['email'] and !$this->notifyUser) {
  //if ($addr=$vars['email']) {
    $qVars=array_intersect_key($vars,jt_fill_new($this->colNames,$i));
    $pageName=$this->notifyPage;
    $given='Administrative action on your request';
    $outp="<html>\r\n<head>\r\n<title>".$this->formTitle.
        "</title>\r\n</head>\r\n<body>\r\n";
    $outp.="<p><strong>$given</strong></p>\r\n";
    $outp.="<hr />\r\n";
    $outp.=str_replace("\n","\r\n",$this->displayListing($qVars));
    $outp.="</body>\r\n</html>\r\n";
    $addlHeaders="MIME-Version: 1.0\r\n";
    $addlHeaders.="Content-type: text/html; charset=iso-8859-1\r\n";
  if ($this->formTitle=='Maintenance Request') {
    $addlHeaders.="From: Jerry Moreland <jmoreland@wsu.edu>\r\n";
    $addlHeaders.="Reply-To: jmoreland@wsu.edu\r\n";  
  } else {
    $addlHeaders.="From: Christina Mayer <cmayer@wsu.edu>\r\n";
    $addlHeaders.="Reply-To: cmayer@wsu.edu\r\n";
  }
    if ($ccnames=$this->notifyList) {
      $names=explode(',',$ccnames);
      $first=1;
      foreach($names as $name) {
        if ($first) {$addlHeaders.="Cc: $name@wsu.edu"; $first=0;}
          else {$addlHeaders.=",$name@wsu.edu";}
      }
      $addlHeaders.="\r\n";
    }
    //echo "<PRE>\n$addlHeaders\n</PRE>\n";
    if (!mail($addr,$given,$outp,$addlHeaders)) {
      echo "<p><strong>Error in notifying those who need to ",
          "know</strong></p>\n";
    } else {
      echo "<p>E-mail notification of update sent to $addr</p>\n";
    }
  } elseif (!$this->notifyUser) {
    echo "<p>Requestor's e-mail address not available</p>\n";
  } else {
    echo "<!-- Notify not sent -->\n";
  }
  return 0;
}


function msgOnCreate($ar) {
  echo "<p style=\"color: #090;\"><strong>Your request has been entered</strong></p>\n";
  echo $this->displayListing($ar);
  return 1;
}

function displayListing2($ar) {
  $qVars=array_intersect_key($ar,jt_fill_new($this->colNames,$i));
  $colInfo=$this->getItemInfo();
  $strng="<div id=\"displayListing\">\n";
  //if ($this->formTitle) {echo "<h3>",$this->formTitle,"</h3>\n";}
  //$strng.="<PRE>\n".print_r($qVars)."\n</PRE>\n";
 $strng.="<h3>".$this->formTitle."</h3>\n";
 foreach ($qVars as $k=>$v) {
    $info=$colInfo["$k"];
    if ($info->getOpts('drop')!='yes') {
      if ($info->getSize==98) {
        $xar=explode("\\t",$info->getOpts('values'));
        $v=$xar[$v];
      }
      $v=($v) ? $v : 'n/a' ;
      $strng.="<p><strong>".$info->getLabel()." </strong>= $v</p>\n";
    }
  }
  $strng.="</div>\n";
  return $strng;
}
function displayListing($ar) {
  $qVars=array_intersect_key($ar,jt_fill_new($this->colNames,$i));
  $colInfo=$this->getItemInfo();
  $strng="<div id=\"displayListing\">\n";
 $strng.="<h3>".$this->formTitle."</h3>\n";
 foreach ($qVars as $k=>$v) {
    $info=$colInfo["$k"];
    $strng.=$this->formItemToOutput($info,$k,$v);
  }
  $strng.="</div>\n";
  return $strng;
}

function addIncluded($tabl) {
  $name=$tabl->getTableName();
  $this->includedTablAr["$name"]=$tabl;
  return sizeof($this->includedTablAr);
} 

function includeTables($list) {
    $tables=explode(',',$list);
  foreach($tables as $tabl) {
    $name=trim($tabl);
    if ($tabl) {
      $ti=$this->itemInfo["$name"];
      $g_info=new IncludedTabls($ti,$this->dbName);
      $z=$this->addIncluded($g_info);
    }
  } 
  return $z;
}

function getIncluded($tabl) {
  //echo "<p>Getting $tabl </p>\n";
  return $this->includedTablAr["$tabl"];
} 
function getIncludedList() {
  return array_keys($this->includedTablAr);
}

function updateIncluded($incList,$pid) {
  //echo "<PRE>\n",print_r($incList),"\n</PRE>\n";      
  //echo "<p>Update Included invoked</p>\n";
  foreach($incList as $tabl) {
    $var=extractCheckBoxes("_g$tabl".'_',$_REQUEST);
    //echo "<p>Found ",sizeof($var)," possible entries for $tabl</p>\n";
    $inc=$this->getIncluded($tabl);
    $g_indx=$inc->getIndx();
    //echo "<PRE>\n",print_r($inc),"\n</PRE>\n";      
    $l_indx=$inc->getLookUpIndx();
    $g_data=$inc->getDatName();
    $c_indxName=$this->getIndx();
    $c_indxVal=$this->currentRow["$c_indxName"];
    $l_titl=$inc->getLookUpTitle();
    $l_tabl=$inc->getLookUpTable();
    $g_tabl=$inc->getTableName();
    $sql="SELECT a.$l_indx,a.$l_titl AS glu_titl,b.$g_data,b.$g_indx FROM $l_tabl a ".
      "LEFT OUTER JOIN (SELECT * FROM $g_tabl WHERE $c_indxName=$c_indxVal) b USING ($l_indx) ";
    $l_wiki=$inc->getLookUpWiki();
    if ($l_wiki) {$sql.='WHERE active>0';}

    $rL=$this->xQuery($sql,0);
    while($ar=pg_fetch_assoc($rL)) { 
      $indx=$ar["$l_indx"];
      if ($var["$indx"]!=$ar["$g_data"]) {
        if (!$var["$indx"]) {$inc->deleteObs($ar["$g_indx"]);
          //echo "<p>Delete ",$ar["$g_indx"],"</p>\n";
          }
          elseif (!$ar["$g_data"]) {
            $inc->createObs(array($g_data=>$var["$indx"],
              $l_indx=>$indx,
              $this->indx=>$pid));
            //echo "<p>Create ",$g_data,'=',$var["$indx"],' ',
              //$l_indx,'=',$indx,
              //$this->indx,'=',$pid,"</p>\n";
          }
          else {$inc->updateObs($ar["$g_indx"],array($g_data=>$var["$indx"]));
            //echo "<p>Update ",$ar["$g_indx"],' ',$g_data,'=',$var["$indx"],"</p>\n";
            }
      } // if 
    } //while
  } //foreach 
} //updateIncluded

function createIncluded($incList,$pid) {
  //echo "<PRE>\n",print_r($incList),"\n</PRE>\n";      
  //echo "<p>Create Included invoked</p>\n";
  foreach($incList as $tabl) {
    $var=extractCheckBoxes("_g$tabl".'_',$_REQUEST);
    //echo "<p>Found ",sizeof($var)," possible entries for $tabl</p>\n";
    $inc=$this->getIncluded($tabl);
    //echo "<PRE>\n",print_r($inc),"\n</PRE>\n";      
    foreach($var as $k=>$v) {
      if ($v) {
        $inp=array($this->getIndx()=>$pid,
          $inc->getLookUpIndx()=>$k,
          $inc->getDatName()=>$v);
          $inc->createObs($inp);   
          //echo "<PRE>\n",print_r($inp),"\n</PRE>\n";      
      } //v
    } //foreach
  } //foreach
} //createIncluded


function createObs($var) {
  $qVars=array_intersect_key($var,jt_fill_new($this->colNames,$i));
  echo "<p>Creating...</p>\n";
  //echo "<p>Other: indx=",$this->indx,"; table=",$this->tabl,"</p>\n";
    if ($this->indx) {
      $pid=Pages6::createObs($qVars);
      if ($incList=$this->getIncludedList()) {
        $this->createIncluded($incList,$pid);
      }
      return $pid;
    } 
   else { return 0;}
}

function updateObs($pid,$vars) {
  $qVars=array_intersect_key($vars,jt_fill_new($this->colNames,$i));
  if ($this->indx) {
    $pid=Pages6::updateObs($pid,$qVars);
    if ($incList=$this->getIncludedList()) {
      $this->updateIncluded($incList,$pid);
    }
    return $pid; 
  } else { return 0;}
}

function getRIndx($gofor,$indexName) {
  $rI=($gofor) ? $_REQUEST["$indexName"] : URLParts(1);
  //echo "<p>Found index of $rI</p>\n";
  if (is_numeric($rI)) {return $rI;}
    else {return 0;}
}

function submitListing($thisFileName,$varOrder,$notify) {
  //this routine should have two modes: prompt and save
  $gofor=($_REQUEST['gofor']) ? $_REQUEST['gofor'] : 'Begin' ;
  $indx=$this->getIndx();
  $idName=$this->getDisplayText();
  $colInfo=$this->getItemInfo();
  $arOrder=explode(',',$varOrder);
  $hiddenVars=array();
  //echo "<p>Notify list=$notify</p>\n";
  $rIndx=-199;
  if ($gofor) {

    if ($gofor=='Submit') {
      $varlist=array_diff(array_keys($colInfo),array($indx));
      $vars=arrayFromInput($varlist,'1','');
      if ($this->arTextFlds) {$this->removeParaMarks($vars);}
      //echo "<p>Beginning to process...</p>\n";
      if (!$this->validateInput($vars)) {
        echo "<p>Input did not validate</p>\n";
      } elseif (($ckVar=$this->checkDuplicate()) and 
        ($gofor=='Submit') and 
        $cIndx=getValueFromDB($this->tabl,$this->indx,
            array($ckVar=>$vars["$ckVar"]))) {
          echo "<p><strong>Warning ($cIndx): $ckVar = ",$vars["$ckVar"],
            " exists in database</strong></p>\n";
          echo "<p><strong>Continue</strong> to add as an ",
            "additional entry with or without further changes</p>\n";
          $disp='Continue';
          $ar=$vars;
          $hiddenVars['indx']=$cIndx;
       } else {
         $rIndx=$this->createObs($vars);
         if ($notify) {$this->notify($notify,$rIndx,$vars);}
         echo $this->msgOnCreate($vars);
       }
    } 
    if ($gofor=='Begin') {
      $disp='Submit';
      $disp2='';
      echo "<p>&nbsp;</p>\n";
      $this->cmsForm('',$this->formid,$thisFileName,'POST','','',$hiddenVars);
      $this->fillFields();
      
      if ($varOrder) {
        foreach($arOrder as $key) {
          if ($key!=$indx) {
            $val=$colInfo["$key"];
            echo $this->formItem($val,$key,'');
          } //!$indx
        } //$cols      
      } else { 
        foreach($colInfo as $key => $val ) {
          if ($key!=$indx) {
            echo $this->formItem($val,$key,'');
          } //!$indx
        } //$cols
      }
      echo "<p>&nbsp;</p>\n";
      closeForm($disp,'Reset',$disp2);
    }
  }
  return $rIndx;
}


function manageListing($thisFileName,$idAr,$varOrder) {
  $gofor=$_REQUEST['gofor'];
  if ($gofor=='Submit') {$gofor='Insert';}
  $indx=$this->getIndx();
  $idName=$this->getDisplayText();
  $colInfo=$this->getItemInfo();
  $rIndx=$this->getRIndx($gofor,'indx');
  if (!$gofor and $rIndx) {$gofor='Edit';}
  $hiddenVars=($idAr) ? $idAr : array();
  if ($rIndx) {
    $ar=$this->getDbRow($rIndx);
    $varName=$this->getDisplayText();
    echo "<p><strong>Editing data for ",$ar["$varName"],"</strong></p>\n";
    $hiddenVars['indx']=$rIndx;
  } else {
    //$hiddenVars=array();
  }
  //echo "<p>Looking for gofor=*$gofor* with index=*$rIndx*; part=",
    //URLParts(1),"</p>\n";
  if ($gofor) {
    if ($gofor=='Delete?') {
      $this->cmsForm('Delete '.$ar["$idName"].' (are you sure)','',$thisFileName,'POST','1',
        'OK, Delete',$hiddenVars);
      $this->cmsForm('NO ','',$thisFileName,'POST','1',
        'Cancel','');
    } else if ($gofor=='OK, Delete') {
      $nid=$this->deleteObs($rIndx);
      $ar=array();
      $rIndx=0;
    } else if ($gofor=='Update' OR $gofor=='Insert' OR $gofor=='Continue' 
        OR $gofor=='Replace' OR $gofor=='Include' OR $gofor=='Duplicate') {
      $varlist=array_diff(array_keys($colInfo),array($indx));
      $vars=arrayFromInput($varlist,'1','');
      if ($this->arTextFlds) {$this->removeParaMarks($vars);}
      if (($ckVar=$this->checkDuplicate()) and 
        ($gofor=='Insert' OR $gofor=='Duplicate') and 
        $cIndx=getValueFromDB($this->tabl,$this->indx,array($ckVar=>$vars["$ckVar"]))) {
          echo "<p><strong>Warning ($cIndx): $ckVar = ",$vars["$ckVar"],
            " exists in database</strong></p>\n";
          echo "<p>Choose <strong>Replace</strong> to Overwrite entry<br />",
            "or <strong>Continue</strong> to add as an ",
            "additional entry with or without further changes</p>\n";
          $disp='Continue';
          $ar=$vars;
          $hiddenVars['indx']=$cIndx;
       } elseif ($gofor=='Include') {
         $ar=$vars;
       } else {
        if ($gofor=='Update' OR $gofor=='Replace')   
          {$nid=$this->updateObs($rIndx,$vars);
           echo "<!-- Ready to notify -->\n";
           $this->updNotify($vars);}
        elseif ($gofor=='Insert' OR $gofor=='Continue' OR $gofor=='Duplicate') 
          {$rIndx=$this->createObs($vars); $hiddenVars['indx']=$rIndx;}
        $ar=$this->getDbRow($rIndx);
      }
    } 
    if ($gofor=='Edit' OR $gofor=='Update' OR $gofor=='Insert' 
        OR $gofor=='Add' OR $gofor=='Continue' OR $gofor=='Replace' OR $gofor=='Include' OR $gofor=='Duplicate') {
      $disp2='';
      if ($gofor=='Add' OR $gofor=='Include') {$disp='Insert';
          $this->upLoadButton();} 
        elseif ($disp=='Continue') {$disp2='Replace';}
        else {$disp='Update'; $disp2='Duplicate';
      $this->cmsForm('Delete this entry','',$thisFileName,'POST','1','Delete?',$hiddenVars);}
      echo "<p>&nbsp;</p>\n";
      echo $this->closerButton($rIndx);
      $this->cmsForm('',$this->formid,$thisFileName,'POST','','',$hiddenVars);
      //echo "<p>Checkpoint</p>\n";
      //echo "<p>There are ",sizeof($colInfo)," items</p>\n";
      $this->fillFields();
      foreach($colInfo as $key => $val ) {
        if ($key!=$indx) {
          echo $this->formItem($val,$key,$ar["$key"]);
        } //!$indx
      } //$cols
      echo "<p>&nbsp;</p>\n";
      closeForm($disp,'Reset',$disp2);
    }
  
    echo "<hr />\n";
    $strng="Edit another entry";
  } else {
    $strng="Edit this entry";
  }
  $hiddenVars=($idAr) ? $idAr : array();
  $this->cmsForm('','editThisEntry',$thisFileName,'POST','','',$hiddenVars);
  echo "<p><strong>$strng</strong></p>\n";
  if ($this->xQueryInfo) {$this->extendedBrowse();}
  $rowList=$this->getRowList();
  
  echo "<p><strong>View list as </strong>";
  radioInputWScript('pullTabl','pull',1,
      "togglePullTabl('viewAsPopup','viewAsTable')"); echo "Pull-down\n";
  radioInputWScript('pullTabl','tabl',0,
      "togglePullTabl('viewAsTable','viewAsPopup')"); echo "Table</p>\n";
  echo "<div id=\"editList\">\n";
  
  echo "<p id=\"viewAsPopup\">";
  //selectFromArray('indx',array_keys($rowList),'',
  //    array_values($rowList),'--','','');
  selectFromArray('indx',array_keys($rowList),'',
    cImplode(':',array_values($rowList),30,5,'array'),'--','','');
  echo "</p>\n";
  
  echo "<table id=\"viewAsTable\" style=\"display: none\" ".
      "border=\"1\" width=\"100%\">\n";
  $i=1;
  echo "<tr><td>"; addGofor('Edit'); echo "</td></tr>\n";
  foreach ($rowList AS $k => $rLine) {
    $tar=explode(':',$rLine);
    echo "<tr>";
    echo "<td><input type=\"radio\" name=\"alt_indx\" ",
        "onClick=\"tookie($i,'indx','editThisEntry');\" /></td>";
    foreach ($tar AS $rItem) {echo "<td>$rItem</td>";}      
    echo "</tr>\n";
    $i++;
  }
  echo "</table>\n";
  
  echo "</div>\n";
  echo "<p>";
  //hiddenInput('gofor','Edit');
  closeForm('Edit','','');
  pg_free_result($r);
  echo "<p><strong>OR</strong></p>\n";
  $this->cmsForm('Add an entry','',$thisFileName,'POST','1','Add',$hiddenVars);
  return 1;
}

function extendedBrowse() {
  $this->vList();
  $display=($this->displayQuery) ? 
      str_replace("||':'||",',',$this->displayQuery) : $this->displayText;
  $js="method=updEditList&db=".$this->dbName."&tabl=".$this->tabl.
      "&index=".$this->indx."&display=$display";
  echo "<input type=\"button\" id=\"updBrowse\" value=\"Update Edit List\" ".
      "onClick=\"jtUpdEditList('$js')\" style=\"visibility: hidden;\" /> ";
  //addButton('updBrowse','Update Edit List',"jtUpdEditList('$js')");
  echo "<div id=\"jtWork\" ></div>\n";
  echo "<div id=\"jtSpecs\" ></div>\n";
  
  echo "<p><strong>Reset browsing filters</strong>\n";
  echo "<input type=\"button\" value=\"Reset\" ",
      "onClick=\"document.location.reload();\"></p>\n";

  return 1;
}

function extendedBrowseAlt() {
  $this->vList();
  echo "<span id=\"updBrowse\">&nbsp;</span>\n";
  echo "<div id=\"jtWork\" ></div>\n";
  echo "<div id=\"jtSpecs\" ></div>\n";
    echo "<p><strong>Reset browsing filters</strong>\n";
  echo "<input type=\"button\" value=\"Reset\" ",
      "onClick=\"document.location.reload();\"></p>\n";
  return 1;
}

function createExportQuery($allVars,$thisFileName) {
  echo "<p>Creating query...</p>\n";
  reset($_REQUEST);
  $iMax=0;
  $whereClause='';
  $chosenOnes=array();
  foreach($_REQUEST AS $varX=>$valX) {
    if (strpos($varX,'VarX')!==false) {
      $i=substr($varX,4);
      $iMax=max($iMax,$i);
      if ($whereClause) {$whereClause.=$_REQUEST['BoolX'.$i];}
        $whereClause.=' '.$valX."='".$_REQUEST['SelX'.$i]."' ";
        //$whereClause.=' ('.fillClause($valX,$_REQUEST['SelX'.$i]).') ';
    } elseif (strpos($varX,'FLD')!==false) {
      $chosenOnes[]=$valX;
    }
  }
  $chosenString=($_REQUEST['allvars']=='ALL') ? implode(',',$allVars) : 
      implode(',',$chosenOnes);
  
  if ($whereClause) {
    echo "<p>$whereClause</p>\n";
    $sql="SELECT $chosenString FROM grants WHERE active>0 AND ($whereClause) ";
    //echo "<p>$sql</p>\n";
  } else {
    echo "<p><strong style=\"color: #090;\">Note: all records will be used in query</strong></p>\n";
    $sql="SELECT $chosenString FROM grants WHERE active>0";
  }

  if ($sql) {
     echo "<p><strong>Query generated:</strong></p>\n";
     //echo "<p>$sql</p>\n";
     echo "<p><strong>Display in new window as HTML or download XLS</strong>\n";
     echo "<form action=\"http://www.tfrec.wsu.edu/QinWindow.php\" ",
       "method=\"POST\" target=\"_exportDB\" >";
     hiddenInput('caller',$thisFileName);
     hiddenInput('varlist',$chosenString);
     hiddenInput('where',($whereClause) ? 'WHERE '.$whereClause : '');
     closeForm('HTML','','XLS');
  } 
} // createExportQuery

function exportListing($thisFileName,$idAr,$varOrder) {
  //echo "<p>Coming soon...</p>\n";
  $qVars=array_intersect_key($this->getItemInfo(),
      jt_fill_new($this->colNames,1));
  if ($_REQUEST['gofor']=='Export') 
      {$this->createExportQuery(array_keys($qVars),$thisFileName);}
  echo "<hr />\n";
  $this->cmsForm('',$this->formid,$thisFileName,'POST','','','');
  $one=$this->extendedBrowseAlt();
  echo "<p>";
  //checkboxInput('allvars','ALL','');
  echo "<input type=\"checkbox\" name=\"allvars\" value=\"ALL\" checked=\"checked\" ",
      "onClick=\"manageCheckBoxes('z_allvars',allCheckBoxes,'z_allvars');\" ",
      "id=\"z_allvars\">\n";
  echo "<strong>All variables</strong></p>\n";
  echo "<table border=\"0\">\n<tr valign=\"top\"><td><strong>\n";
  $colInfo=$this->getItemInfo();
  $qz=sizeof($qVars);
  $half=(integer) $qz/2; 
  $boxAr=array(); 
  foreach ($qVars as $k=>$du){
    //checkboxInput('FLD'.$i,$k,'');
    $fld='FLD'.$i;
    echo "<input type=\"checkbox\" name=\"$fld\" value=\"$k\" ",
      "onClick=\"manageCheckBoxes('z_$fld',allCheckBoxes,'z_allvars');\" ",
      "id=\"z_$fld\">\n";
    $boxAr[]='z_'.$fld;
    $v=$colInfo["$k"];
    echo $v->getLabel(),"<br />\n";
    $i++;
    if ($i>$half) { echo "</strong></td><td><strong>"; $half=$qz;}
  }
  echo "</strong></td></tr></table>\n";
  echo "<script>",createJSarray('allCheckBoxes',$boxAr),"</script>\n";
  closeForm('Export','','');

  return 1;
}

function updateBuildInfo($name,$sz,$unused,$title,$display,$opts) {
  $this->itemInfo["$name"]=
      new TableItem($name,$sz,$unused,$title,$display,$opts);
  if ($sz>600 and $sz<700) {$this->arTextFlds[]=$name;}
}

};

function listingSetup($connection,$ar_setup,$ar_subset) {
   return new Listing6($connection,$ar_setup['tabl'],$ar_setup['indx'],
       $ar_subset);
}

//echo "loaded</p>\n";
?>