<?php

 class Installer extends Application{

  function __construct($args){
   // Searching for a method name and calling either it or default method
   if(is_array($args) && count($args) && method_exists($this, strtolower($args[0]).'Action')){
	call_user_func_array(array($this, strtolower($args[0]).'Action'), array_slice($args, 1));
   }else{
	$this->defaultAction();
   }
  }
   
  function defaultAction(){
   echo '<html><head><title>Administrator Area</title><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><base href="'.$directory.'" /><link rel=stylesheet href=st.css></head><body style="background-color:#FFFFFF;"><br><br><br><br><br><br><br><br><center>';
    open_table('Error');
	if(file_exists('settings.php') && !is_writable('settings.php')){
	 echo '<p>Not able to connect to database.</p><p>Unfortunately settings.php file is not writable, so installer script can not run.</p>';
	}elseif(!file_exists('settings.php') && !is_writable('.')){
	 echo '<p>Not able to connect to database.</p><p>Unfortunately settings.php file does not exist and current directory is not writable, so installer script can not run.</p>';
	}else{
	 echo '<p>Not able to connect to database. Do you want to run installer script?</p><div align="center"><input type="button" class="bu" value="Run" onclick="location.href = \''.$this->getUrl('installer/run').'\'" /></div>';
	}    
    close_table();
	echo '</body></html>';  
  }
  
  function runAction(){
   global $directory, $admPassword, $adminEmail, $defaultFrom, $dbHost, $dbUser, $dbUser, $dbPassword, $dbName, $prefix;
   
   echo '<html><head><title>Administrator Area</title><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><base href="'.$directory.'" /><link rel=stylesheet href=st.css></head><body style="background-color:#FFFFFF;"><br><br><br><br><br><br><br><br><center>';

    open_table('Installer');
    echo '<table width=355 cellpadding=4 cellspacing=2><form method=post action="'.$this->getUrl('installer/save').'">
            <tr><td align=right><b>Administator Password:</b></td><td style="padding-top:2px;padding-bottom:2px;"><input type=password name=admPassword size=30 value="'.$admPassword.'"></td></tr>
            <tr><td align=right><b>Administrator E-Mail:</b></td><td style="padding-top:2px;padding-bottom:2px;"><input name=adminEmail size=30 value="'.$adminEmail.'"></td></tr>
            <tr><td align=right><b>Default From Address:</b></td><td style="padding-top:2px;padding-bottom:2px;"><input name=defaultFrom size=30 value="'.$defaultFrom.'"></td></tr>
            <tr><td align=right><b>MySQL Server:</b></td><td style="padding-top:2px;padding-bottom:2px;"><input type=text name=dbHost value="'.$dbHost.'" size=30></td></tr>
            <tr><td align=right><b>MySQL Login:</b></td><td style="padding-top:2px;padding-bottom:2px;"><input type=text name=dbUser value="'.$dbUser.'" size=30></td></tr>
            <tr><td align=right><b>MySQL Password:</b></td><td style="padding-top:2px;padding-bottom:2px;"><input type=password name=dbPassword value="'.$dbPassword.'" size=30></td></tr>
            <tr><td align=right><b>MySQL Database:</b></td><td style="padding-top:2px;padding-bottom:2px;"><input type=text name=dbName value="'.$dbName.'" size=30></td></tr>
            <tr><td align=right><b>Database Prefix:</b></td><td style="padding-top:2px;padding-bottom:2px;"><input type=text name=prefix value="'.$prefix.'" size=30></td></tr>
            <tr><td></td><td><input type=submit value="Save Settings" class=bu></td></tr>
           </form></table>';
    close_table();
	echo '</body></html>';
  }

  function saveAction(){
   global $directory;
   
   if(count($_POST) == 8){
    $rd = @mysql_connect($_POST['dbHost'], $_POST['dbUser'], $_POST['dbPassword']);
    if(@mysql_select_db($_POST['dbName'], $rd)){
     $query = '

DROP TABLE IF EXISTS '.$_POST['prefix'].'project;
CREATE TABLE IF NOT EXISTS '.$_POST['prefix'].'project (
  project_id int(10) unsigned NOT NULL AUTO_INCREMENT,
  project_name varchar(255) NOT NULL,
  PRIMARY KEY (project_id)
);

DROP TABLE IF EXISTS '.$_POST['prefix'].'project_to_query;
CREATE TABLE IF NOT EXISTS '.$_POST['prefix'].'project_to_query (
  project_id int(10) unsigned NOT NULL,
  query_id int(10) unsigned NOT NULL,
  UNIQUE KEY project_id (project_id,query_id)
);

DROP TABLE IF EXISTS '.$_POST['prefix'].'query;
CREATE TABLE IF NOT EXISTS '.$_POST['prefix'].'query (
  query_id int(10) unsigned NOT NULL AUTO_INCREMENT,
  query_q varchar(255) NOT NULL,
  query_lang char(2) NOT NULL,
  query_geocode varchar(255) NOT NULL,
  PRIMARY KEY (query_id)
);

DROP TABLE IF EXISTS '.$_POST['prefix'].'search;
CREATE TABLE IF NOT EXISTS '.$_POST['prefix'].'search (
  search_id int(10) unsigned NOT NULL AUTO_INCREMENT,
  query_id int(10) unsigned NOT NULL,
  PRIMARY KEY (search_id)
);

DROP TABLE IF EXISTS '.$_POST['prefix'].'search_entity;
CREATE TABLE IF NOT EXISTS '.$_POST['prefix'].'search_entity (
  search_entity_id int(10) unsigned NOT NULL AUTO_INCREMENT,
  search_id int(10) unsigned NOT NULL,
  search_entity_name varchar(255) NOT NULL,
  search_entity_value text NOT NULL,
  PRIMARY KEY (search_entity_id)
);

';
     $queries = preg_split("/;+(?=([^'|^\\\']*['|\\\'][^'|^\\\']*['|\\\'])*[^'|^\\\']*[^'|^\\\']$)/", $query);	 
     foreach ($queries as $query){ 
      if (strlen(trim($query)) > 0){
	   mysql_query($query); 
	  }
     } 
     $fp = fopen('settings.php', 'w+');
     fputs($fp, "<?php\n\n");
     while(list($var, $val) = each($_POST))
      fputs($fp, ' $'.$var." = '".$val."';\n");
     fputs($fp, "\n\n?>");
     fclose($fp);
	 
	 header('Location: '.$this->getUrl());
    }else{
     echo '<html><head><title>Administrator Area</title><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><base href="'.$directory.'" /><link rel=stylesheet href=st.css></head><body style="background-color:#FFFFFF;"><br><br><br><br><br><br><br><br><center>';
     open_table('Error');	
	 echo '<p>Not able to connect to database.</p><div align="center"><input type="button" class="bu" value="Try Again?" onclick="location.href = \''.$this->getUrl('installer/run').'\'" /></div>';
	 close_table();
	 echo '</body></html>';
    }
   }   
  }
  
 }

?>