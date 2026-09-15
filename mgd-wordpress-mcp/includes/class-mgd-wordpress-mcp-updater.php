<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class MGD_WordPress_MCP_Updater {
    private static $instance = null;
    private $repo = MGD_WPMCP_GITHUB_REPO;
    public static function instance(){ if(null===self::$instance){self::$instance=new self();} return self::$instance; }
    private function __construct(){
        add_filter('update_plugins_github.com',array($this,'update_uri_response'),10,4);
        add_filter('plugins_api',array($this,'plugin_info'),20,3);
        add_action('upgrader_process_complete',array($this,'clear_cache'),10,2);
    }
    private function cache_key(){ return 'mgd_wpmcp_release_'.md5($this->repo); }
    private function release(){
        if(!MGD_WordPress_MCP::setting('github_updates',true)){return false;}
        $cached=get_site_transient($this->cache_key()); if(false!==$cached){return $cached;}
        $response=wp_safe_remote_get('https://api.github.com/repos/'.$this->repo.'/releases/latest',array('timeout'=>10,'headers'=>array('Accept'=>'application/vnd.github+json','User-Agent'=>'MGD-WordPress-MCP/'.MGD_WPMCP_VERSION)));
        if(is_wp_error($response)||200!==wp_remote_retrieve_response_code($response)){set_site_transient($this->cache_key(),array(),HOUR_IN_SECONDS);return array();}
        $data=json_decode(wp_remote_retrieve_body($response),true); if(!is_array($data)){return array();}
        set_site_transient($this->cache_key(),$data,6*HOUR_IN_SECONDS); return $data;
    }
    private function package_url($release){ if(empty($release['assets'])||!is_array($release['assets'])){return '';} foreach($release['assets'] as $asset){if(isset($asset['name'],$asset['browser_download_url'])&&'mgd-wordpress-mcp.zip'===$asset['name']){return esc_url_raw($asset['browser_download_url']);}} return ''; }
    public function update_uri_response($update,$plugin_data,$plugin_file,$locales){
        if(MGD_WPMCP_BASENAME!==$plugin_file){return $update;}
        $release=$this->release(); if(empty($release['tag_name'])){return false;}
        $version=ltrim((string)$release['tag_name'],'vV'); $package=$this->package_url($release); if(!$package){return false;}
        return array(
            'id'=>'https://github.com/'.$this->repo,
            'slug'=>'mgd-wordpress-mcp',
            'version'=>$version,
            'new_version'=>$version,
            'url'=>'https://Michael-Gahn.de',
            'package'=>$package,
            'tested'=>'7.1',
            'requires_php'=>'7.4',
            'autoupdate'=>false,
            'icons'=>array('1x'=>MGD_WPMCP_URL.'assets/branding/plugin-icon.svg','2x'=>MGD_WPMCP_URL.'assets/branding/plugin-icon.svg')
        );
    }
    public function plugin_info($result,$action,$args){
        if('plugin_information'!==$action||empty($args->slug)||'mgd-wordpress-mcp'!==$args->slug){return $result;}
        $release=$this->release(); $version=!empty($release['tag_name'])?ltrim($release['tag_name'],'vV'):MGD_WPMCP_VERSION;
        return(object)array('name'=>'MGD WordPress MCP','slug'=>'mgd-wordpress-mcp','version'=>$version,'author'=>'<a href="https://Michael-Gahn.de">Michael Gahn DESIGN</a>','homepage'=>'https://Michael-Gahn.de','requires'=>'6.9','tested'=>'7.1','requires_php'=>'7.4','download_link'=>$this->package_url($release),'icons'=>array('1x'=>MGD_WPMCP_URL.'assets/branding/plugin-icon.svg','2x'=>MGD_WPMCP_URL.'assets/branding/plugin-icon.svg'),'sections'=>array('description'=>'<h2>WordPress trifft KI</h2><p>MGD WordPress MCP verbindet WordPress kontrolliert mit MCP-kompatiblen KI-Agenten.</p><h3>Privacy by Default</h3><p>Die Admin-Oberfläche lädt keine externen Fonts, Icon-CDNs oder JavaScript-CDNs.</p>','installation'=>'<p>Plugin installieren, Einrichtungs-Assistent starten, MCP Adapter prüfen und nur benötigte Rechte aktivieren.</p>','changelog'=>!empty($release['body'])?wp_kses_post(nl2br($release['body'])):'<p>Siehe GitHub Releases.</p>'));
    }
    public function clear_cache($upgrader,$options){if(isset($options['type'])&&'plugin'===$options['type']){delete_site_transient($this->cache_key());}}
}
