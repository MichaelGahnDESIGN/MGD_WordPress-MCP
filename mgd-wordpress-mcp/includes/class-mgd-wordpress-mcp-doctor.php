<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class MGD_WordPress_MCP_Doctor {
 private static function endpoint_probe(){
  $url=MGD_WordPress_MCP::adapter_endpoint_url();
  $response=wp_safe_remote_get($url,array('timeout'=>8,'redirection'=>2,'headers'=>array('Accept'=>'application/json','Cache-Control'=>'no-cache','User-Agent'=>'MGD-WordPress-MCP-Doctor/'.MGD_WPMCP_VERSION)));
  if(is_wp_error($response)){return array('ok'=>false,'code'=>0,'detail'=>'Endpoint nicht erreichbar: '.$response->get_error_message());}
  $code=(int)wp_remote_retrieve_response_code($response);
  $headers=wp_remote_retrieve_headers($response);
  $cache='';
  foreach(array('x-litespeed-cache','x-cache','cf-cache-status','x-fastcgi-cache') as $name){if(isset($headers[$name])){$cache=$name.': '.$headers[$name];break;}}
  $expected=in_array($code,array(400,401,403,405),true);
  $detail='HTTP '.$code.'. '.($expected?'Endpoint antwortet und schützt unauthentifizierte Anfragen.':'Unerwartete Antwort, vor MCP-Test prüfen.');
  if($cache){$detail.=' Cache-Header erkannt ('.$cache.'). MCP-Route vom Full-Page-Cache ausschließen.';}
  return array('ok'=>$expected,'code'=>$code,'detail'=>$detail,'cache'=>$cache);
 }
 public static function checks(){
  $adapter=class_exists('\\WP\\MCP\\Core\\McpAdapter');$abilities=function_exists('wp_register_ability');$permalinks=(string)get_option('permalink_structure','');$locks=MGD_WordPress_MCP_Environment::frontend_locks();$probe=($adapter&&$abilities)?self::endpoint_probe():array('ok'=>false,'detail'=>'Endpoint kann erst geprüft werden, wenn Adapter und Abilities API verfügbar sind.','cache'=>'');
  return array(
   'wordpress'=>array('ok'=>version_compare(get_bloginfo('version'),'6.9','>='),'label'=>'WordPress '.get_bloginfo('version'),'detail'=>'WordPress 6.9 oder neuer erforderlich.'),
   'php'=>array('ok'=>version_compare(PHP_VERSION,'7.4','>='),'label'=>'PHP '.PHP_VERSION,'detail'=>'PHP 7.4 oder neuer erforderlich.'),
   'https'=>array('ok'=>is_ssl(),'label'=>is_ssl()?'HTTPS aktiv':'HTTPS nicht erkannt','detail'=>is_ssl()?'Transport ist TLS-geschützt.':'Remote MCP sollte ausschließlich über HTTPS verwendet werden.'),
   'rest'=>array('ok'=>!empty(rest_url()),'label'=>'REST API URL verfügbar','detail'=>rest_url()),
   'permalinks'=>array('ok'=>''!==$permalinks,'label'=>''!==$permalinks?'Pretty Permalinks aktiv':'Standard-Permalinks erkannt','detail'=>''!==$permalinks?'REST-Routing ist vorbereitet.':'Permalinks für zuverlässiges REST-Routing konfigurieren.'),
   'abilities'=>array('ok'=>$abilities,'label'=>$abilities?'Abilities API verfügbar':'Abilities API fehlt','detail'=>'WordPress Abilities bilden die Werkzeugschicht.'),
   'adapter'=>array('ok'=>$adapter,'label'=>$adapter?'MCP Adapter verfügbar':'MCP Adapter fehlt','detail'=>'Offizieller WordPress MCP Adapter.'),
   'server'=>array('ok'=>$adapter&&$abilities,'label'=>$adapter&&$abilities?'MGD MCP Server kann registriert werden':'MGD MCP Server nicht bereit','detail'=>MGD_WordPress_MCP::adapter_endpoint_url()),
   'endpoint'=>array('ok'=>!empty($probe['ok']),'label'=>!empty($probe['ok'])?'MCP Endpoint antwortet':'MCP Endpoint nicht bestätigt','detail'=>$probe['detail']),
   'frontend'=>array('ok'=>true,'warning'=>!empty($locks['potentially_locked']),'label'=>empty($locks['potentially_locked'])?'Kein Frontend-Schutz erkannt':'Frontend-Schutz erkannt','detail'=>empty($locks['potentially_locked'])?'Keine bekannte Frontend-Sperre erkannt.':'Für MCP nicht automatisch ein Fehler. Kann visuelle Browser-Prüfungen blockieren.'),
  );
 }
 public static function summary(){ $c=self::checks();$ok=0;$warnings=0;foreach($c as $v){if(!empty($v['ok']))$ok++;if(!empty($v['warning']))$warnings++;}return array('passed'=>$ok,'total'=>count($c),'warnings'=>$warnings,'ready'=>$ok===count($c),'checks'=>$c,'summary'=>$ok.' von '.count($c).' technischen Prüfungen erfolgreich'.($warnings?' · '.$warnings.' Hinweis(e)':'').' · Authentifizierung und MCP-Handshake werden anschließend im Client getestet.'); }
}
