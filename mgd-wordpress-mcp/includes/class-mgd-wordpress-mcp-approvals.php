<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class MGD_WordPress_MCP_Approvals {
 const TTL=300;
 public static function issue($action,$object=''){
  if(!current_user_can('manage_options')){return new WP_Error('mgd_forbidden','Keine Berechtigung.',array('status'=>403));}
  $token=wp_generate_password(48,false,false);$hash=hash('sha256',$token);$data=array('action'=>sanitize_key($action),'object'=>sanitize_text_field((string)$object),'user_id'=>get_current_user_id(),'expires'=>time()+self::TTL);
  set_transient('mgd_wpmcp_approval_'.$hash,$data,self::TTL);return array('token'=>$token,'expires_in'=>self::TTL,'action'=>$data['action'],'object'=>$data['object']);
 }
 public static function consume($token,$action,$object=''){
  if(!is_string($token)||strlen($token)<20){return false;}$key='mgd_wpmcp_approval_'.hash('sha256',$token);$data=get_transient($key);delete_transient($key);
  if(!is_array($data)||time()>absint($data['expires']??0)){return false;}
  return hash_equals((string)$data['action'],sanitize_key($action))&&hash_equals((string)$data['object'],sanitize_text_field((string)$object))&&absint($data['user_id']??0)===get_current_user_id();
 }
}
