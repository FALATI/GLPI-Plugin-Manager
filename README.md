📦 GLPI Plugin Manager
Plugin Manager é uma ferramenta para GLPI 11+ que permite instalar plugins via upload diretamente pela interface web, sem necessidade de acesso ao terminal.

✨ Funcionalidades
✅ Upload de plugins em formato .zip diretamente pelo navegador

✅ Extração automática do pacote

✅ Detecção inteligente da estrutura do plugin (procura por setup.php)

✅ Instalação automática na pasta correta de plugins do GLPI

✅ Ajuste automático de permissões

✅ Interface simples e intuitiva

✅ Apenas Super Administradores podem acessar (segurança)

📋 Requisitos
GLPI 11.0 ou superior

PHP 8.1 ou superior

Extensões PHP: zip, json

Servidor Linux (testado em Debian/Ubuntu)

🚀 Instalação
1. Download do Plugin
cd /var/www/html/glpi/plugins/
git clone https://github.com/seu-usuario/glpi-pluginmanager.git pluginmanager

2. Permissões (PASSO CRÍTICO!)
# Ajustar permissões do plugin
chown -R www-data:www-data /var/www/html/glpi/plugins/pluginmanager/
chmod -R 755 /var/www/html/glpi/plugins/pluginmanager/

# Garantir que a pasta de plugins tenha permissão de escrita
chown www-data:www-data /var/www/html/glpi/plugins/
chmod 755 /var/www/html/glpi/plugins/

# Criar diretório temporário (se não existir)
mkdir -p /var/www/html/glpi/files/_tmp
chown www-data:www-data /var/www/html/glpi/files/_tmp
chmod 755 /var/www/html/glpi/files/_tmp

3. Instalação no GLPI
Acesse o GLPI como Super Administrador

Vá em Configuração > Plugins

Localize "Plugin Manager" na lista

Clique em Instalar e depois Habilitar

🎯 Como Usar
Acesse Administração > Plugin Manager

Clique em "Escolher arquivo" e selecione um plugin .zip

Clique em Fazer Upload e Instalar

Pronto! O plugin será extraído e instalado automaticamente

Vá em Configuração > Plugins para ativar o novo plugin

🤝 Contribuindo
Contribuições são bem-vindas! Sinta-se à vontade para:

Reportar issues
Sugerir melhorias
Enviar pull requests
