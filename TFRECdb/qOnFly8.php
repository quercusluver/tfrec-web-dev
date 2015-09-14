<?php
  class QonFly extends Listing6 {
  
  function putItemInfo($ar) {
    $this->itemInfo=$ar;
    return 1;
  }
  function getItemOpts($var) {
    $a=$this->xQueryInfo["$var"];
    return $a->getAllOpts();
  }
  function distinctQuery($var,$ar) {
    $z=extract($ar);
    if ($this->useWiki) { $act='WHERE active>0'; $and=' AND ';}
      else {$act=''; $and='WHERE';}
    $subQ="SELECT MAX(".$this->indx.") AS maxIndx FROM ".$this->tabl.
          " $act GROUP BY $key";
    $SQL="SELECT a.".$this->indx.",a.$key,$var FROM ".$this->tabl." a $act $and ".
        $this->indx." IN ($subQ) ORDER BY $var";
    return $SQL;
  }
  function valueQuery($var) {
    $z=extract($ar);
    if ($this->useWiki) { $act="WHERE active>0"; }
      else {$act='';}
    $SQL="SELECT DISTINCT $var FROM ".$this->tabl." $act ORDER BY $var";
    return $SQL;
  }
  function manyToOneQuery($ar) {
    $z=extract($ar);
    //$act=($this->useWiki) ? ' WHERE active>0 ' : '';
    $SQL="SELECT DISTINCT $many FROM ".$this->tabl;
    $rZ=$this->xQuery($SQL,0);
    $aList=compileList(',',pg_fetch_all_columns($rZ));
    pg_free_result($rZ);
    $act=($useWiki) ? 'WHERE active>0 AND ' : 'WHERE ';
    $SQL="SELECT DISTINCT $key,$var FROM $tabl $act ".
        "$key IN (".implode(',',$aList).")";
    return $SQL;
  }
  function lookupQuery($ar) {
    $z=extract($ar);
    if ($useWiki) { $act="WHERE active>0"; }
      else {$act='';}
    $SQL="SELECT DISTINCT $key,$var FROM $tabl $act ORDER BY $var";
    return $SQL;
  }
  function listQuery($var) {
    if (useWiki) { $act="WHERE active>0"; }
      else {$act='';}
    $SQL="SELECT DISTINCT ".$this->indx.",$var FROM ".$this->tabl." $act ORDER BY $var";
    return $SQL;  
  }
  function getListVars() {
    $vlist=array_keys($this->xQueryInfo);
    //echo "<PRE>\n",print_r($vlist),"\n</PRE>\n";
    return $vlist;
  }
  function getVarTitle($var) {
    $a=$this->xQueryInfo["$var"];
    return $a->getLabel();
  }
  function getQParmName($var,$opt) {
    $a=$this->itemInfo["$var"];
    $varName=($v=$a->getOpts($opt)) ? $v : $var;
    return $varName;
  }
  function noQuery($ar) {
    return '';
  }
  function fetchQuery($var,$ar,$f) {
    $a=$this->xQueryInfo["$var"];
    $f=$a->getSize();
    $ar=$a->getAllOpts();
    if ($f==1) {return $this->distinctQuery($var,$ar);}
      elseif ($f==2) {return $this->lookupQuery($ar);}
      elseif ($f==3) {return $this->valueQuery($var);}
      elseif ($f==4) {return $this->manyToOneQuery($ar);}
      elseif ($f==5) {return $this->noQuery($ar);}
      else {return $this->listTest($ar);}
  }    
  function listTest($ar) {
    echo "<test/>\n";
    echo "<p>";print_r($ar);echo "</p>\n";
    return 0;
  }
  
 /*
  $mo_db=($_REQUEST['db']) ? $_REQUEST['db'] : $callerAr['db'];
  include '/WebStuff/apacheSites/'.$mo_db;
  $postStrng="db=$mo_db&method=qOnFly";
 
  //To be included in db specific files
  $cols=array();
  $cols['title']=new TableItem('title',1,'','Project Title',0,'key=projid');
  $cols['aid']=new TableItem('aid',2,'','Principal PI',0,'var=lastname;key=n;tabl=pis;useWiki=0');
  $cols['tid']=new TableItem('tid',2,'','Category',0,'var=txt;key=n;tabl=topic;useWiki=0');
  $grants=new QonFly('grants','gid','1','csanr',$cols);

  //
 
  $mo_specific=$_REQUEST['sp'];
  $mo_cmnd=($_REQUEST['cmnd']) ? $_REQUEST['cmnd'] : $callerAr['cmnd'];*/
  
function parseToWhere($vars,$bool,$selx,$etype) {
  $arVars=explode(';',$vars);
  $arBool=explode(';',$bool);
  $arSelx=explode(';',$selx);
  $arEtype=explode(';',$etype);
  
  $i=0;
  //$iMax=0;
  $whereClause='';
  //$chosenOnes=array();
  
  foreach($arVars AS $varName) {
    if ($whereClause) {$whereClause.=$arBool[$i];}
    if (substr($arSelx[$i],0,2)=='__') {
      $op=substr($arSelx[$i],2,2);
      $val=substr($arSelx[$i],4);
      if ($op=='lt') {$whereClause.=' ('.$varName."<'".$val."') ";}
        elseif ($op=='eq') {$whereClause.=' ('.$varName."='".$val."') ";}
        elseif ($op=='gt') {$whereClause.=' ('.$varName.">'".$val."') ";}
        elseif ($op=='rg') {
          $v0=substr($val,0,strpos($val,'_'));
          $v1=substr($val,strpos($val,'_')+1);
          $whereClause.=" ($varName>='$v0' AND $varName<='$v1') ";
        }
        else {$nought=0;}
    } elseif ($arEtype[$i]=='text') {
      $whereClause.=' ('.$varName." LIKE '%".$arSelx[$i]."%') ";
    } else {
      $whereClause.=' ('.$varName."='".$arSelx[$i]."') ";
    }
    $i++;
  }
  return $whereClause;
} //parseToWhere
  
function implodeAllOpts($a) {
  $opts=$a->getAllOpts();
  $strng='';
  foreach($opts AS $k=>$v) {
    $strng.="$k=$v;";
  }
  return $strng;
}
  
function vList() {
    //$mo_vlist=($_REQUEST['vlist']) ? explode(',',$_REQUEST['vlist']) : array();
    $vlist=$this->getListVars();
    //need database,table,varname,tableinfo for variable =
    // browse=varname;ctrl=whatever;titl=something;whatever other options
    $postStrng="method=useQonFly".
        '&db='.$this->dbName.
        '&tabl='.$this->tabl.
        '&index='.$this->indx;
    if ($vlist) {
      $postStrng.="&cmnd=qlist";
      $chStrng="document.getElementById('updBrowse').style.visibility='visible';";
      $chStrng.="jtAjaxLibSelect('$postStrng','varinfo','jtSpecs','1');";
      echo "<p><strong>Filter by</strong>\n<select id=\"varinfo\" name=\"varinfo\"";
      echo " />\n";
      echo "<option value=\"0\"> -- </option>\n";
      foreach ($vlist as $item) {
        $a=$this->xQueryInfo["$item"];
        $code="$item;ctrl=".$a->getSize().';titl='.$a->getLabel().';'.
            $this->implodeAllOpts($a);
        echo "<option value=\"$code\">",$a->getLabel(),"</option>\n";
      }
      echo "</select>\n";
      addButton('myChoice','Choose',$chStrng);      
      //echo "</p>\n";
    } //vlist not empty
  } 
  
function qList($var,$dyn) {
      //echo "<p>SQL:\n";
      $sql=$this->fetchQuery($var);
      //echo "$sql</p>\n";
      $xSet='XSet'.$dyn;
      echo "<p id=\"$xSet\"><strong>",$this->getVarTitle($var),"</strong>\n";
      hiddenInput('VarX'.$dyn,$var);
      if ($dyn>1) {
        radioInput('BoolX'.$dyn,'AND');
        echo "AND\n";
        radioInput('BoolX'.$dyn,'OR');
        echo "OR\n";
      }
    if ($sql) {
      $rX=$this->xQuery($sql,1);
      selectFromQuery($rX,'SelX'.$dyn,$this->getQParmName($var,'key'),'',
          $this->getQParmName($var,'var'),'','','');
      killResult($rX);
    } else {
      $arOpts=$this->getItemOpts($var);
      if ($arOpts['dated']) {
        
        $currTime=time();
        $dayOfWeek=date('N',$t);
        if ($dayOfWeek>6) {$dayOfWeek=0;}
        $Sunday=$currTime-24*3600*$dayOfWeek;
        $nextSunday=$currTime+(7-$dayOfWeek)*24*3600;
        $Saturday=6*24*3600+$Sunday;
        $nextSaturday=6*24*3600+$nextSunday;
        $thisMonth=date('Y-m-01',$currTime).'_'.date('Y-m-t',$currTime);
        $thisWeek=date('Y-m-d',$Sunday).'_'.date('Y-m-d',$Saturday);
        $nextWeek=date('Y-m-d',$nextSunday).'_'.date('Y-m-d',$nextSaturday);
        $today=date('Y-m-d');
        selectFromArray('SelX'.$dyn,
            array("__lt$today","__eq$today","__rg$thisWeek","__rg$nextWeek",
                "__rg$thisMonth","__gt$today"),
            '',array('Before today','Today','This week','Next week','This month','After today'),
            '','','');
      } elseif ($arOpts['specify']) {
        echo " like <input type=\"text\" id=\"SelX$dyn\" name =\"SelX$dyn\" size=\"16\" />\n";
      } else {
        selectFromArray('SelX'.$dyn,'','',explode('\t',$arOpts['values']),
          '','','');
      }
    }
    echo "<span style=\"color: #600;\" onClick=\"removeOpt('$dyn');\" >Scratch</span>\n";
    echo "</p>\n";
  } //qlist
  
};