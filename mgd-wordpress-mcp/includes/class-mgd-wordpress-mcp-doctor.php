<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class MGD_WordPress_MCP_Doctor {
 public static function checks(){
  $adapter=class_exists('\\WP\\MCP\\Core\\McpAdapter');
  $abilities=function_exists('wp_register_ability');
  $permalinks=(string)get_option('permalink_structure','');
  $locks=MGD_WordPress_MCP_Environment::frontend_locks();
  return array(
   'wordpress'=>array('ok'=>version_compare(get_bloginfo('version'),'6.9','>='),'label'=>'WordPress '.get_bloginfo('version')),
   'php'=>array('ok'=>version_compare(PHP_VERSION,'7.4','>='),'label'=>'PHP '.PHP_VERSION),
   'https'=>array('ok'=>is_ssl(),'label'=>is_ssl()?'HTTPS aktiv':'HTTPS nicht erkannt'),
   'rest'=>array('ok'=>!empty(rest_url()),'label'=>'REST API URL verfügbar'),
   'permalinks'=>array('ok'=>''!==$permalinks,'label'=>''!==$permalinks?'Pretty Permalinks aktiv':'Standard-Permalinks erkannt'),
   'abilities'=>array('ok'=>$abilities,'label'=>$abilities?'Abilities API verfügbar':'Abilities API fehlt'),
   'adapter'=>array('ok'=>$adapter,'label'=>$adapter?'MCP Adapter verfügbar':'MCP Adapter fehlt'),
   'frontend'=>array('ok'=>empty($locks['potentially_locked']),'warning'=>!empty($locks['potentially_locked']),'label'=>empty($locks['potentially_locked'])?'Kein Frontend-Schutz erkannt':'Möglicher Frontend-Schutz erkannt'),
   'endpoint'=>array('ok'=>$adapter&&$abilities,'label'=>MGD_WordPress_MCP::adapter_endpoint_url()),
  );
 }
 public static function summary(){ $c=self::checks();$ok=0;foreach($c as $v){if(!empty($v['ok'])){$ok++;}}return array('passed'=>$ok,'total'=>count($c),'checks'=>$c); }
}
