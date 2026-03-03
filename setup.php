<?php
// plugins/pluginmanager/setup.php
define('PLUGIN_PLUGINMANAGER_VERSION', '1.0.0');
define('PLUGIN_PLUGINMANAGER_DIR', __DIR__);

function plugin_init_pluginmanager()
{
    global $PLUGIN_HOOKS;
    $PLUGIN_HOOKS['csrf_compliant']['pluginmanager'] = true;

    if (Session::haveRight('config', UPDATE)) {
        $PLUGIN_HOOKS['config_page']['pluginmanager'] = 'front/upload.php';
    }
}

function plugin_version_pluginmanager()
{
    return [
        'name' => 'Plugin Manager',
        'version' => PLUGIN_PLUGINMANAGER_VERSION,
        'author' => 'Jeferson Filipe (_Fala Ti)',
        'license' => 'GPLv3+',
        'requirements' => [
            'glpi' => ['min' => '11.0']
        ]
    ];
}

function plugin_pluginmanager_check_prerequisites()
{
    return true;
}

function plugin_pluginmanager_check_config()
{
    return true;
}
?>
