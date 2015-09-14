<?php
class DBConnManager {
  protected $ar_conn;
  protected $ar_name;
function __construct () {
  $this->ar_name=array();
  $this->ar_conn=array();
}
function getConn($name,$rw) {
  if (!in_array($name,$this->ar_name)) {
    $conn=($rw) ? pg_connect("dbname=$name user=dbguest password=".obtain()) :
        pg_connect("dbname=$name");
    if ($conn) {
      $this->ar_conn["$name"]=$conn;
      $this->ar_name[]=$name;
      return $conn;
    } else {
      return 0;
    }
  } else {
    return $this->ar_conn["$name"];
  }
} //getConn
function killConn($name) {
  pg_close($this->ar_conn["$name"]);
  $this->ar_conn=array_diff_key($this->ar_conn,array($name=>'0'));
  $this->ar_name=array_diff($this->ar_name,array($name));
  return 0;
}
function killAll() {
  foreach ($this->ar_conn as $conn) {pg_close($conn);}
  $this->ar_conn=array();
  $this->ar_name=array();
  return 0;
}
};

function jt_fill_new($ar,$i) {
  return array_combine($ar,array_fill(0,sizeof($ar),$i));
}

function fetchDBArray($r) {
  return pg_fetch_assoc($r);
}

function killResult($r) {
  return pg_free_result($r);
}

class Pages6 {
  protected $tabl;
  protected $colNames;
  protected $indx;
  protected $useWiki;
  protected $dbConn;
  protected $dbName;
  
function __construct($tabl,$indx,$useWiki,$dbName) {
   global $allDBConns;
   $this->dbName=$dbName;
   if (!$allDBConns) {$allDBConns=new DBConnManager();}
   $this->dbConn=$allDBConns->getConn($this->dbName);
   $this->tabl=$tabl;
   $r=$this->xQuery("SELECT c.column_name FROM information_schema.columns c ".
       "WHERE c.table_name='$tabl';",0);
   $this->colNames=pg_fetch_all_columns($r,0);
   if ($indx AND !in_array($indx,$this->colNames)) {$indx='';}
   $this->indx=$indx;
   $this->useWiki=$useWiki;
   if ($useWiki) {
     $this->colNames=array_diff($this->colNames,array('obid','active','lastdate'));
   }
}

function getDBName() {
  return $this->dbName;
}

function xQuery($SQLstmt,$display) {
  global $recentNRows;
  if ($display==1) {echo "<!-- $SQLstmt -->\n";}
    elseif ($display>1) {echo "<p> $SQLstmt </p>\n";}
  if ($display<3) {
    $result =  pg_query($this->dbConn,$SQLstmt);
    if (!$result) {
      echo "<p>Execution failed on database ".$this->dbName."!<br>";
      if ($display<2) {echo $SQLstmt."<br />\n";}
      echo pg_errormessage($this->dbConn); exit;
    }
    $recentNRows=pg_num_rows($result);
    return $result;
  } else {
    $recentNRows=0;
    return 0;
  }
} /*end xQuery*/

function getColNames() {
  return $this->colNames;
}

function getIndx() {
  return $this->indx;
}

function getNextIndx($col) {
  $SQLstmt="SELECT max($col) as maxid FROM ".$this->tabl.';';
  $r=$this->xQuery($SQLstmt,0);
  $maxid=pg_result($r,0,'maxid')+1;
  pg_free_result($r);
  return $maxid;
}

function getUseWiki() {
  return $this->useWiki;
}

function createObs($vars) {
  if ($this->indx) {
    $pid=$this->getNextIndx($this->indx);
    $vNames='';
    $vValues='';
    while (list($k,$v)=each($vars)) {
      $vNames.=",$k";
      $x=trim($v);
      if ($x) {$vValues.=",'$x'";}
        else {$vValues.=",NULL";}
    }
    if ($this->useWiki) {
      $obid=$this->getNextIndx('obid');
      $SQL="INSERT INTO ".$this->tabl." (".
        $this->indx.",obid,active,lastdate"."$vNames) VALUES (".
        "$pid,$obid,1,'".date('Y-m-d')."'"."$vValues);";
      } else {
      $SQL="INSERT INTO ".$this->tabl." (".
        $this->indx."$vNames) VALUES ($pid"."$vValues);";      
      }
    $r=$this->xQuery($SQL,1);
    pg_free_result($r);
    return $pid;
    } 
  else { return 0;}
}

function updateObs($pid,$vars) {
  if ($this->indx) {
    if ($this->useWiki) {
      $ar_id=array('obid','active','lastdate');
      $ar_id[]=$this->indx;
      $varList=implode(',',array_diff($this->colNames,$ar_id));
      $SQL="SELECT $varList FROM ".$this->tabl." WHERE ".
        $this->indx."=$pid AND active>0;";
      $r=$this->xQuery($SQL,1);
      $ar=pg_fetch_assoc($r);
      pg_free_result($r);
      while (list($k,$v)=each($vars)) {
        $ar["$k"]=$v;
      }
      reset($ar);  
      $vNames='';
      $vValues='';
      $vList=array_keys($vars);
      while (list($k,$v)=each($ar)) {
        $vNames.=",$k";
        $x=trim($v);
        if ($x) {
          if (in_array($k,$vList)) {$vValues.=",'$x'";}
            else {$vValues.=",'".pg_escape_string($x)."'";}
        } else {
          $vValues.=",NULL";
        }
      }
      $SQL="UPDATE ".$this->tabl." SET active=0 WHERE ".
        $this->indx."=$pid AND active>0;";
      $r=$this->xQuery($SQL,1);
      pg_free_result($r);
      $obid=$this->getNextIndx('obid');
      $SQL="INSERT INTO ".$this->tabl." (".
        $this->indx.",obid,active,lastdate"."$vNames) VALUES (".
        "$pid,$obid,1,'".date('Y-m-d')."'"."$vValues);";
      } else {
        $SQL="UPDATE ".$this->tabl." SET ";
        reset($vars);
        list($k,$v)=each($vars);
        while ($k) {
          if ($v) {$SQL.="$k='$v'";}
            else {$SQL.="$k=NULL";}
          //$SQL.="$k='$v'";
          list($k,$v)=each($vars);
          if ($k) {$SQL.=',';}
        }
        $SQL.=" WHERE ".$this->indx."=$pid;";
      }
    $r=$this->xQuery($SQL,1);
    pg_free_result($r);
    return $pid; 
    } 
  else { return 0;}
}

function copyObs($pid,$subst) {
  if ($this->indx) {
    $ar_id[]=$this->indx;
    $varList=implode(',',array_diff($this->colNames,$ar_id,array_keys($subst)));
    //echo "<p>DEBUG: varList=$varList</p>\n";
    //echo "<p>DEBUG: colNames=".implode(',',$this->colNames)."</p>\n";
    //echo "<p>DEBUG: ar_id=".$this->indx."</p>\n";
    if ($this->useWiki) {
      $SQL="SELECT $varList FROM ".$this->tabl." WHERE ".
        $this->indx."=$pid AND active>0;";
    } else {
      $SQL="SELECT $varList FROM ".$this->tabl." WHERE ".
        $this->indx."=$pid;";
    }
      $r=$this->xQuery($SQL,1);
      $ar=pg_fetch_assoc($r);
      pg_free_result($r);
      // subst variables here
      reset($ar);
      while (list($k,$v)=each($ar)) {
        $ar["$k"]=pg_escape_string($v);
      }
      $ar=array_merge($ar,$subst);
    return $this->createObs($ar);
    } 
  else { return 0;}
}

function deleteObs($pid) {
  if ($this->indx) {
    if ($this->useWiki) {
      $SQL="UPDATE ".$this->tabl." SET active=0 WHERE ".
        $this->indx."=$pid AND active>0;";
    } else {
      $SQL="DELETE FROM ".$this->tabl." WHERE ".
        $this->indx."=$pid;";
    }
      $r=$this->xQuery($SQL,1);
      pg_free_result($r);
    return $pid;
  } 
  else { return 0;}
}

function lookupValue($var,$qValues) {
  if ($this->useWiki) {$qValues['active']=1;}
  $r=$this->xQuery("SELECT $var FROM ".$this->tabl." WHERE ".where_list($qValues).";",0);
  $x=pg_result($r,0,$var);
  pg_free_result($r);
  return $x;
}

function getValueFromDB($tabl,$var,$qValues) {
  $r=$this->xQuery("SELECT $var FROM $tabl WHERE ".where_list($qValues).";",0);
  $x=pg_result($r,0,$var);
  pg_free_result($r);
  return $x;
}

function getValueFromDB_SQL($SQL) {
  $r=$this->xQuery($SQL,1);
  $x=pg_fetch_result($r,0,0);
  pg_free_result($r);
  return $x;
}

};

class ExPages6 extends Pages6 {

protected $itemInfo;
protected $ar_subset;
protected $currentRow;

function __construct($ar_subset) {
   $this->dbConn=$this->getDatabaseConn();
   $tabl=$this->getTableName();
   $this->tabl=$tabl;
   $indx=$this->getIndx();
   $r=$this->xQuery("SELECT c.column_name FROM information_schema.columns c ".
       "WHERE c.table_name='$tabl';",0);
   $this->colNames=pg_fetch_all_columns($r,0);
   if ($indx AND !in_array($indx,$this->colNames)) {$indx='';}
   //if (!$indx) {echo "<!-- Index (",$this->getIndx(),") not found -->\n";
   //  echo "<!-- "; print_r($this->colNames);echo " -->\n";}
   $this->indx=$indx;
   if (in_array('obid',$this->colNames) and in_array('active',$this->colNames)
       and in_array('lastdate',$this->colNames)) {$wiki=1;}
     else {$wiki=0;}
   $this->useWiki=$wiki;
   if ($this->useWiki) {
     $this->colNames=array_diff($this->colNames,array('obid','active','lastdate'));
   }
   $this->ar_subset=$ar_subset;
   $this->itemInfo=$this->buildInfo();
}

function getDatabaseConn() {
  global $conn;
  return $conn;
}

function buildInfo() { 
  $arr=array();
  foreach ($this->getColNames() as $name) {
    if ($name==$this->getIndx()) {
      $arr["$name"]=new TableItem($name,'0','','Row ID',0,'');
    }
    else {
      $arr["$name"]=new TableItem($name,'30','',$name,0,'');
    }
  }
  return $arr; 
  //return 1;
}

function setBuildInfo($arTableItems) {
  $this->itemInfo=$arTableItems;
  return 1;
}

function getIndx() {
  return 0;
}

function getDisplayText() {
  return 0;
}

function checkDuplicate() {
  return 0;
}

function validateInput($ar) {
  return 1;
}

function getTableName() {
  return $this->tabl;
}

function getItemInfo() {
  return $this->itemInfo;
}

function getSingleItem($name) {
  return $this->itemInfo["$name"];
}

function manyToOne($ti,$k,$val) {
  return '';
}

function getSubsetArray() {
  return $this->ar_subset;
}

function sqlSubset() {
  return where_list($this->ar_subset);
}

function getDbRow($indx) {
  if ($this->useWiki) {$useAct=' AND active>0 ;';} else {$useAct=';';}
  $rX=$this->xQuery("SELECT * FROM ".$this->tabl." WHERE ".$this->indx."='$indx' $useAct",0);
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
  $r=$this->xQuery("SELECT ".$this->indx.",".$this->getDisplayText()." FROM ".
    $this->tabl." $useAct ORDER BY ".$this->getDisplayText().";",1);
  $ids=pg_fetch_all_columns($r,0);
  $names=pg_fetch_all_columns($r,1);
  pg_free_result($r);
  return array_combine($ids,$names);
}

function formItem($ti,$k,$val) {
  $changeJS=($s=$ti->getOpts('onchange')) ? "onChange=\"$s;\"" : '';
  $clickJS=($s=$ti->getOpts('onclick')) ? "onClick=\"$s;\"" : '';
  $css=($s=$ti->getOpts('css')) ? "style=\"".str_replace(':,',';',$s)."\"" : '';
  $class=($s=$ti->getOpts('class')) ? "class=\"$s\"" : '';
  $promptId=($s=$ti->getOpts('promptid')) ? "id=\"$s\"" : '';
  $whereActive=($this->useWiki AND !$ti->getOpts('diswiki')) ? ' AND active>0 ' : '';
  
  if ($ti->getOpts('currentdate')) {$val=date('n/j/Y');}
  if ($s=$ti->getOpts('default') and $val=='') {$val=$s;}
  if ($urlPart=$ti->getOpts('urlpart')) {$val=URLParts($urlPart);}
  
  if ($ti->getOpts('killzero') AND !($val)) {
    $strng='';
  }
    
  elseif (!$ti->getSize()) { //hidden w editor option
    if ($k=='editor' and !$val) {$val='99';}
    $strng="<input type=\"hidden\" name=\"$k\" id=\"z_$k\" value=\"$val\" />\n";   
    if ($ti->getOpts('showhidden')) {
      $tx=($zF=$ti->getOpts('formatdate') and $val) ? date($zF,strtotime($val)) : $val;
      $strng.="<strong>".$ti->getLabel()." = $tx</strong>\n";
    }
    
  } elseif ($ti->getSize()<0) {//ignore
    $strng='';
    
  } elseif ($ti->getSize()<2) { //display value
    $tx=($zF=$ti->getOpts('formatdate') and $val) ? date($zF,strtotime($val)) : $val;
    $strng="<strong>".$ti->getLabel()." = $tx</strong>\n";
    
  } elseif ($ti->getSize()<3) { //display label
    $strng="<strong>".$ti->getLabel()."</strong>\n";
    
  } else if ($ti->getSize()<90) { //text box
    $tx=($zF=$ti->getOpts('formatdate') and $val) ? date($zF,strtotime($val)) : $val;
    $strng="<strong>".$ti->getLabel()."</strong>\n<input type=\"text\" ".
      "name=\"$k\" id=\"z_$k\" value=\"$tx\" ".$class.
      " size=\"".$ti->getSize()."\" $changeJS />\n";
      
  } else if ($ti->getSize()<100) { //select with list
      $strng="<strong>".$ti->getLabel()."</strong>\n<select name=\"$k\" id=\"z_$k\" $changeJS ".
          "$clickJS >\n";
      if (!$ti->getOpts('nopt')) {$strng.="<option value=\"0\">--</option>\n";}
      $cValues=str_replace('\t',"\t",$ti->getOpts('values'));
      $values=explode("\t",$cValues);
      $i=0;
      $optFound=0;
      foreach($values as $option) {
        $i++;
        if ($ti->getSize()<99) { $x="$i"; }
          else { $x=$option;}          
        $strng.="<option value=\"$x\" ";
        if ($x==$val) {$strng.="selected=\"selected\" "; $optFound=1;}
        $strng.=">$option</option>\n";
      }
      $strng.="</select>\n";
      
  } else if ($ti->getSize()<200) { //select displayText
      $strng="<strong>".$ti->getLabel()."</strong>\n<select name=\"$k\" id=\"z_$k\" $changeJS ".
        "$clickJS >\n";
      $strng.="<option value=\"0\">--</option>\n";
       $indx=$this->getIndx();
     $r=$this->xQuery("SELECT $indx,".$this->getDisplayText().' FROM '.
        $this->getTableName()." ORDER BY ".$this->getDisplayText().';',0);
      $ar=pg_fetch_assoc($r);
      $displayVars=explode(',',$this->getDisplayText());
      while ($ar) {
        $strng.="<option value=\"".$ar["$indx"]."\" ";
        if ($ar["$indx"]==$val) {$strng.="selected=\"selected\" ";}
        $option=''; $first=1;
        foreach($displayVars as $displayVar) {
          if ($first) {$first=0;}
            else {$option.='/';}
          $option.=$ar["$displayVar"];
        }
        $strng.=">$option</option>\n";
        $ar=pg_fetch_assoc($r);
      }
      $strng.="</select>\n";
      
  } else if ($ti->getSize()<300) { // textarea no TinyMCE
    $rows=$ti->getSize()-200;
    $strng="<strong>".$ti->getLabel()."</strong></p>\n";
    $strng.="<textarea name=\"$k\" id=\"z_$k\" cols=\"60\" rows=\"$rows\">";
    $strng.=$val;
    $strng.="</textarea>\n<p>&nbsp;";  

 } else if ($ti->getSize()<400) { //select with sql specified
     $strng="<strong>".$ti->getLabel()."</strong>\n";
     $qStrng=$ti->getOpts('sql');
     if ($subVarName=$ti->getOpts('subvarname')) {
       $uStrng=$this->currentRow["$subVarName"];
       $strng.="\n<!-- subvarname = $subVarName ($uStrng) -->\n";
       $qStrng=str_replace('VALUE',$uStrng,$qStrng);
     }
     $r=$this->xQuery($qStrng,1);
     $strng.="<select name=\"$k\" id=\"z_$k\" $changeJS>\n";
     $ar=pg_fetch_assoc($r);
     $optName=$ti->getOpts('optname');
     $optValue=$ti->getOpts('optvalue');
     $strng.="<option value=\"0\">--</option>\n";
     while ($ar) {
        //$strng.="<!-- $val = ".$ar["$optValue"].": ".$ar["$optName"]." -->\n";
        $strng.="<option value=\"".$ar["$optValue"]."\" ";
        if ($ar["$optValue"]==$val) {$strng.="selected=\"selected\" ";}
        $strng.=">".$ar["$optName"]."</option>\n";
        $ar=pg_fetch_assoc($r);
     }
     $strng.="</select>\n";
     pg_free_result($r);

 } elseif ($ti->getSize()<410) { //text with date format

   if ($val) {$dt=date('m/d/Y',strtotime($val));}
     else {$dt=date('m/d/Y');}
    $strng="<strong>".$ti->getLabel()."</strong>\n<input type=\"text\" ".
      "name=\"$k\" id=\"z_$k\" value=\"$dt\" ".
      "size=\"12\" />\n";   
      
 } elseif ($ti->getSize()<450) { //radio buttons
 
   $strng="<strong>".$ti->getLabel()."\n";
   $cRadio=str_replace('\t',"\t",$ti->getOpts('radio'));
   $cValues=str_replace('\t',"\t",$ti->getOpts('values'));
   $brkLine=($ti->getOpts('break')) ? $ti->getOpts('break') : 0;
   $prompts=explode("\t",$cRadio);
   $values=explode("\t",$cValues);
   $z=sizeof($prompts);
   $optFound=0;
   for ($ict=0;$ict<$z;$ict++) {
    if ($brkLine and $ict>0) {$strng.='<br />';}
     $strng.="<input type=\"radio\" name=\"$k\" id=\"z_$k\" value=\"";
     $strng.=$values[$ict];
     $strng.="\"";
     if ($values[$ict]==$val) {$strng.=" checked=\"checked\" "; $optFound=1;}
     $strng.=" $clickJS />".$prompts[$ict]."\n";
   }
   $strng.="</strong>\n";
   
 } elseif ($ti->getSize()<470) { // hidden subset
 
   $strng="<input type=\"hidden\" name=\"$k\" id=\"z_$k\" value=\"";
   $ar_p=$this->getSubsetArray();
   $strng.=$ar_p["$k"];
   $strng.="\" />\n";
   
  } else if ($ti->getSize()<600) { //upload button
    $ar_p=$this->getSubsetArray();
    $thisRefid=$ar_p['refid'];
    $callUpload="createWindow(".
      "'http://jenny.tfrec.wsu.edu/cms3/pdfUpld4.php?refid=$thisRefid".
      "&fldname=z_$k',".
      "'upld','');";
    $wd=$ti->getSize()-500;
    $strng="<strong>".$ti->getLabel()."</strong><input type=\"text\" ".
      "name=\"$k\" id=\"z_$k\" value=\"$val\" ".
      "size=\"$wd\" />\n<input type=\"button\" name=\"Upload\" ".
      "value=\"Get URL from Database\" onClick=\"$callUpload\" />\n\n";
      
 } else if ($ti->getSize()<700) { //TinyMCE textarea
 
    $rows=$ti->getSize()-600;
    $strng="<p><strong>".$ti->getLabel()."</strong></p>\n";
    $strng.="<textarea name=\"$k\" id=\"z_$k\" class=\"captionEd\" ";
    $strng.="cols=\"60\" rows=\"$rows\">";
    $strng.=$val;
    $strng.="</textarea>\n<p>&nbsp;</p>\n"; 
    
} else if ($ti->getSize()<720) { //formulated many to one

   $strng="<p id=\"mo_$k\"><strong>".$ti->getLabel()."</strong><br />\n";
   //$strng.="<span id=\"li_$k\">";
   $strng.="<input type=\"hidden\" name=\"$k\" value=\"$val\" id=\"z_$k\" />\n";
   $mo_tabl=$ti->getOpts('tabl');
   $mo_id=$ti->getOpts('id');
   $mo_value=$ti->getOpts('value');
   $mo_coded=($ti->getOpts('coded')) ? " AND ".$ti->getOpts('coded')."='$k' " : '' ;
   
   $jsParms="'$k','$mo_id','$mo_tabl','$mo_value','";
   if ($ti->getOpts('coded')) {$jsParms.=$ti->getOpts('coded');}
   $jsParms.="','";
   $jsParms.=$this->getDBName()."','".$ti->getLabel()."'";
   if ($val) {
     $sql="SELECT $mo_id,$mo_value FROM $mo_tabl WHERE $mo_id IN ($val) $mo_coded ".
         "ORDER BY $mo_value;";
     $r=$this->xQuery($sql,1);
     $ar=pg_fetch_assoc($r);
     $ptr=0;
     while ($ar) {
       //$entry=$ar["$mo_id"].' '.$ar["$mo_value"];
       //$strng.="$entry ";
       $strng.=$ar["$mo_value"];
       $removeCode="jtRemove('$ptr',$jsParms)";
       $strng.=" <span style=\"color: #900;\" onClick=\"$removeCode\">(remove)</span>";
       $strng.="<br />\n";
       $ar=pg_fetch_assoc($r);
       $ptr++;
     }
     pg_free_result($r);
   }
   //$strng.="</span>\n";
   $strng.="<span style=\"color: #600;\" id =\"add_$k\">";
   $strng.="<strong onClick=\"jtAddToList($jsParms);\">Add</strong></span></p>\n";
   
} else if ($ti->getSize()<730) { //formulated many to one

   $strng="<p id=\"mo_$k\"><strong>".$ti->getLabel()."</strong><br />\n";
   //$strng.="<span id=\"li_$k\">";
   $strng.="<input type=\"hidden\" name=\"$k\" value=\"$val\" id=\"z_$k\" />\n";
   $mo_tabl=$ti->getOpts('tabl');
   $mo_id=$ti->getOpts('id');
   $mo_value=$ti->getOpts('value');
   $mo_coded=($ti->getOpts('coded')) ? " AND ".$ti->getOpts('coded')."='$k' " : '' ;
   $mo_db=$ti->getOpts('db');
   $mo_wiki=($ti->getOpts('wiki')) ? ' AND active>0 ' : '';
   $altConn=new Listing6($mo_db,$mo_tabl,$mo_id,$mo_value,'',0);
   if ($val) {
     $sql="SELECT $mo_id,$mo_value FROM $mo_tabl WHERE $mo_id IN ($val) $mo_wiki $mo_coded ".
         "ORDER BY $mo_value;";
     $r=$altConn->xQuery($sql,1);
     $ar=pg_fetch_assoc($r);
     while ($ar) {
       $entry=$ar["$mo_id"].' '.$ar["$mo_value"];
       $strng.="$entry<br />\n";
       $ar=pg_fetch_assoc($r);
     }
     pg_free_result($r);
   }
   //$strng.="</span>\n";
   $strng.="<span style=\"color: #600;\" id =\"add_$k\">";
   $mo_method='http://www.tfrec.wsu.edu/methodsLib.php?method='.$ti->getOpts('method');
   $strng.="<strong onClick=\"window.open('$mo_method','w_$k','');\">";
   $strng.="Add</strong></span></p>\n";
   
} elseif ($ti->getSize()<765) {// custom area, can be re-used for repeated field
  $strng="<p $css ><strong>".$ti->getLabel()."</strong></p>\n";
  $strng.="<input type=\"hidden\" name=\"$k\" id=\"z_$k\" value=\"$val\" />\n";
  $valList=($val) ? explode(';',$val) : array('');
  foreach($valList as $vEntry) {
      $strng.="<p $css id=\"p_$k\">".$ti->getOpts('prompt');
      $strng.="\n<input type=\"text\" name=\"a_$k\" class=\"c_$k\" value=\"$vEntry\" size=\"";
      $strng.=$ti->getSize()-740;
      $strng.="\" />\n</p>\n";
  }
  $strng.="<p $css id=\"b_$k\"><strong>".$ti->getOpts('bprompt')."</strong>\n";
  $jScript="cloneSection('p_$k','b_$k');";
  $strng.="<input type=\"button\" id=\"new_$k\" value=\"Add\" onClick=\"$jScript\" />";
  $strng.="</p>";
  
} elseif ($ti->getSize()<770) { //repeated set of fields -- no values
  $setOfFields=explode(',',$ti->getOpts('flds'));
  $arSize=explode(',',$ti->getOpts('sizes'));
  $arTitle=explode(',',$ti->getOpts('titles'));
  $strng='';

    $j=0;
    foreach ($setOfFields AS $fieldName) {
      $strng.=$arTitle[$j];
      $strng.="<input type=\"text\" name=\"a_$fieldName\" class=\"c_$fieldName\" size=\"";
      $strng.=$arSize[$j];
      $strng.="\" />\n";
      $j++;
    }
 
} elseif ($ti->getSize()<775) { //repeated set of fields -- prefilled values
  $setOfFields=explode(',',$ti->getOpts('flds'));
  $maxLen=0;
  $arDatAll=array();
  foreach ($setOfFields AS $fieldName) {
    if ($x=$this->currentRow["$fieldName"]) {
      $arDat=explode(';',$x);
      $maxLen=max($maxLen,sizeof($arDat));
    } else {
      $arDat=array();
    }
    $arDatAll["$fieldName"]=$arDat;
  } //setOfFields
  $arSize=explode(',',$ti->getOpts('sizes'));
  $arTitle=explode(',',$ti->getOpts('titles'));
  $strng='';
  
  if (!$maxLen) {
    $j=0;
    foreach ($setOfFields AS $fieldName) {
      $strng.=$arTitle[$j];
      $strng.="<input type=\"text\" name=\"a_$fieldName\" class=\"c_$fieldName\" size=\"";
      $strng.=$arSize[$j];
      $strng.="\" />\n";
      $j++;
    }  
  } else {
    for ($i=0;$i<$maxLen;$i++) {
      if ($i) {$strng.= "<p $css >";}
      $j=0;
      foreach ($setOfFields AS $fieldName) {
        $strng.=$arTitle[$j];
        $strng.="<input type=\"text\" name=\"a_$fieldName\" class=\"c_$fieldName\" size=\"";
        $strng.=$arSize[$j];
        $strng.="\" value=\"";
        $strng.=$arDatAll["$fieldName"][$i];
        $strng.="\" />\n";
        $j++;
      }
      if ($i<$maxLen-1) {$strng.="</p>\n";}
    }// i
  }

} elseif ($ti->getSize()<780) { //repeated set of fields -- no values
  $setOfFields=explode(',',$ti->getOpts('flds'));
  $arSize=explode(',',$ti->getOpts('sizes'));
  $arTitle=explode(',',$ti->getOpts('titles'));
  $strng="<table border=0>\n<tr>";
  foreach ($arTitle AS $fieldTitle) {
    $strng.="<td><strong>$fieldTitle</strong></td>";
  }
  $strng.="</tr>\n<tr id=\"a_$k\">";
    $j=0;
    foreach ($setOfFields AS $fieldName) {
      $strng.="<td><input type=\"text\" name=\"a_$fieldName\" class=\"c_$fieldName\" size=\"";
      $strng.=$arSize[$j];
      $strng.="\" /></td>\n";
      $j++;
    }
  $strng.="</tr>\n";
  $strng.="<tr id=\"b_$k\"><td colspan=\"".sizeof($setOfFields).
      "\"><strong>".$ti->getLabel()."</strong><input type=\"button\" value=\"Add\" ".
      "id=\"d_$k\" $clickJS /></td></tr>";
  $strng.="</table>\n<p>&nbsp;</p>\n";
  
} elseif ($ti->getSize()<785) { //repeated set of fields -- no values
  $setOfFields=explode(',',$ti->getOpts('flds'));
  $maxLen=0;
  $arDatAll=array();
  foreach ($setOfFields AS $fieldName) {
    if ($x=$this->currentRow["$fieldName"]) {
      $arDat=explode(';',$x);
      $maxLen=max($maxLen,sizeof($arDat));
    } else {
      $arDat=array();
    }
    $arDatAll["$fieldName"]=$arDat;
  } //setOfFields
  $arSize=explode(',',$ti->getOpts('sizes'));
  $arTitle=explode(',',$ti->getOpts('titles'));
  $strng="<table border=0>\n<tr>";
  foreach ($arTitle AS $fieldTitle) {
    $strng.="<td><strong>$fieldTitle</strong></td>";
  }
  $strng.="</tr>\n";
  
  if (!$maxLen) {
    $strng.="<tr id=\"a_$k\">";
      $j=0;
      foreach ($setOfFields AS $fieldName) {
        $strng.="<td><input type=\"text\" name=\"a_$fieldName\" class=\"c_$fieldName\" size=\"";
        $strng.=$arSize[$j];
        $strng.="\" /></td>\n";
        $j++;
      }
    $strng.="</tr>\n";
  } else {
      for ($i=0;$i<$maxLen;$i++) {
      if ($i) {$strng.= "<tr>";}
        else {$strng.="<tr id=\"a_$k\">";}
      $j=0;      
      foreach ($setOfFields AS $fieldName) {
          $strng.="<td><input type=\"text\" name=\"a_$fieldName\" class=\"c_$fieldName\" size=\"";
          $strng.=$arSize[$j];
          $strng.="\" value=\"";
          $strng.=$arDatAll["$fieldName"][$i];          
          $strng.="\" /></td>\n";
          $j++;
        }
      $strng.="</tr>\n";  
    }// i
  }  
  $strng.="<tr id=\"b_$k\"><td colspan=\"".sizeof($setOfFields).
      "\"><strong>".$ti->getLabel()."</strong><input type=\"button\" value=\"Add\" ".
      "id=\"d_$k\" $clickJS /></td></tr>";
  $strng.="</table>\n<p>&nbsp;</p>\n";
  
} elseif ($ti->getSize()<810) {//checkbox
   $strng.="<input type=\"checkbox\" name=\"$k\" id=\"z_$k\" ";
   $strng.="value=\"".$ti->getOpts('value')."\" ";
  if ($val) {$strng.="checked=\"checked\" ";}
  $strng.="$clickJS />\n<strong>";
  $strng.=$ti->getLabel();
  $strng.="</strong>\n";
  
} elseif ($ti->getSize()<900) { //select interval or time

      $strng="<strong>".$ti->getLabel()."</strong>\n<select name=\"$k\" id=\"z_$k\" $changeJS>\n";
      $strng.="<option value=\"0\">--</option>\n";
      $firstOpt=($ti->getOpts('first')) ? $ti->getOpts('first') : 7 ;
      $lastOpt=($ti->getOpts('last')) ? $ti->getOpts('last') : 22 ;
      $intOpt=($ti->getOpts('int')) ? $ti->getOpts('int') : 1 ;
      $i=$firstOpt;
      if ($ti->getSize()>849) {
        $timeDec=nWord($val,0,':')+nWord($val,1,':')/60;
      }
      $vAlt=($ti->getSize()>849) ? date('H:i',$timeDec*3600+mktime(0,0,0,1,1,2010)) : $val;
      $strng.="<!-- val = $val; vAlt = $vAlt time = $timeDec-->\n";
      while ($i<=$lastOpt) {
        $strng.="<option value=\"";
        $x=($ti->getSize()>849) ? date('H:i',$i*3600+mktime(0,0,0,1,1,2010)) : $i;
        $strng.="$x\" ";
        if ($x==$vAlt) {$strng.="selected=\"selected\" ";}
        $xA=($ti->getSize()>849) ? date('g:i',$i*3600+mktime(0,0,0,1,1,2010)) : $i;
        $strng.=">$xA";
        $strng.="</option>\n";
        $i+=$intOpt;
      }

      $strng.="</select>\n";

} elseif ($ti->getSize()<910) { //button
   $strng="<strong>".$ti->getLabel()."</strong>\n";
   $strng.="<input type=\"button\" id=\"z_$k\" value=\"";
   $strng.=$ti->getOpts('text');
   $strng.="\" $clickJS />\n";
   
} elseif ($ti->getSize()<950) { 
  $strng=$ti->getLabel();
  
} elseif ($ti->getSize()<960) { //lastdate
  $strng="<strong>".$ti->getLabel()."= ";
  $strng.=date('n/j/Y',strtotime($this->currentRow['lastdate']));
  $strng.="</strong>";

} elseif ($ti->getSize()>1000 and $ti->getSize()<1100) { //call special manyToOne
   //$strng="<input type=\"hidden\" name=\"$k\" id=\"z_$k\" value=\"$val\" />\n";
   $strng.=$this->manyToOne($ti,$k,$val);

} elseif ($ti->getSize()<1110) {
  if ($ti->getSize()<1109) {$strng="<div $css $promptId $class >\n";}
    else $strng.="</div>\n";

} elseif ($ti->getSize()<1150) { //set of fields -- no repeat
  $setOfFields=explode(',',$ti->getOpts('flds'));
  $arSize=explode(',',$ti->getOpts('sizes'));
  $arTitle=explode(',',$ti->getOpts('titles'));
  $strng="<table border=0>\n<tr>";
  foreach ($arTitle AS $fieldTitle) {
    $strng.="<td><strong>$fieldTitle</strong></td>";
  }
  $strng.="</tr>\n";  
      $strng.= "<tr>";
      $j=0;      
      foreach ($setOfFields AS $fieldName) {
          $strng.="<td><input type=\"text\" name=\"$fieldName\" id=\"z_$fieldName\" size=\"";
          $strng.=$arSize[$j];
          if ($ti->getSize()>1130) {
            $strng.="\" value=\"";
            $strng.=$this->currentRow["$fieldName"];
          }
          $strng.="\" /></td>\n";
          $j++;
        }
      $strng.="</tr>\n";  
  $strng.="</table>\n<p>&nbsp;</p>\n"; 
  
 } elseif ($ti->getSize()<1150) { //list -- hidden if empty
 
   $strng="<input type=\"hidden\" name=\"$k\" id=\"z_$k\" value=\"$val\" />\n";
   $strng="<strong>".$ti->getLabel()."</strong>";
   if ($val) {}
   else { $strng.=' <strong>None</strong>';}
   
 } elseif ($ti->getSize()<1201) { // include php code
   $strng='<!-- '.$ti->getOpts('include')." -->\n";
   include $ti->getOpts('include');
 } elseif ($ti->getSize()<1300) { // insert space.gif
    $w=12*($ti->getSize()-1200);
    $strng="<img src=\"http://jenny.tfrec.wsu.edu/space.gif\" width=\"$w\" height=\"1\" ".
      "border=\"0\" />\n";
 } elseif ($ti->getSize()<1310) {
   $strng="Please enter security code<br />".
    "<img id=\"captcha\" src=\"http://jenny.tfrec.wsu.edu/securimage/securimage_show.php\" ".
     "alt=\"CAPTCHA Image\" /><br />Code <input type=\"text\" name=\"captcha_code\" size=\"10\" ".
     "maxlength=\"6\" /> <a onclick=\"document.getElementById('captcha').".
     "src='http://jenny.tfrec.wsu.edu/securimage/securimage_show.php?'+".
     "Math.random();return false\" href=\"#\">[Different Image]</a>";
 } elseif ($ti->getSize()<1350) {
   $newID=($val) ? $val : $this->getNextIndx($k);
   $strng="<strong>".$ti->getLabel()." = $newID</strong>\n";
   $strng.="<input type=\"hidden\" name=\"$k\" id=\"z_$k\" value=\"$newID\" >\n";
 } elseif ($ti->getSize()<1400) {
   $lookUpVal=$this->lookupValue($k,array($ti->getOpts('index')=>$val));
   $strng="<strong>".$ti->getLabel()." = $lookUpVal</strong>\n";
   $strng.="<input type=\"hidden\" name=\"$k\" id=\"z_$k\" value=\"$lookUpVal\" >\n";
 } elseif ($ti->getSize()<1450) {
   $qSQL="SELECT ".$ti->getOpts('outp').' FROM '.$ti->getOpts('table').
       " WHERE $k=$val ;";
   $lookUpVal=$this->getValueFromDB_SQL($qSQL);
   if ($lookUpVal) {$strng="<strong>".$ti->getLabel()." = $lookUpVal</strong>\n";}
   if (!$ti->getOpts('noinput')) {
     $strng.="<input type=\"hidden\" name=\"$k\" id=\"z_$k\" value=\"$val\" >\n";
   }
 } elseif ($ti->getSize()<1500) {
   if (!$val) {$val='0';}
   if ($qSQL=$ti->getOpts('sql')) {$qSQL=str_replace('VALUE',"'$val'",$qSQL);}
     else { $qSQL="SELECT ".$ti->getOpts('outp').' FROM '.$ti->getOpts('table').
         " WHERE ".$ti->getOpts('inp')."='".$val."' $whereActive ;";}
   $lookUpVal=$this->getValueFromDB_SQL($qSQL);
   if (!$lookUpVal AND $ti->getOpts('default')) {$lookUpVal=$ti->getOpts('default');}
   if ($lookUpVal) {$strng="<strong>".$ti->getLabel()." = $lookUpVal</strong>\n";}
   if (!$ti->getOpts('noinput')) {
     $strng.="<input type=\"hidden\" name=\"$k\" id=\"z_$k\" value=\"$lookUpVal\" >\n";
   } elseif ($ti->getOpts('noinput')==2) {
     $strng.="<input type=\"hidden\" name=\"$k\" id=\"z_$k\" value=\"$val\" >\n";
   }
} elseif ($ti->getSize()<1550) {
  $cRadio=str_replace('\t',"\t",$ti->getOpts('radio'));
  $cValues=str_replace('\t',"\t",$ti->getOpts('values'));
  $prompts=explode("\t",$cRadio);
  $values=explode("\t",$cValues);
  $tempArr=array_combine($values,$prompts);
  $lookUpVal=$tempArr["$val"];
  $strng="<strong>".$ti->getLabel()." = $lookUpVal</strong>\n";
  $strng.="<input type=\"hidden\" name=\"$k\" id=\"z_$k\" value=\"$val\" />\n";   
} elseif ($ti->getSize()<1600) { //create set of indexed checkboxes for new input
  $values=explode('\t',$ti->getOpts('values'));
  $strng='';
  $optN=1;
  foreach ($values as $checkopt) {    
    if ($checkopt!='*') {$strng.="<input type=\"checkbox\" name=\"$k"."$optN\" ".
        "value=\"$checkopt\" /> $checkopt\n";
      $optN++;
    } else {
      $strng.="</p>\n<p>";
    }
  }
} else {
    $strng='';
 }

 if ($sz=$ti->getOpts('other')) {
   $strng.="\n";
   $strng.="<input type=\"checkbox\" name=\"$k\" value=\"useOther\" ";
   if ($val and !$optFound) {$strng.="checked=\"checked\" ";}
   $strng.="/>\n";
   $strng.="<strong>other</strong>\n";
   $strng.="<input type=\"text\" name=\"add_$k\" size=\"$sz\" ";
   if ($val and !$optFound) {$strng.="value=\"$val\" ";}
   $strng.="/>";
   $strng.="\n";
 } 
 if (!($sz=$ti->getOpts('kill'))) {$strng="<p $css $promptId>$strng</p>";} 
   elseif ($sz=='4') {$strng="<p $css $promptId>$strng";}
   elseif ($sz=='5') {$strng="$strng</p>";}
   elseif ($sz!='6' AND $css) {$strng="<span $css $promptId>$strng</span>\n";}
   
 return $strng;
}
function formItemToOutput($ti,$k,$v) {
  if ($ti->getOpts('drop')=='yes') {
    return '';
  } elseif (!$ti->getSize() AND !$ti->getOpts('showhidden')) {
    return '';
  } else  {
    if ($ti->getSize()==98) {
        $xar=explode('\t',$ti->getOpts('values'));
        $v=$xar[$v-1];
    } elseif ($fd=$ti->getOpts('formatdate')) {
      $v=date($fd,strtotime($v));
    } elseif ($ti->getSize()>849 AND $ti->getSize()<900) {
      $v=date('g:iA',strtotime($v)); 
    } elseif ($ti->getSize()>=410 AND $ti->getSize()<=449) {
      $xar=array_combine(explode('\t',$ti->getOpts('values')),explode('\t',$ti->getOpts('radio')));
      $v=$xar["$v"]; 
    }
    $v=($v) ? $v : 'n/a' ;
    return "<p><strong>".$ti->getLabel()." </strong>= $v</p>\n";
  }
}

};

class IncludedTabls extends Pages6 {
protected $lookUpTabl;
protected $lookUpIndx;
protected $lookUpTitl;
protected $lookUpWiki;

protected $dat; 
function __construct($ti,$dbName) {
  $this->lookUpTabl=$ti->getOpts('l_tabl');
  $this->lookUpIndx=$ti->getOpts('l_indx');
  $this->lookUpTitl=$ti->getOpts('l_titl');
  $this->lookUpWiki=$ti->getOpts('l_wiki');
  $this->dat=$ti->getOpts('g_data');
  Pages6::__construct($ti->getOpts('g_tabl'),$ti->getOpts('g_indx'),$ti->getOpts('g_wiki'),$dbName);
}
function getTableName() {
  return $this->tabl;
}
function getLookUpIndx() {return $this->lookUpIndx;}
function getLookUpTable() {return $this->lookUpTabl;}
function getLookUpTitle() {return $this->lookUpTitl;}
function getLookUpWiki() {return $this->lookUpWiki;}
function getDatName() {return $this->dat;}
};
//echo "<p>Database classes loaded</p>\n";
?>

