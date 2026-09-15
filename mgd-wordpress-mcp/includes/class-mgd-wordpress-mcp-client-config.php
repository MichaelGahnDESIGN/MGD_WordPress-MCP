<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class MGD_WordPress_MCP_Client_Config {
 public static function configs(){
  $url=MGD_WordPress_MCP::adapter_endpoint_url();
  return array(
   'claude-code'=>"claude mcp add --transport http wordpress ".esc_url_raw($url)." --header \"Authorization: Basic <APPLICATION_PASSWORD_AUTH>\"",
   'codex'=>"codex mcp add wordpress --url ".esc_url_raw($url),
   'generic'=>wp_json_encode(array('mcpServers'=>array('wordpress'=>array('type'=>'http','url'=>$url,'headers'=>array('Authorization'=>'Basic <APPLICATION_PASSWORD_AUTH>')))),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),
  );
 }
}
