<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class MGD_WordPress_MCP_Security {
 private static $instance=null;
 public static function instance(){if(null===self::$instance){self::$instance=new self();}return self::$instance;}
 private function __construct(){add_action('wp_abilities_api_init',array($this,'harden_abilities'),1000);}
 public function harden_abilities(){ $ability='mgd-wordpress-mcp/upload-media-base64';if(function_exists('wp_has_ability')&&function_exists('wp_unregister_ability')&&wp_has_ability($ability)){wp_unregister_ability($ability);} }
 public static function profile(){return sanitize_key((string)MGD_WordPress_MCP::setting('permission_profile','read_only'));}
 public static function can_write(){return 'read_only'!==self::profile()&&MGD_WordPress_MCP::setting('writes_enabled',false)&&current_user_can('edit_posts');}
 public static function can_admin(){return 'admin'===self::profile()&&MGD_WordPress_MCP::setting('maintenance_enabled',false)&&current_user_can('update_plugins');}
 public static function rate_limit($bucket='default',$limit=120,$window=60){
  $uid=get_current_user_id();$key='mgd_wpmcp_rate_'.md5($uid.'|'.$bucket);$data=get_transient($key);if(!is_array($data)){$data=array('count'=>0,'reset'=>time()+$window);}if(time()>$data['reset']){$data=array('count'=>0,'reset'=>time()+$window);}$data['count']++;set_transient($key,$data,$window);return $data['count']<=$limit;
 }
 public static function redact($value){
  if(is_array($value)){foreach($value as $k=>$v){if(preg_match('/pass|password|token|secret|authorization|api[_-]?key|pin/i',(string)$k)){$value[$k]='[REDACTED]';}else{$value[$k]=self::redact($v);}}return $value;}return $value;
 }
 public static function filter_mcp_tools($tools){
  $disabled=array('mgd-wordpress-mcp/upload-media-base64');
  if('read_only'===self::profile()){foreach((array)$tools as $tool){if(preg_match('/create|update|delete|save|set-featured|import-media|backup|plugin-update|theme-update/i',$tool)){$disabled[]=$tool;}}}
  return array_values(array_diff((array)$tools,array_unique($disabled)));
 }
}
