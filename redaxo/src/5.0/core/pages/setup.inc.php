<?php

/**
 * Direkter Aufruf, um zu testen, ob der Ordner redaxo/src
 * erreichbar ist. Dies darf aus Sicherheitsgründen nicht möglich sein!
 */
if (!isset($REX))
{
	echo '<html>
          <title></title>
          <head>
            <script src="../../../../../assets/standard.js" type="text/javascript"></script>
            <script type="text/javascript">
              var needle = new parent.getObj("security_warning");
              var span = needle.obj;
              span.style.display="";
              var needle = new parent.getObj("nextstep");
              var span = needle.obj;
              span.style.display="none";
            </script>
          </head>
          <body></body>
        </html>';
	exit();
}


/**
 *
 * @package redaxo4
 * @version svn:$Id$
 */

// --------------------------------------------- SETUP FUNCTIONS

/**
 * Ausgabe des Setup spezifischen Titels
 */
function rex_setup_title($title)
{
	rex_title($title);

	echo '<div id="rex-setup" class="rex-area">';
}

function rex_setup_import($import_sql, $import_archiv = null)
{
	global $REX, $export_addon_dir;

	$err_msg = '';

	if (!is_dir($export_addon_dir))
	{
		$err_msg .= $REX['I18N']->msg('setup_03703').'<br />';
	}
	else
	{
		if (file_exists($import_sql) && ($import_archiv === null || $import_archiv !== null && file_exists($import_archiv)))
		{
			$REX['I18N']->appendFile($REX['INCLUDE_PATH'] .'/addons/import_export/lang/');

			// DB Import
			$state_db = rex_a1_import_db($import_sql);
			if ($state_db['state'] === false)
			{
				$err_msg .= nl2br($state_db['message']) .'<br />';
			}

			// Archiv optional importieren
			if ($state_db['state'] === true && $import_archiv !== null)
			{
				$state_archiv = rex_a1_import_files($import_archiv);
				if ($state_archiv['state'] === false)
				{
					$err_msg .= $state_archiv['message'].'<br />';
				}
			}
		}
		else
		{
			$err_msg .= $REX['I18N']->msg('setup_03702').'<br />';
		}
	}

	return $err_msg;
}

function rex_setup_is_writable($items)
{
	global $REX;
	$res = array();

	foreach($items as $item)
	{
		$is_writable = _rex_is_writable($item);

		// 0 => kein Fehler
		if($is_writable != 0)
		{
			$res[$is_writable][] = $item;
		}
	}

	return $res;
}

// -------------------------- System AddOns prüfen
function rex_setup_addons($uninstallBefore = false, $installDump = true)
{
	global $REX;

	require_once $REX['INCLUDE_PATH'].'/core/functions/function_rex_addons.inc.php';

	$addonErr = '';
	$ADDONS = rex_read_addons_folder();
	$addonManager = new rex_addonManager($ADDONS);
  if($uninstallBefore)
  {
    foreach(array_reverse($REX['SYSTEM_ADDONS']) as $systemAddon)
    {
    	$state = $addonManager->uninstall($systemAddon);

    	if($state !== true)
    	  $addonErr .= '<li>'. $systemAddon .'<ul><li>'. $state .'</li></ul></li>';
    }
  }
  foreach($REX['SYSTEM_ADDONS'] as $systemAddon)
  {
  	$state = true;

  	if($state === true && !rex_ooAddon::isInstalled($systemAddon))
  	  $state = $addonManager->install($systemAddon, $installDump);

  	if($state === true && !rex_ooAddon::isActivated($systemAddon))
  	  $state = $addonManager->activate($systemAddon);

  	if($state !== true)
  	  $addonErr .= '<li>'. $systemAddon .'<ul><li>'. $state .'</li></ul></li>';
  }

	if($addonErr != '')
	{
		$addonErr = '<ul class="rex-ul1">
                   <li>
                     <h3 class="rex-hl3">'. $REX['I18N']->msg('setup_011', '<span class="rex-error">', '</span>') .'</h3>
                     <ul>'. $addonErr .'</ul>
                   </li>
                 </ul>';
	}

	return $addonErr;
}

function rex_setup_setUtf8()
{
  global $REX;
  $gt = new rex_sql();
  $gt->setQuery("show tables");
  foreach($gt->getArray() as $t) {
    $table = $t["Tables_in_".$REX['DB']['1']['NAME']];
    $gc = new rex_sql();
    $gc->setQuery("show columns from $table");
    if(substr($table,0,strlen($REX['TABLE_PREFIX'])) == $REX['TABLE_PREFIX']) {
      $columns = Array();
      $pri = "";
      foreach($gc->getArray() as $c) {
        $columns[] = $c["Field"];
        if ($pri == "" && $c["Key"] == "PRI") {
          $pri = $c["Field"];
        }
      }
      if ($pri != "") {
        $gr = new rex_sql();
        $gr->setQuery("select * from $table");
        foreach($gr->getArray() as $r) {
          reset($columns);
          $privalue = $r[$pri];
          $uv = new rex_sql();
          $uv->setTable($table);
          $uv->setWhere($pri.'= "'.$privalue.'"');
          foreach($columns as $key => $column) {
            if ($pri!=$column) {
              $value = $r[$column];
              $newvalue = utf8_decode($value);
              $uv->setValue($column,addslashes($newvalue));
            }
          }
          $uv->update();
        }
      }
    }
  }
}

// --------------------------------------------- END: SETUP FUNCTIONS

// -- setup requirements
$min_version = '5.3.0';
$min_mysql_version = '5.0';
$min_php_extensions = array('session', 'mysql', 'pcre');
// -- /setup requirements

$MSG['err'] = "";
$err_msg = '';

$checkmodus = rex_request('checkmodus', 'float');
$send       = rex_request('send', 'string');
$dbanlegen  = rex_request('dbanlegen', 'string');
$noadmin    = rex_request('noadmin', 'string');
$lang       = rex_request('lang', 'string');

$export_addon_dir = $REX['INCLUDE_PATH'] .'/addons/import_export';
require_once $export_addon_dir.'/functions/function_folder.inc.php';
require_once $export_addon_dir.'/functions/function_import_folder.inc.php';
require_once $export_addon_dir.'/functions/function_import_export.inc.php';


// ---------------------------------- MODUS 0 | Start
if (!($checkmodus > 0 && $checkmodus < 10))
{
  // initial purge all generated files
  rex_deleteDir($REX['INCLUDE_PATH'].'/generated', FALSE);

  // copy alle media files of the current rex-version into redaxo_media
  rex_copyDir($REX['INCLUDE_PATH'] .'/media', $REX['OPENMEDIAFOLDER']);

	$langpath = $REX['INCLUDE_PATH'].'/core/lang';
	foreach($REX['LANGUAGES'] as $l)
	{
		$I18N_T = rex_create_lang($l,$langpath,FALSE);
		$label = $I18N_T->msg('lang');
		$langs[$l] = '<li><a href="index.php?checkmodus=0.5&amp;lang='.$l.'"'. rex_tabindex() .'>'.$label.'</a></li>';

	}
	unset($I18N_T);

	// wenn nur eine Sprache -> direkte weiterleitung
	if (count($REX['LANGUAGES'])==1)
	{
		header('Location: index.php?checkmodus=0.5&lang='.key($langs));
		exit();
	}

	rex_setup_title('SETUP: SELECT LANGUAGE');

	echo '<h2 class="rex-hl2">Please choose a language!</h2>
      <div class="rex-area-content">
        <ul class="rex-setup-language">'. implode('', $langs) .'</ul>
      </div>';
}

// ---------------------------------- MODUS 0 | Start

if ($checkmodus == '0.5')
{
	rex_setup_title('SETUP: START');

	$REX['LANG'] = $lang;

	echo $REX['I18N']->msg('setup_005', '<h2 class="rex-hl2">', '</h2>');
	echo '<div class="rex-area-content">';

	echo $REX['I18N']->msg('setup_005_1', '<h3 class="rex-hl3">', '</h3>', ' class="rex-ul1"');
	echo '<div class="rex-area-scroll">';

	$Basedir = dirname(__FILE__);
	$license_file = $Basedir.'/../../../../../_lizenz.txt';
	$license = '<p class="rex-tx1">'.nl2br(rex_get_file_contents($license_file)).'</p>';

	echo $license;

	echo '</div>
      </div>
      <div class="rex-area-footer">
        <p class="rex-algn-rght"><a href="index.php?page=setup&amp;checkmodus=1&amp;lang='.$lang.'"'. rex_tabindex() .'>&raquo; '.$REX['I18N']->msg("setup_006").'</a></p>
      </div>';

	$checkmodus = 0;
}

// ---------------------------------- MODUS 1 | Versionscheck - Rechtecheck

if ($checkmodus == 1)
{
  // -------------------------- VERSIONSCHECK
	if (version_compare(phpversion(), $min_version, '<') == 1)
	{
		$MSG['err'] .= '<li>'. $REX['I18N']->msg('setup_010', phpversion(), $min_version).'</li>';
	}

	// -------------------------- EXTENSION CHECK
	foreach($min_php_extensions as $extension)
	{
		if(!extension_loaded($extension))
		$MSG['err'] .= '<li>'. $REX['I18N']->msg('setup_010_1', $extension).'</li>';
	}

	// -------------------------- SCHREIBRECHTE
	$WRITEABLES = array (
		$REX['INCLUDE_PATH'] .'/config/master.inc.php',
		$REX['INCLUDE_PATH'] .'/config/addons.inc.php',
		$REX['INCLUDE_PATH'] .'/config/plugins.inc.php',
		$REX['INCLUDE_PATH'] .'/config/clang.inc.php',
		$REX['INCLUDE_PATH'] .'/generated',
		$REX['INCLUDE_PATH'] .'/generated/articles',
		$REX['INCLUDE_PATH'] .'/generated/templates',
		$REX['INCLUDE_PATH'] .'/generated/files',
		rex_path::media(),
		rex_path::media('_readme.txt'),
		getImportDir()
	);

	foreach($REX['SYSTEM_ADDONS'] as $system_addon)
	  $WRITEABLES[] = $REX['INCLUDE_PATH'] .'/addons'.DIRECTORY_SEPARATOR. $system_addon;

	$res = rex_setup_is_writable($WRITEABLES);
	if(count($res) > 0)
	{
		$MSG['err'] .= '<li>';
		foreach($res as $type => $messages)
		{
			if(count($messages) > 0)
			{
				$MSG['err'] .= '<h3 class="rex-hl3">'. _rex_is_writable_info($type) .'</h3>';
				$MSG['err'] .= '<ul>';
				foreach($messages as $message)
				{
					$MSG['err'] .= '<li>'. $message .'</li>';
				}
				$MSG['err'] .= '</ul>';
			}
		}
		$MSG['err'] .= '</li>';
	}
}

if ($MSG['err'] == '' && $checkmodus == 1)
{
	rex_setup_title($REX['I18N']->msg('setup_step1'));

	echo $REX['I18N']->msg('setup_016', '<h2 class="rex-hl2">', '</h2>');
	echo '<div class="rex-area-content">';

	echo $REX['I18N']->msg('setup_016_1', ' class="rex-ul1"', '<span class="rex-ok">', '</span>');
	echo '<div class="rex-message"><p class="rex-warning" id="security_warning" style="display: none;"><span>'. $REX['I18N']->msg('setup_security_msg') .'</span></p></div>
        <noscript><div class="rex-message"><p class="rex-warning"><span>'. $REX['I18N']->msg('setup_no_js_security_msg') .'</span></p></div></noscript>
        <iframe src="src/'.$REX['VERSION_FOLDER'].'/core/pages/setup.inc.php?page=setup&amp;checkmodus=1.5&amp;lang='.$lang.'" style="display: none;"></iframe>
     </div>
     <div class="rex-area-footer">
       <p id="nextstep" class="rex-algn-rght">
         <a href="index.php?page=setup&amp;checkmodus=2&amp;lang='.$lang.'"'. rex_tabindex() .'>&raquo; '.$REX['I18N']->msg('setup_017').'</a>
       </p>
     </div>';

}
elseif ($MSG['err'] != "")
{

	rex_setup_title($REX['I18N']->msg('setup_step1'));

	echo '<h2 class="rex-hl2">'.$REX['I18N']->msg('setup_headline1').'</h2>
      <div class="rex-area-content">
        <ul class="rex-ul1">'.$MSG['err'].'</ul>
        <p class="rex-tx1">'.$REX['I18N']->msg('setup_018').'</p>
      </div>
      <div class="rex-area-footer">
        <p class="rex-algn-rght">
          <a href="index.php?page=setup&amp;checkmodus=1&amp;lang='.$lang.'"'. rex_tabindex() .'>&raquo; '.$REX['I18N']->msg('setup_017').'</a>
        </p>
      </div>';
}

// ---------------------------------- MODUS 2 | master.inc.php - Datenbankcheck

if ($checkmodus == 2 && $send == 1)
{
	$master_file = $REX['INCLUDE_PATH'].'/config/master.inc.php';
	$cont = rex_get_file_contents($master_file);

	// Einfache quotes nicht escapen, da der String zwischen doppelten quotes stehen wird
	$serveraddress             = str_replace("\'", "'", rex_post('serveraddress', 'string'));
	$serverbezeichnung         = str_replace("\'", "'", rex_post('serverbezeichnung', 'string'));
	$error_email               = str_replace("\'", "'", rex_post('error_email', 'string'));
	$timezone                  = str_replace("\'", "'", rex_post('timezone', 'string'));
	$mysql_host                = str_replace("\'", "'", rex_post('mysql_host', 'string'));
	$redaxo_db_user_login      = str_replace("\'", "'", rex_post('redaxo_db_user_login', 'string'));
	$redaxo_db_user_pass       = str_replace("\'", "'", rex_post('redaxo_db_user_pass', 'string'));
	$dbname                    = str_replace("\'", "'", rex_post('dbname', 'string'));
	$redaxo_db_create          = rex_post('redaxo_db_create', 'boolean');

  // check if timezone is valid
	if(@date_default_timezone_set($timezone) === false)
	{
	  $err_msg = $REX['I18N']->msg('setup_invalid_timezone');
	}

	if($err_msg == '')
	{
  	$cont = preg_replace("@(REX\['SERVER'\].?\=.?\")[^\"]*@", '${1}'.$serveraddress, $cont);
  	$cont = preg_replace("@(REX\['SERVERNAME'\].?\=.?\")[^\"]*@", '${1}'.$serverbezeichnung, $cont);
  	$cont = preg_replace("@(REX\['LANG'\].?\=.?\")[^\"]*@", '${1}'.$lang, $cont);
  	$cont = preg_replace("@(REX\['INSTNAME'\].?\=.?\")[^\"]*@", '${1}'."rex".date("YmdHis"), $cont);
  	$cont = preg_replace("@(REX\['ERROR_EMAIL'\].?\=.?\")[^\"]*@", '${1}'.$error_email, $cont);
  	$cont = preg_replace("@(REX\['TIMEZONE'\].?\=.?\")[^\"]*@", '${1}'.$timezone, $cont);
  	$cont = preg_replace("@(REX\['DB'\]\['1'\]\['HOST'\].?\=.?\")[^\"]*@", '${1}'.$mysql_host, $cont);
  	$cont = preg_replace("@(REX\['DB'\]\['1'\]\['LOGIN'\].?\=.?\")[^\"]*@", '${1}'.$redaxo_db_user_login, $cont);
  	$cont = preg_replace("@(REX\['DB'\]\['1'\]\['PSW'\].?\=.?\")[^\"]*@", '${1}'.$redaxo_db_user_pass, $cont);
  	$cont = preg_replace("@(REX\['DB'\]\['1'\]\['NAME'\].?\=.?\")[^\"]*@", '${1}'.$dbname, $cont);

  	if(rex_put_file_contents($master_file, $cont) === false)
  	{
  		$err_msg = $REX['I18N']->msg('setup_020', '<b>', '</b>');
  	}
	}


	// -------------------------- DATENBANKZUGRIFF
	if($err_msg == '')
	{
		$err = rex_sql::checkDbConnection($mysql_host, $redaxo_db_user_login, $redaxo_db_user_pass, $dbname, $redaxo_db_create);
		if($err !== true)
		{
			$err_msg = $err;
		}
	}

	// -------------------------- MySQl VERSIONSCHECK
	if($err_msg == '')
	{
		$REX['DB']['1']['NAME'] = $dbname;
		$REX['DB']['1']['LOGIN'] = $redaxo_db_user_login;
		$REX['DB']['1']['PSW'] = $redaxo_db_user_pass;
		$REX['DB']['1']['HOST'] = $mysql_host;
		
	  $serverVersion = rex_sql::getServerVersion();
		if (rex_version_compare($serverVersion, $min_mysql_version, '<') == 1)
		{
			$err_msg = $REX['I18N']->msg('setup_022_1', $serverVersion, $min_mysql_version);
		}
	}

	// everything went fine, advance to the next setup step
	if($err_msg == '')
	{
		$checkmodus = 3;
		$send = "";
	}
}
else
{
	// Allgemeine Infos
	$serveraddress         = $REX['SERVER'];
	$serverbezeichnung     = $REX['SERVERNAME'];
	$error_email           = $REX['ERROR_EMAIL'];
	$timezone              = $REX['TIMEZONE'];

	// DB Infos
	$dbname                = $REX['DB']['1']['NAME'];
	$redaxo_db_user_login  = $REX['DB']['1']['LOGIN'];
	$redaxo_db_user_pass   = $REX['DB']['1']['PSW'];
	$mysql_host            = $REX['DB']['1']['HOST'];
}

if ($checkmodus == 2)
{
	rex_setup_title($REX['I18N']->msg('setup_step2'));

	echo '<h2 class="rex-hl2">'.$REX['I18N']->msg('setup_023').'</h2>
      <div class="rex-form" id="rex-form-setup-step-2">
      <form action="index.php" method="post">
      <fieldset class="rex-form-col-1">
        <input type="hidden" name="page" value="setup" />
        <input type="hidden" name="checkmodus" value="2" />
        <input type="hidden" name="send" value="1" />
        <input type="hidden" name="lang" value="'.$lang.'" />';

	if ($err_msg != '') {
		echo rex_warning($err_msg);
	}

	echo '
          <legend>'.$REX['I18N']->msg("setup_0201").'</legend>

          <div class="rex-form-wrapper">
            <div class="rex-form-row">
              <p class="rex-form-col-a rex-form-text">
                <label for="serveraddress">'.$REX['I18N']->msg("setup_024").'</label>
                <input class="rex-form-text" type="text" id="serveraddress" name="serveraddress" value="'.$serveraddress.'"'. rex_tabindex() .' />
              </p>
            </div>

            <div class="rex-form-row">
              <p class="rex-form-col-a rex-form-text">
                <label for="serverbezeichnung">'.$REX['I18N']->msg("setup_025").'</label>
                <input class="rex-form-text" type="text" id="serverbezeichnung" name="serverbezeichnung" value="'.$serverbezeichnung.'"'. rex_tabindex() .' />
              </p>
            </div>

            <div class="rex-form-row">
              <p class="rex-form-col-a rex-form-text">
                <label for="error_email">'.$REX['I18N']->msg("setup_026").'</label>
                <input class="rex-form-text" type="text" id="error_email" name="error_email" value="'.$error_email.'"'. rex_tabindex() .' />
              </p>
            </div>

            <div class="rex-form-row">
              <p class="rex-form-col-a rex-form-text">
                <label for="timezone">'.$REX['I18N']->msg("setup_timezone").'</label>
                <input class="rex-form-text" type="text" id="timezone" name="timezone" value="'.$timezone.'"'. rex_tabindex() .' />
                <span class="rex-form-notice">see <a href="http://php.net/timezones">http://php.net/timezones</a></span>
              </p>
            </div>
        </div>
        </fieldset>

        <fieldset class="rex-form-col-1">
          <legend>'.$REX['I18N']->msg("setup_0202").'</legend>
          <div class="rex-form-wrapper">
            <div class="rex-form-row">
              <p class="rex-form-col-a rex-form-text">
                <label for="dbname">'.$REX['I18N']->msg("setup_027").'</label>
                <input class="rex-form-text" type="text" value="'.$dbname.'" id="dbname" name="dbname"'. rex_tabindex() .' />
              </p>
            </div>

            <div class="rex-form-row">
              <p class="rex-form-col-a rex-form-text">
                <label for="mysql_host">MySQL Host</label>
                <input class="rex-form-text" type="text" id="mysql_host" name="mysql_host" value="'.$mysql_host.'"'. rex_tabindex() .' />
              </p>
            </div>

            <div class="rex-form-row">
              <p class="rex-form-col-a rex-form-text">
                <label for="redaxo_db_user_login">Login</label>
                <input class="rex-form-text" type="text" id="redaxo_db_user_login" name="redaxo_db_user_login" value="'.$redaxo_db_user_login.'"'. rex_tabindex() .' />
              </p>
            </div>

            <div class="rex-form-row">
              <p class="rex-form-col-a rex-form-text">
                <label for="redaxo_db_user_pass">'.$REX['I18N']->msg("setup_028").'</label>
                <input class="rex-form-text" type="text" id="redaxo_db_user_pass" name="redaxo_db_user_pass" value="'.$redaxo_db_user_pass.'"'. rex_tabindex() .' />
              </p>
            </div>

            <div class="rex-form-row">
              <p class="rex-form-col-a rex-form-checkbox">
                <label for="redaxo_db_create">'.$REX['I18N']->msg("setup_create_db").'</label>
                <input class="rex-form-checkbox" type="checkbox" id="redaxo_db_create" name="redaxo_db_create" value="1"'. rex_tabindex() .' />
              </p>
            </div>
          </div>
        </fieldset>

        <fieldset class="rex-form-col-1">
          <div class="rex-form-wrapper">
            <div class="rex-form-row">
              <p class="rex-form-col-a rex-form-submit">
                <input class="rex-form-submit" type="submit" value="'.$REX['I18N']->msg("setup_029").'"'. rex_tabindex() .' />
              </p>
            </div>

          </div>
        </fieldset>
      </form>
      </div>
      <script type="text/javascript">
         <!--
        jQuery(function($) {
          $("#serveraddress").focus();
        });
         //-->
      </script>';
}

// ---------------------------------- MODUS 3 | Datenbank anlegen ...

if ($checkmodus == 3 && $send == 1)
{
	$dbanlegen = rex_post('dbanlegen', 'int', '');

	// -------------------------- Benötigte Tabellen prüfen
	$requiredTables = array (
		$REX['TABLE_PREFIX'] .'action',
		$REX['TABLE_PREFIX'] .'article',
		$REX['TABLE_PREFIX'] .'article_slice',
		$REX['TABLE_PREFIX'] .'clang',
		$REX['TABLE_PREFIX'] .'file',
		$REX['TABLE_PREFIX'] .'file_category',
		$REX['TABLE_PREFIX'] .'module_action',
		$REX['TABLE_PREFIX'] .'module',
		$REX['TABLE_PREFIX'] .'template',
		$REX['TABLE_PREFIX'] .'user',
		$REX['TABLE_PREFIX'] .'user_role',
		$REX['TABLE_PREFIX'] .'config'
	);

	if ($dbanlegen == 4)
	{
		// ----- vorhandenen seite updaten
		$import_sql = $REX['INCLUDE_PATH'].'/core/install/update4_x_to_5_0.sql';
		if($err_msg == '')
		  $err_msg .= rex_setup_import($import_sql);

		// Aktuelle Daten updaten wenn utf8, da falsch in v4.2.1 abgelegt wurde.
		/*if (rex_lang_is_utf8())
    {
			rex_setup_setUtf8();
    }*/

		if($err_msg == '')
		  $err_msg .= rex_setup_addons();
	}
	elseif ($dbanlegen == 3)
	{
		// ----- vorhandenen Export importieren
		$import_name = rex_post('import_name', 'string');

		if($import_name == '')
		{
			$err_msg .= '<p>'.$REX['I18N']->msg('setup_03701').'</p>';
		}
		else
		{
			$import_sql = getImportDir().'/'.$import_name.'.sql';
			$import_archiv = getImportDir().'/'.$import_name.'.tar.gz';

			// Nur hier zuerst die Addons installieren
			// Da sonst Daten aus dem eingespielten Export
			// Überschrieben würden
			if($err_msg == '')
			  $err_msg .= rex_setup_addons(true, false);
			if($err_msg == '')
			  $err_msg .= rex_setup_import($import_sql, $import_archiv);
		}
	}
	elseif ($dbanlegen == 2)
	{
		// ----- db schon vorhanden, nichts tun
		$err_msg .= rex_setup_addons(true, false);
	}
	elseif ($dbanlegen == 1)
	{
		// ----- volle Datenbank, alte DB löschen / drop
		$import_sql = $REX['INCLUDE_PATH'].'/core/install/redaxo5_0.sql';

		$db = rex_sql::factory();
		foreach($requiredTables as $table)
		$db->setQuery('DROP TABLE IF EXISTS `'. $table .'`');

		if($err_msg == '')
		  $err_msg .= rex_setup_import($import_sql);

		if($err_msg == '')
		  $err_msg .= rex_setup_addons(true);
	}
	elseif ($dbanlegen == 0)
	{
		// ----- leere Datenbank neu einrichten
		$import_sql = $REX['INCLUDE_PATH'].'/core/install/redaxo5_0.sql';

		if($err_msg == '')
		  $err_msg .= rex_setup_import($import_sql);

		$err_msg .= rex_setup_addons();
	}

	if($err_msg == "" && $dbanlegen !== '')
	{
		// Prüfen, welche Tabellen bereits vorhanden sind
		$existingTables = array();
		foreach(rex_sql::showTables() as $tblname)
		{
			if (substr($tblname, 0, strlen($REX['TABLE_PREFIX'])) == $REX['TABLE_PREFIX'])
			{
				$existingTables[] = $tblname;
			}
		}

		foreach(array_diff($requiredTables, $existingTables) as $missingTable)
		{
			$err_msg .= $REX['I18N']->msg('setup_031', $missingTable)."<br />";
		}
	}

	if ($err_msg == "")
	{
		$send = "";
		$checkmodus = 4;
	}
}

if ($checkmodus == 3)
{
	$dbanlegen = rex_post('dbanlegen', 'int', '');
	rex_setup_title($REX['I18N']->msg('setup_step3'));

	echo '<div class="rex-form rex-form-setup-step-database">
      <form action="index.php" method="post">
      <fieldset class="rex-form-col-1">
        <input type="hidden" name="page" value="setup" />
        <input type="hidden" name="checkmodus" value="3" />
        <input type="hidden" name="send" value="1" />
        <input type="hidden" name="lang" value="'.$lang.'" />

        <legend>'.$REX['I18N']->msg('setup_030_headline').'</legend>
          <div class="rex-form-wrapper">
      ';

	if ($err_msg != '')
	  echo rex_warning($err_msg.'<br />'.$REX['I18N']->msg('setup_033'));

	$dbchecked = array_fill(0, 6, '');
	switch ($dbanlegen)
	{
		case 1 :
		case 2 :
		case 3 :
		case 4 :
			$dbchecked[$dbanlegen] = ' checked="checked"';
			break;
		default :
			$dbchecked[0] = ' checked="checked"';
	}

	// Vorhandene Exporte auslesen
	$sel_export = new rex_select();
	$sel_export->setName('import_name');
	$sel_export->setId('import_name');
	$sel_export->setStyle('class="rex-form-select"');
	$sel_export->setAttribute('onclick', 'checkInput(\'dbanlegen_3\')');
	$export_dir = getImportDir();
	$exports_found = false;

	if (is_dir($export_dir))
	{
		if ($handle = opendir($export_dir))
		{
			$export_archives = array ();
			$export_sqls = array ();

			while (($file = readdir($handle)) !== false)
			{
				if ($file == '.' || $file == '..')
				{
					continue;
				}

				$isSql = (substr($file, strlen($file) - 4) == '.sql');
				$isArchive = (substr($file, strlen($file) - 7) == '.tar.gz');

				if ($isSql)
				{
					// endung .sql abschneiden
					$export_sqls[] = substr($file, 0, -4);
					$exports_found = true;
				}
				elseif ($isArchive)
				{
					// endung .tar.gz abschneiden
					$export_archives[] = substr($file, 0, -7);
					$exports_found = true;
				}
			}
			closedir($handle);
		}

		foreach ($export_sqls as $sql_export)
		{
			// Es ist ein Export Archiv + SQL File vorhanden
			if (in_array($sql_export, $export_archives))
			{
				$sel_export->addOption($sql_export, $sql_export);
			}
		}
	}

	echo '

	<div class="rex-form-row">
		<p class="rex-form-col-a rex-form-radio rex-form-label-right">
      <input class="rex-form-radio" type="radio" id="dbanlegen_0" name="dbanlegen" value="0"'.$dbchecked[0]. rex_tabindex() .' />
      <label for="dbanlegen_0">'.$REX['I18N']->msg('setup_034').'</label>
    </p>
  </div>

	<div class="rex-form-row">
		<p class="rex-form-col-a rex-form-radio rex-form-label-right">
      <input class="rex-form-radio" type="radio" id="dbanlegen_1" name="dbanlegen" value="1"'.$dbchecked[1] .' />
      <label for="dbanlegen_1">'.$REX['I18N']->msg('setup_035', '<b>', '</b>').'</label>
    </p>
  </div>

	<div class="rex-form-row">
		<p class="rex-form-col-a rex-form-radio rex-form-label-right">
      <input class="rex-form-radio" type="radio" id="dbanlegen_2" name="dbanlegen" value="2"'.$dbchecked[2] .' />
      <label for="dbanlegen_2">'.$REX['I18N']->msg('setup_036').'</label>
    </p>
  </div>

	<div class="rex-form-row">
		<p class="rex-form-col-a rex-form-radio rex-form-label-right">
      <input class="rex-form-radio" type="radio" id="dbanlegen_4" name="dbanlegen" value="4"'.$dbchecked[4] .' />
      <label for="dbanlegen_4">'.$REX['I18N']->msg('setup_038').'</label>
    </p>
  </div>';

	if($exports_found)
	{
		echo '
	<div class="rex-form-row">
		<p class="rex-form-col-a rex-form-radio rex-form-label-right">
      <input class="rex-form-radio" type="radio" id="dbanlegen_3" name="dbanlegen" value="3"'.$dbchecked[3] .' />
      <label for="dbanlegen_3">'.$REX['I18N']->msg('setup_037').'</label>
    </p>
    <p class="rex-form-col-a rex-form-select rex-form-radio-select">'. $sel_export->get() .'</p>
  </div>';
	}

	echo '
    </div>
		</fieldset>
		<fieldset class="rex-form-col-1">
			<div class="rex-form-wrapper">
				<div class="rex-form-row">
					<p class="rex-form-col-a rex-form-submit">
						<input class="rex-form-submit" type="submit" value="'.$REX['I18N']->msg('setup_039').'"'. rex_tabindex() .' />
					</p>
				</div>
			</div>
		</fieldset>
</form>
</div>
';
}

// ---------------------------------- MODUS 4 | User anlegen ...

if ($checkmodus == 4 && $send == 1)
{
	$noadmin           = rex_post('noadmin', 'int');
	$redaxo_user_login = rex_post('redaxo_user_login', 'string');
	$redaxo_user_pass  = rex_post('redaxo_user_pass', 'string');

	$err_msg = "";
	if ($noadmin != 1)
	{
		if ($redaxo_user_login == '')
		{
			$err_msg .= $REX['I18N']->msg('setup_040');
		}

		if ($redaxo_user_pass == '')
		{
			// Falls auch kein Login eingegeben wurde, die Fehlermeldungen mit " " trennen
			if($err_msg != '') $err_msg .= ' ';

			$err_msg .= $REX['I18N']->msg('setup_041');
		}

		if ($err_msg == "")
		{
			$ga = rex_sql::factory();
			$ga->setQuery("select * from ".$REX['TABLE_PREFIX']."user where login='$redaxo_user_login'");

			if ($ga->getRows() > 0)
			{
				$err_msg .= $REX['I18N']->msg('setup_042');
			}
			else
			{
        // the service side encryption of pw is only required
        // when not already encrypted by client using javascript
        if ($REX['PSWFUNC'] != '' && rex_post('javascript') == '0')
					$redaxo_user_pass = call_user_func($REX['PSWFUNC'], $redaxo_user_pass);

				$user = rex_sql::factory();
				// $user->debugsql = true;
				$user->setTable($REX['TABLE_PREFIX'].'user');
				$user->setValue('name', 'Administrator');
				$user->setValue('login', $redaxo_user_login);
				$user->setValue('psw', $redaxo_user_pass);
				$user->setValue('rights', '#admin[]#');
				$user->addGlobalCreateFields('setup');
				$user->setValue('status', '1');
				if (!$user->insert())
				{
					$err_msg .= $REX['I18N']->msg("setup_043");
				}
			}
		}
	}
	else
	{
		$gu = rex_sql::factory();
		$gu->setQuery("select * from ".$REX['TABLE_PREFIX']."user LIMIT 1");
		if ($gu->getRows() == 0)
		$err_msg .= $REX['I18N']->msg('setup_044');
	}

	if ($err_msg == '')
	{
		$checkmodus = 5;
		$send = '';
	}
}

if ($checkmodus == 4)
{
	$user_sql = rex_sql::factory();
	$user_sql->setQuery("select * from ".$REX['TABLE_PREFIX']."user LIMIT 1");

	rex_setup_title($REX['I18N']->msg("setup_step4"));

	echo '
	<div class="rex-form rex-form-setup-admin">
  <form action="index.php" method="post" autocomplete="off" id="createadminform">
  	<input type="hidden" name="javascript" value="0" id="javascript" />
    <fieldset class="rex-form-col-1">
      <input type="hidden" name="page" value="setup" />
      <input type="hidden" name="checkmodus" value="4" />
      <input type="hidden" name="send" value="1" />
      <input type="hidden" name="lang" value="'.$lang.'" />
      <legend>'.$REX['I18N']->msg("setup_045").'</legend>
      <div class="rex-form-wrapper">
      ';

	if ($err_msg != "")
	  echo rex_warning($err_msg);

	$redaxo_user_login = rex_post('redaxo_user_login', 'string');
	$redaxo_user_pass  = rex_post('redaxo_user_pass', 'string');

	echo '
  	<div class="rex-form-row">
	    <p class="rex-form-col-a rex-form-text">
        <label for="redaxo_user_login">'.$REX['I18N']->msg("setup_046").':</label>
        <input class="rex-form-text" type="text" value="'.$redaxo_user_login.'" id="redaxo_user_login" name="redaxo_user_login"'. rex_tabindex() .'/>
      </p>
    </div>
  	<div class="rex-form-row">
	    <p class="rex-form-col-a rex-form-text">
        <label for="redaxo_user_pass">'.$REX['I18N']->msg("setup_047").':</label>
        <input class="rex-form-text" type="password" value="'.$redaxo_user_pass.'" id="redaxo_user_pass" name="redaxo_user_pass"'. rex_tabindex() .'/>
      </p>
    </div>';

	if($user_sql->getRows() > 0)
	{
		echo '
  	<div class="rex-form-row">
	    <p class="rex-form-col-a rex-form-checkbox rex-form-label-right">
        <input class="rex-form-checkbox" type="checkbox" id="noadmin" name="noadmin" value="1"'. rex_tabindex() .'/>
        <label for="noadmin">'.$REX['I18N']->msg("setup_048").'</label>
      </p>
    </div>';
	}

	echo '
    </div>
    </fieldset>
    <fieldset class="rex-form-col-1">
      <div class="rex-form-wrapper">
        <div class="rex-form-row">
          <p class="rex-form-col-a rex-form-submit">
            <input class="rex-form-submit" type="submit" value="'.$REX['I18N']->msg("setup_049").'"'. rex_tabindex() .' />
          </p>
        </div>
      </div>
    </fieldset>
  </form>
  </div>
  <script type="text/javascript">
     <!--
    jQuery(function($) {
      $("#redaxo_user_login").focus();
      $("#createadminform")
        .submit(function(){
        	var pwInp = $("#redaxo_user_pass");
        	if(pwInp.val() != "")
        	{
          	pwInp.val(Sha1.hash(pwInp.val()));
        	}
      });

      $("#javascript").val("1");
    });
   //-->
  </script>';
}

// ---------------------------------- MODUS 5 | Setup verschieben ...

if ($checkmodus == 5)
{
	$master_file = $REX['INCLUDE_PATH'].'/config/master.inc.php';
	$cont = rex_get_file_contents($master_file);
	$cont = preg_replace("@(REX\['SETUP'\].?\=.?)[^;]*@", '$1false', $cont);

	if(rex_put_file_contents($master_file, $cont))
	{
		$errmsg = "";
	}
	else
	{
		$errmsg = $REX['I18N']->msg('setup_050');
	}

	rex_setup_title($REX['I18N']->msg('setup_step5'));
	echo $REX['I18N']->msg('setup_051', '<h2 class="rex-hl2">', '</h2>');
	echo '<div class="rex-area-content">';
	echo $REX['I18N']->msg('setup_052', '<h3 class="rex-hl3">', '</h3>', ' class="rex-ul1"', '<a href="index.php">', '</a>');
	echo '<p class="rex-tx1">'.$REX['I18N']->msg('setup_053').'</p>';
	echo '</div>';

}
echo '</div>';