<?php
class JTbegin {
  function __construct() {
    echo "<p>This is a dummy class</p>\n";
  }
};

$thisFileName=basename($_SERVER["SCRIPT_FILENAME"],".php");
function thisFileName() {
  global $thisFileName;
  return $thisFileName;
}

function stripTagsTool($strng) {
  $buffer='';
  $old=$strng;
  $p=strpos($old,'<');
  while ($p!==false) {
    $q=strpos($old,'>');
    if ($p>0) {$buffer.=substr($old,0,$p);}
    $old=substr($old,$q+1);
    $p=strpos($old,'<');
  }
  return ($buffer.$old);
}

 function noParaMarks($strng) {
   $buffer=trim($strng);
   if (preg_match('#^<p>#',$buffer)) {
     $nb=substr($buffer,3);
     if (preg_match("#</p>$#",$nb)) {
       $nb=substr($nb,0,strrpos($nb,'<'));}
   } else {
     $nb=$strng;
   }
   return $nb;
 }

function nWord ($strng,$wordn,$delimit) {
  $sar=explode($delimit,$strng);
  return $sar[$wordn];
}

function lastWord ($strng,$delimit) {
  $sar=explode($delimit,$strng);
  if ($sar) {
    $last=sizeof($sar)-1;
    return $sar[$last];}
  else return '';
}

function openForm($tag,$name,$action,$method,$closeForm,$submit,$hiddenVars) {
  /* openForm("base","editWeb","POST",1,"Continue",$hiddenVars); */
  echo "<form action=\"$action.php\" method=\"$method\" ";
  if ($name) {echo "name=\"$name\" ";}
  echo ">\n";
  if ($tag) {echo "<b>$tag</b> ";}
  reset($hiddenVars);
  while (list($k,$v)=each($hiddenVars)) {
    echo "<input type=\"hidden\" name=\"$k\" id =\"$k\" value=\"$v\" />\n";
  }
  if ($submit) {
    echo "<input type=\"submit\" name=\"gofor\" value=\"$submit\" />\n";
  }
  if ($closeForm>0) {echo "</form>\n";}
  return 1;
} /*end openForm*/

function openFormA($tag,$name,$action,$method,$closeForm,$submit,$hiddenVars) {
  /* openForm("base","editWeb","POST",1,"Continue",$hiddenVars); */
  echo "<form action=\"$action\" method=\"$method\" ";
  if ($name) {echo "name=\"$name\" ";}
  echo ">\n";
  if ($tag) {echo "<b>$tag</b> ";}
  reset($hiddenVars);
  while (list($k,$v)=each($hiddenVars)) {
    echo "<input type=\"hidden\" name=\"$k\" id =\"$k\" value=\"$v\" />\n";
  }
  if ($submit) {
    echo "<input type=\"submit\" name=\"gofor\" value=\"$submit\" />\n";
  }
  if ($closeForm>0) {echo "</form>\n";}
  return 1;
} /*end openFormA*/

function closeForm($submit,$reset,$option) {
  echo "<p>";
  if ($submit) {
    echo "<input type=\"submit\" name=\"gofor\" value=\"$submit\">\n";
  }
  if ($option) {
    echo "<input type=\"submit\" name=\"gofor\" value=\"$option\">\n";
  }
  if ($reset) {echo "<input type=\"reset\" value=\"$reset\">\n";}
  echo "</p></form>\n";
  return 1;
} /*end closeForm*/

function newQuery($SQLstmt,$display) {
  global $conn,$recentNRows;
  if ($display==1) {echo "<!-- $SQLstmt -->\n";}
    elseif ($display>1) {echo "<p> $SQLstmt </p>\n";}
  if ($display<3) {
    $result =  pg_Exec($conn,$SQLstmt);
    if (!$result) {
      echo "<p>Execution failed!<br>";
      if ($display<2) {echo $SQLstmt."<br />\n";}
      echo pg_errormessage($conn); exit;
    }
    $recentNRows=pg_num_rows($result);
    return $result;
  } else {
    $recentNRows=0;
    return 0;
  }
} /*end newQuery*/

function selectFromQuery
  ($result,$formName,$varName,$currValue,$strngName,$zeroOpt,$change,$click) {
  $recentNRows=pg_num_rows($result);
  echo "<select name=\"$formName\" id=\"$formName\" ";
  if ($change) {echo "onChange=\"$change\" ";}
  if ($click) {echo "onClick=\"$click\" ";}
  echo ">\n";
  if ($zeroOpt) {
    echo "<option value=\"0\">$zeroOpt</option>\n";
  }
  $i=0;
  while ($i<$recentNRows) {
    $x=pg_fetch_result($result,$i,$varName);
    echo "<option value=\"$x\" ";
    if ($currValue) {
      if ($x==$currValue) { echo "selected";}
    }
    echo ">";
    if ($strngName) { $strng=pg_fetch_result($result,$i,$strngName);}
      else {$strng=$x;}
    if (!$strng) {$strng='n/a';}
    echo $strng;
    echo "</option>\n";
    $i++;
  }
  echo "</select>\n";
  return 1;
} /* end selectFromQuery */

function selectFromArray
  ($formName,$strngs,$currValue,$txts,$zeroOpt,$change,$click) {
  global $recentNRows;
  echo "<select name=\"$formName\" id=\"$formName\" ";
  if ($change) {echo "onChange=\"$change\" ";}
  if ($click) {echo "onClick=\"$click\" ";}
  echo ">\n";
  if ($zeroOpt) {
    echo "<option value=\"0\">$zeroOpt</option>\n";
  }
  $i=0;
  if ($strngs) {$z=sizeof($strngs);$j=0;}
    else {$z=sizeof($txts);$j=1;}
  while ($i<$z) {
    if ($j) {$x=$j;$j++;}
      else {$x=$strngs[$i];}
    echo "<option value=\"$x\" ";
    if ($currValue) {
      if (($j or !$txts) and $x==$currValue) { echo "selected=\"selected\"";}
      elseif (!$j and $x==$currValue) { echo "selected=\"selected\"";}
      elseif ($txts[$i]==$currValue) { echo "selected=\"selected\"";}
    }
    echo ">";
    if ($txts) { echo $txts[$i];}
      else {echo $x;}
    echo "</option>\n";
    $i++;
  }
  echo "</select>\n";
  return 1;
} /* end selectFromArray */


function obtain($id) {
  $connx=pg_connect("dbname=allmine");
  if (!$connx) {echo "<p>Connection problems</p>\n"; exit();}
  $SQLstmt="SELECT id,p FROM trackit;";
  $r=pg_query($connx,$SQLstmt);
  $p=pg_result($r,0,"p");
  pg_free_result($r);
  pg_close($connx);
  return $p;
}

function lookForCookie($cname,$db,$user,$id) {
  global $conn,$connmsg;
     $cookiecat=$_COOKIE["$cname"];
     if ($cookiecat) {
       $az=obtain($id);
  	   $conn = pg_connect("dbname=$db user=$user password=$az");
  	 } else {
  	   $conn = pg_connect("dbname=$db");
  	   $connmsg="<p><b>Sorry, you need to <a href=\"editWeb.php\">login</a> ";
  	   $connmsg.="to use this application</b></p>\n";
  	 }
     if (!$conn) {echo "<p>Couldn't make the connection</p>\n"; exit;}
     
     return 1;
}

function dumpRegs() {
  global $yourin;
  reset($_REQUEST);
  $strng="<p>";
  while (list($k,$v)=each($_REQUEST)) {
    $strng.="$k == $v <br />\n";
  }
  $strng.="yourin = $yourin </p>\n";
  return $strng;
}

function dumpSession() {
  global $yourin;
  reset($_SESSION);
  $strng="<p>";
  while (list($k,$v)=each($_SESSION)) {
    $strng.="$k == $v <br />\n";
  }
  $strng.="yourin = $yourin </p>\n";
  return $strng;
}


function textInput($name,$value,$size) {
  echo "<input type=\"text\" name=\"$name\" id=\"z_$name\" ";
  echo "value=\"$value\" size=\"$size\" />\n";
}

function genericInput($type,$name,$value,$size) {
  echo "<input type=\"$type\" name=\"$name\" id=\"z_$name\" ";
  echo "value=\"$value\" size=\"$size\" />\n";
}

function addQtoInput($name,$tabl,$altName) {
    echo "<span id=\"y_$name\">";
    if (!$altName) {$altName=$name;}
    addButton("x_$name","Find similar","findsimA('$altName','$tabl','$name')");
    echo "</span>\n";
    return 1;
}

function radioInput($name,$value) {
  echo "<input type=\"radio\" name=\"$name\" id=\"z_$name\" ";
  echo "value=\"$value\" />\n";
  return 1;
}

function radioInputCk($name,$value) {
  echo "<input type=\"radio\" name=\"$name\" id=\"z_$name\" ";
  echo "value=\"$value\" checked=\"checked\" />\n";
  return 1;
}

function radioInputWScript($name,$value,$ck,$js) {
  echo "<input type=\"radio\" name=\"$name\" id=\"z_$name\" ";
  echo "value=\"$value\" ";
  if ($ck) {echo "checked=\"checked\" ";}
  echo "onClick=\"$js\" />\n";
  return 1;
}
function radioInputFromArray($radioId,$titles,$values,$default,$js) {
  if (!$values) {$values=$titles;}
  $ar_titl=array_combine(explode(',',$titles),explode(',',$values));
  $on=($js) ? "onClick=\"$js\"" : '';
  foreach ($ar_titl as $titl=>$val) {
    echo "<input type=\"radio\" name=\"$radioId\" id=\"z_$radioId\" ";
    echo "value=\"$val\" ";
    if ($default==$val) {echo "checked=\"checked\" ";}
    echo "$on /> $titl\n";
  }
  return 1;
}

function radioButtonChain($name,$arr,$current,$stacked) {
  foreach($arr AS $k=>$v) {
    if ($k==$current) {
      radioInputCk($name,$k);
    } else {
      radioInput($name,$k);
    }
    echo $v;
    if ($stacked) {echo "<br />";}
    echo "\n";
  }
  return 0;
}

function hiddenInput($name,$value) {
  echo "<input type=\"hidden\" name=\"$name\" id=\"z_$name\" ";
  echo "value=\"$value\" />\n";
}

function textAreaInput($name,$value,$rows) {
  echo "<textarea name=\"$name\" id=\"z_$name\" rows=\"$rows\" cols=\"60\" ";
  echo "edHTML=\"true\" >\n";
  echo $value;
  echo "\n</textarea>\n";
  return 1;
}

function textAreaInputAlt($name,$value,$rows) {
  echo "<textarea name=\"$name\" id=\"z_$name\" rows=\"$rows\" cols=\"60\" ";
  echo "edHTML=\"true\" >";
  echo $value;
  echo "</textarea>\n";
  return 1;
}

function textAreaInput3($name,$value,$rows,$tiny) {
  echo "<textarea name=\"$name\" id=\"z_$name\" rows=\"$rows\" cols=\"60\" ";
  if ($tiny) {echo " class=\"edHTML\" ";}
  echo " >\n";
  echo $value;
  echo "\n</textarea>\n";
  return 1;
}

function checkboxInput($name,$value,$checked) {
  echo "<input type=\"checkbox\" name=\"$name\" id=\"z_$name\" ";
  echo "value=\"$value\" ";
  if ($checked) {
    echo "checked=\"checked\" ";
  }
  echo "/>\n";  
  return 1;
}

function otherInput($name,$size) {
    checkboxInput($name,'useOther','');
    echo "<strong>otherwise</strong>\n";
    textInput('add_'.$name,'',$size);
    return 1;
}

function fileInput($name,$size,$multi) {
  hiddenInput('MAX_FILE_SIZE',$size);
  echo "<input type=\"file\" name=\"$name\" id=\"z_$name\" ";
  if ($multi) { echo "multiple ";}
  echo "/>\n";
}


class TableItem {
  private $name;
  private $size;
  private $short;
  private $label;
  private $alpha;
  private $opts;
  private $value;
 
  function __construct($name,$size,$short,$label,$alpha,$opts) {
    $this->name=$name;
    $this->size=$size;
    $this->short=$short;
    $this->label=$label;
    $this->alpha=$alpha;
    if ($opts) {
      $items=explode(';',$opts);
      $z=sizeof($items);
      for ($i=0;$i<$z;$i++) {
        $p=strpos($items[$i],'=');
        $k=substr($items[$i],0,$p);
        $this->opts["$k"]=substr($items[$i],$p+1);
      }
    } else {
      $this->opts=array();
    }
  }
  
  function getLabel() {
    return $this->label;
  }

  function getItemName() {
    return $this->name;
  }

  function getSize() {
    return $this->size;
  }

  function getShort() {
    return $this->short;
  }

  function getAlpha() {
    return $this->alpha;
  }
  function getOpts($key) {
    return $this->opts["$key"];
  }
  function getAllOpts() {
       return $this->opts;
  }
  function setValue($value) {
    $this->value=$value;
    return 1;
  }

  function getValue() {
    return $this->value;
  }

};

function selectInput($var,$tablItem,$current) {
  $tabl=$tablItem->getOpts('table');
  $colname=$tablItem->getOpts('name');
  if (!$colname) {$colname=$var;}  
  $txtname=$tablItem->getOpts('txt');
  if ($txtname) {
    $vList=$txtname.','.$colname;
  } else {
    $vList=$colname;
  }
  
  $SQLstmt="SELECT DISTINCT $vList FROM $tabl ORDER BY $vList ;";
  $r=newQuery($SQLstmt,0);
  selectFromQuery($r,$var,$colname,$current,$txtname,'');
  pg_free_result($r);
  return 1;
}

function addButton($name,$txt,$onclick) {
  echo "<input type=\"button\" name=\"$name\" id=\"$name\" ";
  echo "value=\"$txt\" onClick=\"$onclick\" />\n";
}

function addGofor($txt) {
  echo "<input type=\"submit\" name=\"gofor\" id=\"gofor\" ",
    "value=\"$txt\" />\n";
}

function getNextID($tabl,$col) {
  $SQLstmt="SELECT max($col) as maxid FROM $tabl;";
  $r=newQuery($SQLstmt,0);
  $maxid=pg_result($r,0,'maxid')+1;
  pg_free_result($r);
  return $maxid;
}

function authnk8($db,$tabl,$ar,$db2) {
  global $conn;
  $conn=pg_connect('dbname='.$db);
  if (!$conn) {
    return 0;
  } else {
      if (!$db2) {$db2=$db;}
      $SQL="SELECT * FROM $tabl WHERE ";
      $ad=0;
      while (list($k,$v)=each($ar)) {
        if ($ad) {$SQL.='AND ';}
        $x=pg_escape_string($v);
        $SQL.=" $k='$x' ";
        $ad=1;
      }
      $SQL.=';';
      $r=newQuery($SQL,0);
      $n=pg_num_rows($r);
      pg_free_result($r);
      pg_close($conn);
      if ($n>0) {
        $conn=pg_connect("dbname=$db2 user=dbguest password=".obtain('tfrec'));
        return 2;
      } else {
        $conn=pg_connect("dbname=$db2");
        return 1;        
      }
    }
} // authnk8

function popUpFromQuery
  ($result,$formName,$varName,$currValue,$strngName,$zeroOpt,$change,$class) {
  global $recentNRows;
  echo "<select name=\"$formName\" id=\"$formName\" ";
  if ($class) {echo "class=\"$class\" ";}
  if ($change) {echo "onChange=\"$change\" ";}
  if ($click) {echo "onClick=\"$click\" ";}
  echo ">\n";
  if ($zeroOpt) {
    echo "<option value=\"\">$zeroOpt</option>\n";
  }
  $i=0;
  while ($i<$recentNRows) {
    $x=pg_fetch_result($result,$i,$varName);
    echo "<option value=\"$x\" ";
    if ($currValue) {
      if ($x==$currValue) { echo "selected";}
    }
    echo ">";
    if ($strngName) { $strng=pg_fetch_result($result,$i,$strngName);}
      else {$strng=$x;}
    if (!$strng) {$strng='n/a';}
    echo $strng;
    echo "</option>\n";
    $i++;
  }
  echo "<option value=\"CANCEL\">CANCEL</option>\n";
  echo "</select>\n";
  return 1;
} /* end popUpFromQuery */

function where_list($ar) {
  $v=reset($ar);
  $k=key($ar);
  $strng='';
  while ($k) {
    $strng.="$k='$v' ";
    $v=next($ar);
    $k=key($ar);
    if ($k) {$strng.='AND ';}
  }
  return $strng;
}

function where_listWName($ar,$tablName) {
  $v=reset($ar);
  $k=key($ar);
  $strng='';
  while ($k) {
    $strng.="$tablName.$k='$v' ";
    $v=next($ar);
    $k=key($ar);
    if ($k) {$strng.='AND ';}
  }
  return $strng;
}

function where_listCoded($ar) {
  $v=reset($ar);
  $k=key($ar);
  $strng='';
  while ($k) {
    if (substr($v,0,1)=='~') {$strng.="$k LIKE '%".substr($v,1)."%' ";} 
      elseif (in_array(substr($v,0,1),array('<','>'))) {
        $strng.="$k".substr($v,0,1);
        if (substr($v,1,1)=='=') {$strng.="=".substr($v,2).' ';}
          else {$strng.=substr($v,1).' ';}
      }
      else {$strng.="$k='$v' ";}
    $v=next($ar);
    $k=key($ar);
    if ($k) {$strng.='AND ';}
  }
  return $strng;
}

function whereORList($col,$commaList,$like,$nocase,$orAnd) {
  if ($col and $commaList) {
    if ($nocase) {$col="upper($col)";}
    $comparison=($like) ? ' LIKE ' : '=';
    $ar=explode(',',$commaList);
    $whStrng='(';
    $ct=0;
    foreach($ar as $item) {
      $strng=trim($item);
      if ($nocase) {$strng=strtoupper($strng);}
      if ($like) {$strng="%$strng%";}
      $whStrng.="$col".$comparison."'$strng'";
      $ct++;
      if ($ct<sizeof($ar)) {$whStrng.=" $orAnd ";}
    }
    $whStrng.=')';
    return $whStrng;
  } else {
    return '';
  }
}

function getValueFromDB($tabl,$var,$qValues) {
  $r=newQuery("SELECT $var FROM $tabl WHERE ".where_list($qValues).";",0);
  $x=pg_result($r,0,$var);
  pg_free_result($r);
  return $x;
}

function getValueFromDB_SQL($SQL) {
  $r=newQuery($SQL,0);
  $x=pg_fetch_result($r,0,0);
  pg_free_result($r);
  return $x;
}

function getDBColumn($tabl,$var,$qValues) {
  $SQL=$qValues ? "SELECT DISTINCT $var FROM $tabl WHERE ".where_list($qValues)." ORDER BY $var ;" :
      "SELECT DISTINCT $var FROM $tabl ORDER BY $var ;";
  $r=newQuery($SQL,0);
  $ar=pg_fetch_all_columns($r,0);
  pg_free_result($r);
  return $ar;
}

function getDBColumn_SQL($SQL) {
  $r=newQuery($SQL,0);
  $ar=pg_fetch_all_columns($r,0);
  pg_free_result($r);
  return $ar;
}

function getValueFromDB2($tabl,$var,$qValues,$dbName) {
  $newConn=pg_connect('dbname='.$dbName);
  $SQLstmt="SELECT $var FROM $tabl WHERE ".where_list($qValues).";";
  if (!$newConn) {echo "<!-- Couldn't connect to $dbName -->\n";}
  //echo "<!-- $SQLstmt -->\n";
  $result =  pg_Exec($newConn,$SQLstmt);
    if (!$result) {
      echo "<p>Query failed to $dbName <br>";
      echo $SQLstmt."<br />\n";
      echo pg_errormessage($newConn);
      $x= '';
    } else { 
      $x=pg_result($result,0,$var);
    }
  pg_free_result($result);
  pg_close($newConn);
  return $x;
}

function secureIP() {
  $ipaddr=$_SERVER["HTTP_PC_REMOTE_ADDR"];
  return (substr($ipaddr,0,11)=="207.180.117") ? 1 : 0;
}

function compileList($delimit,$lines) {
  $list=array();
  foreach ($lines as $line) {
    $items=explode($delimit,$line);
    foreach ($items as $item) {
      $entry=trim($item);
      if ($entry) {$list["$entry"]=strtolower($entry);}
    }
  }
  asort($list);
  return array_keys($list);
}

function displayCompileList($keyList,$prefix) {
      echo "<table border=\"0\">";
      $ict=0;
      echo "<tr valign=\"top\" align=\"left\">";
      $iKw=0;
      foreach ($keyList as $key) { 
        if ($ict>2) {echo "</tr>\n<tr valign=\"top\" align=\"left\">";
          $ict=0;
        }
        echo "<td width=\"33%\">";
        checkboxInput($prefix.$iKw,$key,'');
        $iKw++;
        echo "$key</td>";
        $ict++;
      }
      echo "</tr>\n";
      echo "</table>\n";
      return 1;
}

function extractCheckBoxes($prefix,$ar) {
  $l=strlen($prefix);
  $bar=array();
  foreach ($ar as $key => $val) {
    if (substr($key,0,$l)==$prefix) {
      $indx=substr($key,$l);
      $bar["$indx"]=$val;
    }
  }
  return $bar;
}

function reOrderItems($tabl,$ar_where,$indx,$ordr,$sortby,$start,$inc) {
  $strng="SELECT $indx,$ordr FROM $tabl ";
  if ($wh_str=where_list($ar_where)) {$strng.=" WHERE $wh_str ";}
  $orderby=$sortby ? " ORDER BY $sortby " :" ORDER BY $ordr ";
  $strng.="$orderby ;";
  $rX=newQuery($strng,0);
  $ar=pg_fetch_assoc($rX);
  $v=$start;
  while ($ar) {
    $rU=newQuery("UPDATE $tabl SET $ordr=$v WHERE $indx=".$ar["$indx"],0);
    pg_free_result($rU);
    $v+=$inc;
    $ar=pg_fetch_assoc($rX);
  }
  pg_free_result($rX);
}

function augmentName($name,$var,$tabl,$where) {
  $augN=0;
  $strng=$name;
  $whereAs=($where) ? array_merge($where,array($var=>$strng)) : array($var=>$strng);
  while ($strng==getValueFromDB($tabl,$var,$whereAs)) {
    $augN++;
    $strng=$name.'_'."$augN";
  }
  return $strng;
}

function cImplode($ch,$ar,$n,$m,$mode) {
    $sar=(is_array($ar)) ? $ar : explode($ch,$ar);
    foreach ($sar AS &$var) {
      if (strlen($var)>($n+$m)) {
        $p=substr($var,0,$n);
        $q=substr($var,-$m);
        $var=$p.'...'.$q;
      }
    }
    unset($var);
    if ($mode=='array') {return $sar;}
      else {return implode($ch,$sar);}
} 
?>
