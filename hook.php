<?php
// plugins/pluginmanager/hook.php

function plugin_pluginmanager_install() {
    // Sem criação de tabelas - apenas retorna true
    return true;
}

function plugin_pluginmanager_uninstall() {
    return true;
}
?>
