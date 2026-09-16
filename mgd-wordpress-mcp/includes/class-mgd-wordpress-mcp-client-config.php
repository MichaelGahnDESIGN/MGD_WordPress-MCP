<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class MGD_WordPress_MCP_Client_Config {
 public static function configs(){
  $url=MGD_WordPress_MCP::adapter_endpoint_url();
  $proxy=array(
   'command'=>'npx',
   'args'=>array('-y','@automattic/mcp-wordpress-remote@latest'),
   'env'=>array(
    'WP_API_URL'=>$url,
    'WP_API_USERNAME'=>'<WORDPRESS_USERNAME>',
    'WP_API_PASSWORD'=>'<APPLICATION_PASSWORD>',
   ),
  );
  return array(
   'claude-code'=>wp_json_encode(array('mcpServers'=>array('mgd-wordpress'=> $proxy)),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),
   'codex'=>wp_json_encode(array('mcpServers'=>array('mgd-wordpress'=> $proxy)),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),
   'cursor'=>wp_json_encode(array('mcpServers'=>array('mgd-wordpress'=> $proxy)),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),
   'generic'=>wp_json_encode(array('mcpServers'=>array('mgd-wordpress'=> $proxy)),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),
  );
 }
}
