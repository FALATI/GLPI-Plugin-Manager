<?php
// plugins/pluginmanager/front/upload.php
include('../../../inc/includes.php');

// Verificar permissão
if (!Session::haveRight('config', UPDATE)) {
    Html::displayRightError();
}

// Incluir classe manager
include_once(PLUGIN_PLUGINMANAGER_DIR . '/inc/manager.class.php');

// Processar upload
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['plugin_file'])) {
    $result = PluginPluginmanagerManager::processUpload($_FILES['plugin_file']);
}

// Header
Html::header(
    'Plugin Manager - Upload',
    $_SERVER['PHP_SELF'],
    'admin',
    'config'
);
?>

<div style="padding:20px;">
    <h1 style="margin-bottom:20px;">📦 Plugin Manager - Upload de Plugins</h1>
    
    <?php if ($result): ?>
        <div style="background: <?php echo $result['success'] ? '#d4edda' : '#f8d7da'; ?>; 
                    color: <?php echo $result['success'] ? '#155724' : '#721c24'; ?>; 
                    padding: 15px; 
                    margin: 20px 0; 
                    border-radius: 4px;
                    border: 1px solid <?php echo $result['success'] ? '#c3e6cb' : '#f5c6cb'; ?>;">
            <strong><?php echo $result['success'] ? '✅ Sucesso!' : '❌ Erro!'; ?></strong>
            <p style="margin:10px 0 0 0;"><?php echo $result['message']; ?></p>
        </div>
    <?php
endif; ?>
    
    <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px;">
        <h2 style="margin-top:0;">Upload de Novo Plugin</h2>
        <p style="color:#666; margin-bottom:20px;">Selecione um arquivo .zip contendo um plugin GLPI</p>
        
        <form method="post" enctype="multipart/form-data">
            <div style="margin: 20px 0;">
                <label style="display: block; margin-bottom: 10px; font-weight: bold;">Arquivo do plugin (.zip):</label>
                <input type="file" name="plugin_file" accept=".zip" required 
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            
            <input type="hidden" name="_glpi_csrf_token" value="<?php echo Session::getNewCSRFToken(); ?>">
            
            <button type="submit" 
                    style="background: #3498db; color: white; padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">
                ⬆️ Fazer Upload e Instalar
            </button>
        </form>
        
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
            <h3 style="margin-top:0; font-size:14px; color:#666;">📋 Requisitos:</h3>
            <ul style="margin:0; padding-left:20px; color:#666; font-size:13px;">
                <li>Arquivo .zip contendo o plugin</li>
                <li>Deve conter arquivo setup.php na raiz</li>
                <li>Tamanho máximo: <?php echo ini_get('upload_max_filesize'); ?></li>
                <li>⚠️ Apenas administradores podem fazer upload</li>
            </ul>
        </div>
    </div>
    
    <div style="margin-top: 30px;">
        <a href="<?php echo $CFG_GLPI['root_doc']; ?>/front/plugin.php" 
           style="background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">
            ⚙️ Ir para Gerenciador de Plugins
        </a>
    </div>
</div>

<?php
Html::footer();
?>
