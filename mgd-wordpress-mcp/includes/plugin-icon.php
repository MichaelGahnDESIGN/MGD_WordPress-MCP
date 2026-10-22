<?php
// Lokales Icon nur in der eigenen Pluginzeile und vorhandenen Updateinformationen.
if (!defined('ABSPATH')) { exit; }
return static function ($plugin_file) {
    $basename = plugin_basename($plugin_file);
    $slug = dirname($basename);
    $base_url = plugin_dir_url($plugin_file);
    $icons = array('1x' => $base_url . 'assets/plugin-branding/icon-128.png', '2x' => $base_url . 'assets/plugin-branding/icon-256.png');
    add_action('admin_enqueue_scripts', static function ($hook) use ($basename, $base_url) {
        if ('plugins.php' !== $hook) { return; }
        $handle = 'mgd-icon-' . md5($basename);
        wp_register_style($handle, false, array(), '1.0.0');
        wp_enqueue_style($handle);
        $selector = 'tr[data-plugin="' . esc_attr($basename) . '"] .plugin-title strong';
        $css = $selector . '{display:inline-flex;align-items:center;gap:12px;}'
            . $selector . '::before{content:"";display:block;flex:0 0 40px;width:40px;height:40px;background:center/contain no-repeat url("' . esc_url($base_url . 'assets/plugin-branding/icon-128.png') . '");}';
        wp_add_inline_style($handle, $css);
    });
    add_filter('pre_set_site_transient_update_plugins', static function ($value) use ($basename, $icons) {
        if (!is_object($value)) { return $value; }
        foreach (array('response', 'no_update') as $group) {
            if (isset($value->{$group}[$basename]) && is_object($value->{$group}[$basename])) {
                $value->{$group}[$basename]->icons = $icons;
            }
        }
        return $value;
    }, 99);
    add_filter('plugins_api', static function ($result, $action, $args) use ($slug, $icons) {
        // Ein fehlendes Detailobjekt nicht ersetzen und fremde Plugins nicht verändern.
        if ('plugin_information' === $action && is_object($args) && $slug === ($args->slug ?? '') && is_object($result) && !is_wp_error($result)) {
            $result->icons = $icons;
        }
        return $result;
    }, 99, 3);
};
