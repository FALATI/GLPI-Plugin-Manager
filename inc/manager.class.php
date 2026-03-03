<?php
// plugins/pluginmanager/inc/manager.class.php

class PluginPluginmanagerManager {
    
    /**
     * Processar upload e instalar plugin com PERMISSÕES COMPLETAS
     */
    public static function processUpload($file) {
        $result = [
            'success' => false,
            'message' => ''
        ];
        
        try {
            // Verificar arquivo
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Erro no upload: " . self::uploadError($file['error']));
            }
            
            // Verificar extensão
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'zip') {
                throw new Exception("Apenas arquivos .zip são suportados");
            }
            
            // Criar diretório temporário
            $temp_dir = GLPI_ROOT . '/files/_tmp/plugin_' . time();
            if (!mkdir($temp_dir, 0755, true)) {
                throw new Exception("Não foi possível criar diretório temporário");
            }
            
            // Mover arquivo enviado
            $zip_file = $temp_dir . '/' . $file['name'];
            if (!move_uploaded_file($file['tmp_name'], $zip_file)) {
                throw new Exception("Falha ao salvar arquivo");
            }
            
            // Extrair ZIP
            $zip = new ZipArchive;
            if ($zip->open($zip_file) !== true) {
                throw new Exception("Não foi possível abrir o arquivo ZIP");
            }
            
            $zip->extractTo($temp_dir);
            $zip->close();
            
            // Procurar setup.php
            $plugin_dir = self::findPluginDirectory($temp_dir);
            if (!$plugin_dir) {
                throw new Exception("Arquivo setup.php não encontrado no pacote");
            }
            
            // Obter nome do plugin
            $plugin_name = self::getPluginName($plugin_dir);
            if (!$plugin_name) {
                $plugin_name = basename($plugin_dir);
            }
            
            // Sanitizar nome do plugin (remover caracteres especiais)
            $plugin_name = preg_replace('/[^a-z0-9_]/i', '', $plugin_name);
            
            // Verificar se plugin já existe
            $target_dir = GLPI_ROOT . '/plugins/' . $plugin_name;
            if (is_dir($target_dir)) {
                throw new Exception("Plugin '$plugin_name' já está instalado");
            }
            
            // Mover para pasta de plugins
            if (!rename($plugin_dir, $target_dir)) {
                throw new Exception("Falha ao mover plugin para diretório de plugins");
            }
            
            // ===================================================
            // PERMISSÕES COMPLETAS - TUDO QUE É POSSÍVEL
            // ===================================================
            
            // 1. Proprietário: www-data (usuário do Apache)
            self::setOwner($target_dir, 'www-data');
            
            // 2. Permissões base: 755 para pastas, 644 para arquivos
            self::setPermissions($target_dir);
            
            // 3. Permissões extras para pastas específicas (se existirem)
            $extra_dirs = ['inc', 'front', 'ajax', 'css', 'js', 'locales', 'hooks', 'install', 'lib'];
            foreach ($extra_dirs as $dir) {
                $full_path = $target_dir . '/' . $dir;
                if (is_dir($full_path)) {
                    chmod($full_path, 0755);
                }
            }
            
            // 4. Arquivos específicos que precisam de 755 (executáveis)
            $executable_files = ['cli_install.php', 'cli_update.php', 'cron.php', 'hook.php'];
            foreach ($executable_files as $file) {
                $full_path = $target_dir . '/' . $file;
                if (file_exists($full_path)) {
                    chmod($full_path, 0755);
                }
            }
            
            // 5. Verificar se precisa de permissão extra para arquivos de log/cache
            $write_dirs = ['log', 'cache', 'tmp', 'files', 'data', 'uploads'];
            foreach ($write_dirs as $dir) {
                $full_path = $target_dir . '/' . $dir;
                if (is_dir($full_path)) {
                    chmod($full_path, 0777); // Permissão total para escrita
                }
            }
            
            // 6. Recursivamente garantir que tudo tem permissão
            self::ensurePermissions($target_dir);
            
            // 7. Registrar sucesso no log do GLPI
            error_log("Plugin Manager: Plugin '$plugin_name' instalado com todas as permissões");
            
            // Limpeza
            self::cleanTemp($temp_dir);
            
            $result['success'] = true;
            $result['message'] = "✅ Plugin '$plugin_name' instalado com sucesso! Todas as permissões foram configuradas automaticamente. Acesse Configuração > Plugins para ativá-lo.";
            
        } catch (Exception $e) {
            $result['message'] = "❌ Erro: " . $e->getMessage();
            
            // Registrar erro
            error_log("Plugin Manager Erro: " . $e->getMessage());
            
            // Limpar em caso de erro
            if (isset($temp_dir) && is_dir($temp_dir)) {
                self::cleanTemp($temp_dir);
            }
        }
        
        return $result;
    }
    
    /**
     * Definir proprietário (tenta de várias formas)
     */
    private static function setOwner($path, $user) {
        // Tentar chown com PHP
        if (function_exists('chown')) {
            @chown($path, $user);
            @chgrp($path, $user);
        }
        
        // Tentar com exec (mais poderoso)
        if (function_exists('exec')) {
            @exec("chown -R $user:$user " . escapeshellarg($path) . " 2>/dev/null");
        }
    }
    
    /**
     * Garantir permissões recursivamente
     */
    private static function ensurePermissions($path) {
        if (!is_dir($path)) {
            return;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                chmod($item->getPathname(), 0755);
            } else {
                chmod($item->getPathname(), 0644);
            }
        }
    }
    
    /**
     * Definir permissões base
     */
    private static function setPermissions($dir) {
        // Permissões para pastas
        if (function_exists('exec')) {
            @exec("find " . escapeshellarg($dir) . " -type d -exec chmod 755 {} \; 2>/dev/null");
            @exec("find " . escapeshellarg($dir) . " -type f -exec chmod 644 {} \; 2>/dev/null");
        } else {
            // Fallback simples
            chmod($dir, 0755);
        }
    }
    
    /**
     * Encontrar diretório do plugin (contém setup.php)
     */
    private static function findPluginDirectory($dir) {
        $items = scandir($dir);
        
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;
            
            $path = $dir . '/' . $item;
            
            if (is_dir($path)) {
                if (file_exists($path . '/setup.php')) {
                    return $path;
                }
            } else {
                if ($item == 'setup.php') {
                    return $dir;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Obter nome do plugin do setup.php
     */
    private static function getPluginName($plugin_dir) {
        $setup_file = $plugin_dir . '/setup.php';
        if (!file_exists($setup_file)) {
            return null;
        }
        
        $content = file_get_contents($setup_file);
        
        // Procurar por plugin_version_NOMEDOPLUGIN
        if (preg_match("/function\s+plugin_version_([a-zA-Z0-9_]+)\s*\(/", $content, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Limpar diretório temporário
     */
    private static function cleanTemp($dir) {
        if (is_dir($dir)) {
            if (function_exists('exec')) {
                @exec("rm -rf " . escapeshellarg($dir) . " 2>/dev/null");
            } else {
                array_map('unlink', glob("$dir/*.*"));
                rmdir($dir);
            }
        }
    }
    
    /**
     * Traduzir código de erro de upload
     */
    private static function uploadError($code) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Arquivo maior que upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'Arquivo maior que MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'Upload parcial',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo enviado',
            UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária não encontrada',
            UPLOAD_ERR_CANT_WRITE => 'Falha ao escrever no disco',
            UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão'
        ];
        
        return $errors[$code] ?? 'Erro desconhecido';
    }
}
