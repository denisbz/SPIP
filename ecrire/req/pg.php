<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

define('_DEFAULT_DB', 'spip');

// Se connecte et retourne le nom de la fonction a connexion persistante
// A la premiere connexion de l'installation (BD pas precisee)
// si on ne peut se connecter sans la preciser
// on reessaye avec le login comme nom de BD
// et si ca marche toujours pas, avec "spip" (constante ci-dessus)
// si ca ne marche toujours pas, echec.

// http://doc.spip.org/@base_db_pg_dist
function req_pg_dist($addr, $port, $login, $pass, $db='', $prefixe='', $ldap='') {
	static $last_connect = array();
	
	// si provient de selectdb
	if (empty($addr) && empty($port) && empty($login) && empty($pass)){
		foreach (array('addr','port','login','pass','prefixe','ldap') as $a){
			$$a = $last_connect[$a];
		}
	}
	@list($host, $p) = split(';', $addr);
	if ($p >0) $port = " port=$p" ; else $port = '';
	if ($db) {
		@$link = pg_connect("host=$host$port dbname=$db user=$login password=$pass", PGSQL_CONNECT_FORCE_NEW);
	} elseif (!@$link = pg_connect("host=$host$port user=$login password=$pass", PGSQL_CONNECT_FORCE_NEW)) {
	    if (@$link = pg_connect("host=$host$port dbname=$login user=$login password=$pass", PGSQL_CONNECT_FORCE_NEW)) {
	      $db = $login;
	    } else {
	      $db = _DEFAULT_DB;
	      $link = pg_connect("host=$host$port dbname=$db user=$login password=$pass", PGSQL_CONNECT_FORCE_NEW);
	    }
	}

	if ($link)
		$last_connect = array (
			'addr' => $addr,
			'port' => $port,
			'login' => $login,
			'pass' => $pass,
			'db' => $db,
			'prefixe' => $prefixe,
			'ldap' => $ldap
		);
		
#	spip_log("Connexion vers $host, base $db, prefixe $prefixe "
#		 . ($link ? 'operationnelle' : 'impossible'));

	return !$link ? false : array(
		'db' => $db,
		'prefixe' => $prefixe ? $prefixe : $db,
		'link' => $link,
		'ldap' => $ldap
		);
}

$GLOBALS['spip_pg_functions_1'] = array(
		'alter' => 'spip_pg_alter',
		'count' => 'spip_pg_count',
		'countsel' => 'spip_pg_countsel',
		'create' => 'spip_pg_create',
		'delete' => 'spip_pg_delete',
		'drop_table' => 'spip_pg_drop_table',
		'errno' => 'spip_pg_errno',
		'error' => 'spip_pg_error',
		'explain' => 'spip_pg_explain',
		'fetch' => 'spip_pg_fetch',
		'free' => 'spip_pg_free',
		'hex' => 'spip_pg_hex',
		'in' => 'spip_pg_in',
		'insert' => 'spip_pg_insert',
		'insertq' => 'spip_pg_insertq',
		'listdbs' => 'spip_pg_listdbs',
		'multi' => 'spip_pg_multi',
		'query' => 'spip_pg_query',
		'quote' => 'spip_pg_quote',
		'replace' => 'spip_pg_replace',
		'select' => 'spip_pg_select',
		'selectdb' => 'spip_pg_selectdb',
		'set_connect_charset' => 'spip_pg_set_connect_charset',
		'showbase' => 'spip_pg_showbase',
		'showtable' => 'spip_pg_showtable',
		'update' => 'spip_pg_update',
		'updateq' => 'spip_pg_updateq',
		);

// Par ou ca passe une fois les traductions faites
// http://doc.spip.org/@spip_pg_trace_query
function spip_pg_trace_query($query, $serveur='')
{
	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	$t = !isset($_GET['var_profile']) ? 0 : trace_query_start();
	$r = pg_query($link, $query);

	if ($e = spip_pg_errno())	// Log de l'erreur eventuelle
		$e .= spip_pg_error($query); // et du fautif
	return $t ? trace_query_end($query, $t, $r, $e, $serveur) : $r;
}

// Fonction de requete generale quand on est sur que c'est SQL standard.
// Elle change juste le noms des tables ($table_prefix) dans le FROM etc

// http://doc.spip.org/@spip_pg_query
function spip_pg_query($query, $serveur='')
{
	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	if (preg_match('/\s(SET|VALUES|WHERE)\s/i', $query, $regs)) {
		$suite = strstr($query, $regs[0]);
		$query = substr($query, 0, -strlen($suite));
	} else $suite ='';
	$query = preg_replace('/([,\s])spip_/', '\1'.$prefixe.'_', $query) . $suite;
	return spip_pg_trace_query($query, $serveur);
}

// Alter en PG ne traite pas les index
// http://doc.spip.org/@spip_pg_alter
function spip_pg_alter($query, $serveur='') {

	if (!preg_match('/^\s*(IGNORE\s*)?TABLE\s+(\w+)\s+(ADD|DROP|CHANGE)\s*([^,]*)(.*)$/is', $query, $r)) {
	  spip_log("$query incompris", 'pg');
	} else {
	  if ($r[1]) spip_log("j'ignore IGNORE dans $query", 'pg');
	  $f = 'spip_pg_alter_' . strtolower($r[3]);
	  if (function_exists($f))
	    $f($r[2], $r[4], $serveur);
	  else spip_log("$query non prevu", 'pg');
	}
	// Alter a plusieurs args. Faudrait optimiser.
	if ($r[5])
	  spip_pg_alter("TABLE " . $r[2] . substr($r[5],1));
}
	      
// http://doc.spip.org/@spip_pg_alter_change
function spip_pg_alter_change($table, $arg, $serveur='')
{
	if (!preg_match('/^`?(\w+)`?\s+`?(\w+)`?\s+(.*?)\s*(DEFAULT .*?)?(NOT\s+NULL)?\s*(DEFAULT .*?)?$/i',$arg, $r)) {
	  spip_log("alter change: $arg  incompris", 'pg');
	} else {
	  list(,$old, $new, $type, $default, $null, $def2) = $r;
	  $actions = array("ALTER $old TYPE " . mysql2pg_type($type));
	  if ($null)
	    $actions[]= "ALTER $old SET NOT NULL";
	  else
	    $actions[]= "ALTER $old DROP NOT NULL";

	  if ($d = ($default ? $default : $def2))
	    $actions[]= "ALTER $old SET $d";
	  else
	    $actions[]= "ALTER $old DROP DEFAULT";

	  spip_pg_query("ALTER TABLE $table " . join(', ', $actions));

	  if ($old != $new)
	    spip_pg_query("ALTER TABLE $table RENAME $old TO $new", $serveur);
	}
}

// http://doc.spip.org/@spip_pg_alter_add
function spip_pg_alter_add($table, $arg, $serveur='') {
	if (!preg_match('/^(INDEX|KEY|PRIMARY\s+KEY|)\s*(.*)$/', $arg, $r)) {
		spip_log("alter add $arg  incompris", 'pg');
		return NULL;
	}
	if (!$r[1]) {
		preg_match('/`?(\w+)`?(.*)/',$r[2], $m);
		return spip_pg_query("ALTER TABLE $table ADD " . $m[1] . ' ' . mysql2pg_type($m[2]),  $serveur);
	} elseif ($r[1][0] == 'P') {
		preg_match('/\(`?(\w+)`?\)/',$r[2], $m);
		return spip_pg_query("ALTER TABLE $table ADD CONSTRAINT $table" .'_pkey PRIMARY KEY (' . $m[1] . ')', $serveur);
	} else {
		preg_match('/`?(\w+)`?\s*(\([^)]*\))/',$r[2], $m);
		return spip_pg_query("CREATE INDEX " . $table . '_' . $m[1] . " ON $table " . str_replace("`","",$m[2]),  $serveur);
	}
}

// http://doc.spip.org/@spip_pg_alter_drop
function spip_pg_alter_drop($table, $arg, $serveur='') {
	if (!preg_match('/^(INDEX|KEY|PRIMARY\s+KEY|)\s*`?(\w*)`?/', $arg, $r))
	  spip_log("alter drop: $arg  incompris", 'pg');
	else {
	    if (!$r[1])
	      return spip_pg_query("ALTER TABLE $table DROP " . $r[2],  $serveur);
	    elseif ($r[1][0] == 'P')
	      return spip_pg_query("ALTER TABLE $table DROP CONSTRAINT $table" . '_pkey', $serveur);
	    else {
		return spip_pg_query("DROP INDEX " . $table . '_' . $r[2],  $serveur);
	    }
	}
}

// http://doc.spip.org/@spip_pg_explain
function spip_pg_explain($query, $serveur=''){
	if (strpos(ltrim($query), 'SELECT') !== 0) return array();
	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	if (preg_match('/\s(SET|VALUES|WHERE)\s/i', $query, $regs)) {
		$suite = strstr($query, $regs[0]);
		$query = substr($query, 0, -strlen($suite));
	} else $suite ='';
	$query = 'EXPLAIN ' . preg_replace('/([,\s])spip_/', '\1'.$prefixe.'_', $query) . $suite;

	$r = pg_query($link,$query);
	return spip_pg_fetch($r, NULL, $serveur);
}

// http://doc.spip.org/@spip_pg_selectdb
function spip_pg_selectdb($db, $serveur='') {
	// se connecter a la base indiquee
	// avec les identifiants connus
	$index = $serveur ? $serveur : 0;

	if ($link = spip_connect_db('', '', '', '', $db, 'pg', '', '')){
		if (($db==$link['db']) && $GLOBALS['connexions'][$index] = $link)
			return $db;					
	} else
		return false;
}

// Qu'une seule base pour le moment

// http://doc.spip.org/@spip_pg_listdbs
function spip_pg_listdbs() {
	return pg_query("select * from pg_database");
}

// http://doc.spip.org/@spip_pg_select
function spip_pg_select($select, $from, $where='',
			$groupby=array(), $orderby='', $limit='',
                           $having='', $serveur=''){

	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	$limit = preg_match("/^\s*(([0-9]+),)?\s*([0-9]+)\s*$/", $limit,$limatch);
	if ($limit) {
		$offset = $limatch[2];
		$count = $limatch[3];
	}

	$select = spip_pg_frommysql($select);

	$orderby = spip_pg_orderby($orderby, $select);

	if ($having) {
	  if (is_array($having))
	    $having = join("\n\tAND ", array_map('calculer_pg_where', $having));
	}
	$from =  spip_pg_from($from, $prefixe);
	$q =  "SELECT ". $select
	  . (!$from ? '' : "\nFROM $from")
	  . (!$where ? '' : ("\nWHERE " . (!is_array($where) ? calculer_pg_where($where) : (join("\n\tAND ", array_map('calculer_pg_where', $where))))))
	  . spip_pg_groupby($groupby, $from, $select)
	  . (!$having ? '' : "\nHAVING $having")
	  . ($orderby ? ("\nORDER BY $orderby") :'')
	  . (!$limit ? '' : (" LIMIT $count" . (!$offset ? '' : " OFFSET $offset")));

	// Erreur ? C'est du debug, ou une erreur du serveur
	// il faudrait mettre ici le d�clenchement du message SQL
	// actuellement dans erreur_requete_boucle

	if ($GLOBALS['var_mode'] == 'debug') {
		include_spip('public/debug');
		boucle_debug_requete($q);
	}

	if (!($res = spip_pg_trace_query($q, $serveur))) {
	  include_spip('public/debug');
	  erreur_requete_boucle($q, 0, 0);
	}

	return $res;
}

// Le traitement des prefixes de table dans un Select se limite au FROM
// car le reste de la requete utilise les alias (AS) systematiquement

// http://doc.spip.org/@spip_pg_from
function spip_pg_from($from, $prefixe)
{
	if (is_array($from)) $from = spip_pg_select_as($from);
	return !$prefixe ? $from : preg_replace('/(\b)spip_/','\1'.$prefixe.'_', $from);
}

// http://doc.spip.org/@spip_pg_orderby
function spip_pg_orderby($order, $select)
{
	$res = array();
	$arg = (is_array($order) ?  $order : preg_split('/\s*,\s*/',$order));

	foreach($arg as $v) {
		if (preg_match('/(case\s+.*?else\s+0\s+end)\s*AS\s+' . $v .'/', $select, $m)) {

		  $res[] = $m[1];
		} else $res[]=$v;
	}
	return spip_pg_frommysql(join(',',$res));
}

// Conversion a l'arrach' des jointures MySQL en jointures PG
// A refaire pour tirer parti des possibilites de PG et de MySQL5
// et pour enlever les repetitions (sans incidence de perf, mais ca fait sale)

// http://doc.spip.org/@spip_pg_groupby
function spip_pg_groupby($groupby, $from, $select)
{
	$join = strpos($from, ",");
	if ($join OR $groupby) $join = !is_array($select) ? $select : join(", ", $select);
	if ($join) {
	  $join = str_replace('DISTINCT ','',$join);
	  // fct SQL sur colonne et constante apostrophee ==> la colonne
	  $join = preg_replace('/\w+\(\s*([^(),\']*),\s*\'[^\']*\'[^)]*\)/','\\1', $join);
	  $join = preg_replace('/CAST\(\s*([^(),\' ]*\s+)as\s*\w+\)/','\\1', $join);
	  // resultat d'agregat ne sont pas a mettre dans le groupby
	  $join = preg_replace('/(SUM|COUNT|MAX|MIN|UPPER)\([^)]+\)(\s*AS\s+\w+)\s*,?/i','', $join);
	  // idem sans AS (fetch numerique)
	  $join = preg_replace('/(SUM|COUNT|MAX|MIN|UPPER)\([^)]+\)\s*,?/i','', $join);
	  // ne reste plus que les vrais colonnes, et parfois 1 virgule

	  if (preg_match('/^(.*),\s*$/',$join,$m)) $join=$m[1];
	}
	if (is_array($groupby)) $groupby = join(',',$groupby);
	if ($join) $groupby = $groupby ? "$groupby, $join" : $join;
	if (!$groupby) return '';

	$groupby = spip_pg_frommysql($groupby);
	$groupby = preg_replace('/\s+AS\s+\w+\s*/','', $groupby);

	return "\nGROUP BY $groupby"; 
}

// Conversion des operateurs MySQL en PG
// IMPORTANT: "0+X" est vu comme conversion numerique du debut de X 
// Les expressions de date ne sont pas gerees au-dela de 3 ()
// Le 'as' du 'CAST' est en minuscule pour echapper au dernier preg_replace
// de spip_pg_groupby.
// A ameliorer.

// http://doc.spip.org/@spip_pg_frommysql
function spip_pg_frommysql($arg)
{
	if (is_array($arg)) $arg = join(", ", $arg);

	$res = spip_pg_fromfield($arg);

	$res = preg_replace('/\brand[(][)]/i','random()', $res);

	$res = preg_replace('/\b0\.0[+]([a-zA-Z0-9_.]+)\s*/',
			    'CAST(substring(\1, \'^ *[0-9.]+\') as float)',
			    $res);
	$res = preg_replace('/\b0[+]([a-zA-Z0-9_.]+)\s*/',
			    'CAST(substring(\1, \'^ *[0-9]+\') as int)',
			    $res);
	$res = preg_replace('/\bconv[(]([^,]*)[^)]*[)]/i',
			    'CAST(substring(\1, \'^ *[0-9]+\') as int)',
			    $res);
	$res = preg_replace('/UNIX_TIMESTAMP\s*[(]\s*[)]/',
			    ' EXTRACT(epoch FROM NOW())', $res);

	$res = preg_replace('/UNIX_TIMESTAMP\s*[(]([^)]*)[)]/',
			    ' EXTRACT(epoch FROM \1)', $res);


	$res = preg_replace('/\bDAYOFMONTH\s*[(]([^()]*([(][^()]*[)][^()]*)*[^)]*)[)]/',
			    ' EXTRACT(day FROM \1)',
			    $res);

	$res = preg_replace('/\bMONTH\s*[(]([^()]*([(][^)]*[)][^()]*)*[^)]*)[)]/',
			    ' EXTRACT(month FROM \1)',
			    $res);

	$res = preg_replace('/\bYEAR\s*[(]([^()]*([(][^)]*[)][^()]*)*[^)]*)[)]/',
			    ' EXTRACT(year FROM \1)',
			    $res);

	$res = preg_replace('/TO_DAYS\s*[(]([^()]*([(][^)]*[)][()]*)*)[)]/',
			    ' EXTRACT(day FROM \1 - \'0000-01-01\')',
			    $res);

	$res = preg_replace("/(EXTRACT[(][^ ]* FROM *)\"([^\"]*)\"/", '\1\'\2\'', $res);
	$res = preg_replace('/DATE_FORMAT\s*[(]([^,]*),\s*\'%Y%m%d\'[)]/', 'to_number(to_char(\1, \'YYYYMMDD\'), \'8\')', $res);
	$res = preg_replace('/DATE_FORMAT\s*[(]([^,]*),\s*\'%Y%m\'[)]/', 'to_number(to_char(\1, \'YYYYMM\'),\'6\')', $res);
	$res = preg_replace('/DATE_SUB\s*[(]([^,]*),/', '(\1 -', $res);
	$res = preg_replace('/DATE_ADD\s*[(]([^,]*),/', '(\1 +', $res);
	$res = preg_replace('/INTERVAL\s+(\d+\s+\w+)/', 'INTERVAL \'\1\'', $res);
	$res = preg_replace('/([+<>-]=?)\s*(\'\d+-\d+-\d+\s+\d+:\d+(:\d+)\')/', '\1 timestamp \2', $res);
	$res = preg_replace('/(\'\d+-\d+-\d+\s+\d+:\d+:\d+\')\s*([+<>-]=?)/', 'timestamp \1 \2', $res);

	$res = preg_replace('/([+<>-]=?)\s*(\'\d+-\d+-\d+\')/', '\1 timestamp \2', $res);
	$res = preg_replace('/(\'\d+-\d+-\d+\')\s*([+<>-]=?)/', 'timestamp \1 \2', $res);

	$res = preg_replace('/(timestamp .\d+)-00-/','\1-01-', $res);
	$res = preg_replace('/(timestamp .\d+-\d+)-00/','\1-01',$res);
	$res = preg_replace("/(EXTRACT[(][^ ]* FROM *)(timestamp *'[^']*' *[+-] *timestamp *'[^']*') *[)]/", '\2', $res);
	$res = preg_replace("/(EXTRACT[(][^ ]* FROM *)('[^']*')/", '\1 timestamp \2', $res);

	return str_replace('REGEXP', '~', $res);
}

// http://doc.spip.org/@spip_pg_fromfield
function spip_pg_fromfield($arg)
{
	while(preg_match('/^(.*?)FIELD\s*\(([^,]*)((,[^,)]*)*)\)/', $arg, $m)) {

		preg_match_all('/,([^,]*)/', $m[3], $r, PREG_PATTERN_ORDER);
		$res = '';
		$n=0;
		$index = $m[2];
		foreach($r[1] as $v) {
			$n++;
			$res .= "\nwhen $index=$v then $n";
		}
		$arg = $m[1] . "case $res else 0 end "
		  . substr($arg,strlen($m[0]));
	}
	return $arg;
}

// http://doc.spip.org/@calculer_pg_where
function calculer_pg_where($v)
{
	if (!is_array($v))
		return spip_pg_frommysql($v);

	$op = str_replace('REGEXP', '~', array_shift($v));
	if (!($n=count($v)))
		return $op;
	else {
		$arg = calculer_pg_where(array_shift($v));
		if ($n==1) {
			  return "$op($arg)";
		} else {
			$arg2 = calculer_pg_where(array_shift($v));
			if ($n==2) {
				return "($arg $op $arg2)";
			} else return "($arg $op ($arg2) : $v[0])";
		}
	}
}


function calculer_pg_expression($expression, $v, $join = 'AND'){
	if (empty($v))
		return '';
	
	$exp = "\n$expression ";
	
	if (!is_array($v)) 
		$v = array($v);
	
	if (strtoupper($expression) === 'WHERE')
		$v = array_map('spip_pg_frommysql', $v);
	
	if (!empty($v)) {
		if (strtoupper($join) === 'AND')
			return $exp . join("\n\t$join ", array_map('calculer_pg_where', $v));
		else
			return $exp . join($join, $v);
	}
}

// http://doc.spip.org/@spip_pg_select_as
function spip_pg_select_as($args)
{
	$argsas = "";
        foreach($args as $k => $v) {
		$as = '';
		if (!is_numeric($k)) {
			if (preg_match('/\.(.*)$/', $k, $r))
				$v = $k;
			elseif ($v != $k) $as = " AS $k"; 
		}
		if (strpos($v, 'JOIN') === false)  $argsas .= ', ';
		$argsas .= $v . $as; 
	}
	return substr($argsas,2);
}

// http://doc.spip.org/@spip_pg_fetch
function spip_pg_fetch($res, $t='', $serveur='') {

	if ($res) $res = pg_fetch_array($res, NULL, PGSQL_ASSOC);
	return $res;
}
 
// http://doc.spip.org/@spip_pg_countsel
function spip_pg_countsel($from = array(), $where = array(),
			  $groupby=array(), $limit='', $having = array(), $serveur='')
{
	$r = spip_pg_select('COUNT(*)', $from, $where,
			    $groupby, '', $limit, $sousrequete, $having, '','', $serveur);
	if ($r) list($r) = pg_fetch_array($r, NULL, PGSQL_NUM);
	return $r;
}

// http://doc.spip.org/@spip_pg_count
function spip_pg_count($res, $serveur='') {
	return !$res ? 0 : pg_numrows($res);
}
  
// http://doc.spip.org/@spip_pg_free
function spip_pg_free($res, $serveur='') {
  // rien � faire en postgres
}

// http://doc.spip.org/@spip_pg_delete
function spip_pg_delete($table, $where='', $serveur='') {

	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];
	if ($prefixe) $table = preg_replace('/^spip/', $prefixe, $table);
	return spip_pg_trace_query(
			  calculer_pg_expression('DELETE FROM', $table, ',')
			. calculer_pg_expression('WHERE', $where, 'AND'), 
			$serveur);
}

// http://doc.spip.org/@spip_pg_insert
function spip_pg_insert($table, $champs, $valeurs, $desc=array(), $serveur='') {
	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	if (!$desc) $desc = description_table($table);
	$seq = spip_pg_sequence($table);

	if ($prefixe) {
		$table = preg_replace('/^spip/', $prefixe, $table);
		$seq = preg_replace('/^spip/', $prefixe, $seq);
	}
	$ret = !$seq ? '' : (" RETURNING currval('$seq')");
	$ins = (strlen($champs)<3)
	  ? " DEFAULT VALUES"
	  : "$champs VALUES $valeurs";
	$r = pg_query($link, $q="INSERT INTO $table $ins $ret");
#	spip_log($q);
	if ($r) {
		if (!$ret) return 0;
		if ($r2 = pg_fetch_array($r, NULL, PGSQL_NUM))
			return $r2[0];
	}
	$n = spip_pg_errno();
	$m = spip_pg_error($q);
	spip_log("$n $m $q '$r' '$r2'", 'pg'); // trace a minima
	return -1;
}

// http://doc.spip.org/@spip_pg_insertq
function spip_pg_insertq($table, $couples=array(), $desc=array(), $serveur='') {

	if (!$desc) $desc = description_table($table);
	if (!$desc) die("$table insertion sans description");
	$fields =  $desc['field'];
		
	foreach ($couples as $champ => $val) {
		$couples[$champ]=  spip_pg_cite($val, $fields[$champ]);
	}

	return spip_pg_insert($table, "(".join(',',array_keys($couples)).")", "(".join(',', $couples).")", $desc, $serveur);
}

// http://doc.spip.org/@spip_pg_update
function spip_pg_update($table, $champs, $where='', $desc='', $serveur='') {

	if (!$champs) return;
	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];
	if ($prefixe) $table = preg_replace('/^spip/', $prefixe, $table);
	$set = array();
	foreach ($champs as $champ => $val) {
		$set[] = $champ . '=' . $val; 
	}

	return spip_pg_trace_query(
		  calculer_pg_expression('UPDATE', $table, ',')
		. calculer_pg_expression('SET', $set, ',')
		. calculer_pg_expression('WHERE', $where, 'AND'), 
		$serveur);
}

// idem, mais les valeurs sont des constantes a mettre entre apostrophes
// sauf les expressions de date lorsqu'il s'agit de fonctions SQL (NOW etc)
// http://doc.spip.org/@spip_pg_updateq
function spip_pg_updateq($table, $champs, $where='', $desc=array(), $serveur='') {
	if (!$champs) return;
	if (!$desc) $desc = description_table($table);
	$fields = $desc['field'];
	foreach ($champs as $k => $val) {
		$champs[$k] = spip_pg_cite($val, $fields[$k]);
	}

	return spip_pg_update($table, $champs, $where, $desc, $serveur);
}


// http://doc.spip.org/@spip_pg_replace
function spip_pg_replace($table, $values, $desc, $serveur='') {

	if (!$values) {spip_log("replace vide $table"); return 0;}
	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];

	if (!$desc) $desc = description_table($table);
	if (!$desc) die("$table insertion sans description");
	$prim = $desc['key']['PRIMARY KEY'];
	$ids = preg_split('/,\s*/', $prim);
	$noprims = $prims = array();
	foreach($values as $k=>$v) {
		$values[$k] = $v = spip_pg_cite($v, $desc['field'][$k]);

		if (!in_array($k, $ids))
			$noprims[$k]= "$k=$v";
		else $prims[$k]= "$k=$v";
	}

	$where = join(' AND ', $prims);
	if (!$where) {
		return spip_pg_insert($table, "(".join(',',array_keys($values)).")", "(".join(',', $values).")", $desc, $serveur);
	}
	$couples = join(',', $noprims);

	$seq = spip_pg_sequence($table);
	if ($prefixe) {
		$table = preg_replace('/^spip/', $prefixe, $table);
		$seq = preg_replace('/^spip/', $prefixe, $seq);
	}

	if ($couples) {
	  $couples = pg_query($link, $q = "UPDATE $table SET $couples WHERE $where");
#	  spip_log($q);
	  if (!$couples) {
	    $n = spip_pg_errno();
	    $m = spip_pg_error($q);
	  } else {
	    $couples = pg_affected_rows($couples);
	  }
	}
	if (!$couples) {
		$ret = !$seq ? '' :
		  (" RETURNING nextval('$seq') < $prim");

		$couples = pg_query($link, $q = "INSERT INTO $table (" . join(',',array_keys($values)) . ') VALUES (' .join(',', $values) . ")$ret");
	    if (!$couples) {
	      $n = spip_pg_errno();
	      $m = spip_pg_error($q);
	    } elseif ($ret) {
	      $r = pg_fetch_array($couples, NULL, PGSQL_NUM);
	      if ($r[0]) {
		$q = "SELECT setval('$seq', $prim) from $table";
		// Le code de SPIP met parfois la sequence a 0 (dans l'import)
		// MySQL n'en dit rien, on fait pareil pour PG
		$r = @pg_query($link, $q);
	      }
	    }
	}

	return $couples;
}

// Donne la sequence eventuelle associee a une table 
// Pas extensible pour le moment,

// http://doc.spip.org/@spip_pg_sequence
function spip_pg_sequence($table)
{
	global $tables_principales;
	include_spip('base/serial');
	if (!isset($tables_principales[$table])) return false;
	$desc = $tables_principales[$table];
	$prim = @$desc['key']['PRIMARY KEY'];
	if (!preg_match('/^\w+$/', $prim)
	OR strpos($desc['field'][$prim], 'int') === false)
		return '';
	else  {	return $table . '_' . $prim . "_seq";}
}

// Explicite les conversions de Mysql d'une valeur $v de type $t
// Dans le cas d'un champ date, pas d'apostrophe, c'est une syntaxe ad hoc

// http://doc.spip.org/@spip_pg_cite
function spip_pg_cite($v, $t)
{
	if (sql_test_date($t)) {
		if (strpos("0123456789", $v[0]) === false)
			return spip_pg_frommysql($v);
		else {
			if (strpos($v, "-00-00") === 4)
				$v = substr($v,0,4)."-01-01".substr($v,10);
			return "timestamp '$v'";
		}
	}
	elseif (!sql_test_int($t))
		return   ("'" . addslashes($v) . "'");
	elseif (is_numeric($v) OR (strpos($v, 'CAST(') === 0))
		return $v;
	elseif ($v[0]== '0' AND $v[1]!=='x' AND  ctype_xdigit(substr($v,1)))
		return  substr($v,1);
	else {
		spip_log("Warning: '$v'  n'est pas de type $t", 'pg');
		return intval($v);
	}
}

function spip_pg_hex($v)
{
	return "CAST(x'" . $v . "' as bigint)";
}

function spip_pg_quote($v)
{
	return _q($v);
}

// http://doc.spip.org/@calcul_pg_in
function spip_pg_in($val, $valeurs, $not='', $serveur) {
//
// IN (...) souvent limite a 255  elements, d'ou cette fonction assistante
//
	if (strpos($valeurs, "CAST(x'") !== false)
		return "($val=" . join("OR $val=", explode(',',$valeurs)).')';
	$n = $i = 0;
	$in_sql ="";
	while ($n = strpos($valeurs, ',', $n+1)) {
	  if ((++$i) >= 255) {
			$in_sql .= "($val $not IN (" .
			  substr($valeurs, 0, $n) .
			  "))\n" .
			  ($not ? "AND\t" : "OR\t");
			$valeurs = substr($valeurs, $n+1);
			$i = $n = 0;
		}
	}
	$in_sql .= "($val $not IN ($valeurs))";

	return "($in_sql)";
}

// http://doc.spip.org/@spip_pg_error
function spip_pg_error($query, $serveur='') {
	$s = str_replace('ERROR', 'errcode: 1000 ', pg_last_error());
	if ($s) spip_log("$s - $query", 'pg');
	return $s;
}

// http://doc.spip.org/@spip_pg_errno
function spip_pg_errno($serveur='') {
	$s = pg_last_error(); 
	if ($s) spip_log("Erreur PG $s");
	return $s ? 1 : 0;
}

// http://doc.spip.org/@spip_pg_drop_table
function spip_pg_drop_table($table, $exist='', $serveur='')
{
	if ($exist) $exist =" IF EXISTS";
	return spip_pg_query("DROP TABLE$exist $table", $serveur);
}

// http://doc.spip.org/@spip_pg_showbase
function spip_pg_showbase($match, $serveur='')
{
	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$link = $connexion['link'];
	  
	return pg_query($link, "SELECT tablename FROM pg_tables WHERE tablename ILIKE '$match'");
}

// http://doc.spip.org/@spip_pg_showtable
function spip_pg_showtable($nom_table, $serveur='')
{
	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$link = $connexion['link'];

	$res = pg_query($link, "SELECT column_name, column_default, data_type FROM information_schema.columns WHERE table_name ILIKE " . _q($nom_table));

	if (!$res) return false;

	$fields = array();
	while($field = pg_fetch_array($res, NULL, PGSQL_NUM)) {
		$fields[$field[0]] = $field[2] . (!$field[1] ? '' : (" DEFAULT " . $field[1]));
	}

	$res = pg_query($link, "SELECT indexdef FROM pg_indexes WHERE tablename ILIKE " . _q($nom_table));
	$keys = array();
	while($index = pg_fetch_array($res, NULL, PGSQL_NUM)) {
		if (preg_match('/CREATE\s+(UNIQUE\s+)?INDEX.*\((.*)\)$/',
			       $index[0],$r)) {
			$index = split(',', $r[2]);
			$keys[($r[1] ? "PRIMARY KEY" : ("KEY " . $index[0]))] = 
			  $r[2];
		}
	}
	return array('field' => $fields, 'key' => $keys);
}

// Fonction de creation d'une table SQL nommee $nom
// a partir de 2 tableaux PHP :
// champs: champ => type
// cles: type-de-cle => champ(s)
// si $autoinc, c'est une auto-increment (i.e. serial) sur la Primary Key
// Le nom des index est prefixe par celui de la table pour eviter les conflits
// http://doc.spip.org/@spip_pg_create
function spip_pg_create($nom, $champs, $cles, $autoinc=false, $temporary=false, $serveur='') {

	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];
	$link = $connexion['link'];
	$db = $connexion['db'];
	if ($prefixe) $nom = preg_replace('/^spip/', $prefixe, $nom);
	$query = $prim = $prim_name = $v = $s = $p='';
	$keys = array();

	// certains plugins declarent les tables  (permet leur inclusion dans le dump)
	// sans les renseigner (laisse le compilo recuperer la description)
	if (!is_array($champs) || !is_array($cles)) 
		return;

	foreach($cles as $k => $v) {
		if (strpos($k, "KEY ") === 0) {
		  $n = str_replace('`','',$k);	
		  $v = str_replace('`','"',$v);	
		  $i = $nom . preg_replace("/KEY +/", '_',$n);
		  if ($k != $n) $i = "\"$i\"";
		  $keys[] = "CREATE INDEX $i ON $nom ($v);";
		} else $prim .= "$s\n\t\t" . str_replace('`','"',$k) ." ($v)";
		if ($k == "PRIMARY KEY")
			$prim_name = $v;
		$s = ",";
	}
	$s = '';
	
	$character_set = "";
	if (@$GLOBALS['meta']['charset_sql_base'])
		$character_set .= " CHARACTER SET ".$GLOBALS['meta']['charset_sql_base'];
	if (@$GLOBALS['meta']['charset_collation_sql_base'])
		$character_set .= " COLLATE ".$GLOBALS['meta']['charset_collation_sql_base'];

	foreach($champs as $k => $v) {
		$k = str_replace('`','"',$k);
		if (preg_match(',([a-z]*\s*(\(\s*[0-9]*\s*\))?(\s*binary)?),i',$v,$defs)){
			if (preg_match(',(char|text),i',$defs[1]) AND !preg_match(',binary,i',$defs[1]) ){
				$v = $defs[1] . $character_set . ' ' . substr($v,strlen($defs[1]));
			}
		}

		$query .= "$s\n\t\t$k "
			. (($autoinc && ($prim_name == $k) && preg_match(',\b(big)?int\b,i', $v))
				? " bigserial"
			   : mysql2pg_type($v)
			);
		$s = ",";
	}
	$temporary = $temporary ? 'TEMPORARY':'';

	// En l'absence de "if not exists" en PG, on neutralise les erreurs

	$q = "CREATE $temporary TABLE $nom ($query" . ($prim ? ",$prim" : '') . ")".
	($character_set?" DEFAULT $character_set":"")
	."\n";

	$r = @pg_query($link, $q);

	if (!$r)
		spip_log("creation de la table $nom impossible (deja la ?)");
	else {
		foreach($keys as $index) {pg_query($link, $index);}
	} 
	return $r;
}

// http://doc.spip.org/@spip_pg_set_connect_charset
function spip_pg_set_connect_charset($charset, $serveur=''){
	spip_log("changement de charset sql a ecrire en PG");
}

// Selectionner la sous-chaine dans $objet
// correspondant a $lang. Cf balise Multi de Spip

// http://doc.spip.org/@spip_pg_multi
function spip_pg_multi ($objet, $lang) {
	$r = "regexp_replace("
	  . $objet
	  . ",'<multi>.*[[]"
	  . $lang
	  . "[]]([^[]*).*</multi>', E'\\\\1') AS multi";
	return $r;
}

// Palanquee d'idiosyncrasies MySQL dans les creations de table
// A completer par les autres, mais essayer de reduire en amont.

// http://doc.spip.org/@mysql2pg_type
function mysql2pg_type($v)
{
  return     preg_replace('/bigint\s*[(]\s*\d+\s*[)]/i', 'bigint', 
		preg_replace('/int\s*[(]\s*\d+\s*[)]/i', 'int', 
		preg_replace("/longtext/i", 'text',
		str_replace("mediumtext", 'text',
		preg_replace("/tinytext/i", 'text',
	  	str_replace("longblob", 'text',
		str_replace("0000-00-00",'0000-01-01',
		preg_replace("/datetime/i", 'timestamp',
		preg_replace("/unsigned/i", '', 	
		preg_replace("/double/i", 'double precision', 	
		preg_replace("/tinyint/i", 'int', 	
		preg_replace("/VARCHAR\(\d+\)\s+BINARY/i", 'bytea', 
		preg_replace("/ENUM *[(][^)]*[)]/i", "varchar(255)",
					      $v 
			     )))))))))))));
}

?>
