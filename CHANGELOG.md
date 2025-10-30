# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [1.2.1] - 2025-10-23

### Adicionado
- ✅ **Sistema completo de traduções gettext para GLPI 11**
  - Criada pasta `locales/` com estrutura correta
  - Arquivo template `ticketanswers.pot` com todas as strings traduzíveis
  - Traduções completas em `pt_BR.po` (Português Brasil)
  - Traduções completas em `en_US.po` (Inglês EUA)
  - Arquivos binários compilados `pt_BR.mo` e `en_US.mo`
  - Script Python `compile_translations.py` para compilar traduções sem gettext
  - Documentação completa em `locales/README_TRANSLATIONS.md`
  - Arquivo `.gitattributes` para tratamento correto dos binários
- ✅ **Tabela `glpi_plugin_ticketanswers_notification_prefs`** para preferências de usuário
  - Campos: enable_sound, sound_volume, check_interval, notifications_per_page
  - Criada automaticamente na instalação e atualização
  - Removida automaticamente na desinstalação

### Alterado
- ✅ Todas as strings hardcoded substituídas por chamadas `__()` de tradução
- ✅ Atualizado `setup.php` para remover bloqueios de tradução
- ✅ Atualizado `inc/menu.class.php` com funções de tradução
- ✅ Atualizado `inc/ticketanswers.class.php` com funções de tradução
- ✅ Atualizado `inc/config.class.php` com funções de tradução
- ✅ Script `compile_translations.py` melhorado com validação UTF-8
  - Tratamento UTF-8-BOM (`utf-8-sig`)
  - Validação de encoding antes de adicionar traduções
  - Try/catch para caracteres inválidos
  - Logs de warning para strings problemáticas

### Corrigido
- ✅ **CRÍTICO: Erro "Malformed UTF-8 characters" nos arquivos .mo**
  - Recompilados com validação adequada de encoding
  - Script Python agora valida UTF-8 antes de compilar
- ✅ **CRÍTICO: Table 'glpi_plugin_ticketanswers_notification_prefs' doesn't exist**
  - Tabela criada em `plugin_ticketanswers_install()`
  - Tabela criada em `plugin_ticketanswers_update()` para migrações
  - Tabela removida em `plugin_ticketanswers_uninstall()`
- ✅ **Erro 404 em locales/** - Sistema de traduções agora totalmente funcional
- ✅ Plugin agora segue a documentação oficial do GLPI 11 para traduções
- ✅ Compatibilidade total com sistema gettext do GLPI
- ✅ Avisos "Undefined array key 'domain'" eliminados

## [1.2.0] - 2025-10-22

### Adicionado
- Suporte completo para GLPI 11.0 e versões superiores
- Novos índices de banco de dados para melhor performance (`idx_users_id`, `idx_ticket_id`)
- Compatibilidade com Tabler Icons (padrão do GLPI 11)
- Uso de `Plugin::getWebDir()` e `Plugin::getPhpDir()` para paths dinâmicos
- Documentação de compatibilidade no README
- Sistema de migração automática de versões antigas
- Uso da classe `Migration` para todas as operações de banco de dados

### Alterado
- **BREAKING CHANGE:** Requisito mínimo mudou de GLPI 9.5 para GLPI 11.0
- **BREAKING CHANGE:** Requisito mínimo de PHP mudou de 7.4 para 8.1
- **IMPORTANTE:** Substituídas todas as queries SQL diretas pela API Migration do GLPI 11
- Atualizado sistema de verificação de requisitos para usar novo formato do GLPI 11
- Ícones FontAwesome substituídos por Tabler Icons (`fas fa-bell` → `ti ti-bell`)
- Melhorias na função de instalação com melhor tratamento de erros
- Classe `PluginTicketanswers` renomeada para `PluginTicketanswersTicketanswers` para consistência
- Uso de `ProfileRight` e métodos modernos para gerenciamento de permissões
- Uso de `Toolbox::logInFile()` ao invés de `error_log()`

### Corrigido
- Compatibilidade com a nova API de banco de dados do GLPI 11
- Erro "Executing direct queries is not allowed!" - agora usando Migration API
- Estrutura de paths absolutos vs relativos
- Tratamento de erros em operações de banco de dados
- Timestamps com valor padrão `CURRENT_TIMESTAMP`

### Removido
- Suporte para GLPI 9.5 e 10.x (use versão 1.x para estas versões)
- Parâmetro `minGlpiVersion` (substituído por `requirements`)
- Queries SQL diretas (substituídas pela Migration API)

## [1.1.0] - 2024

### Adicionado
- Sistema de notificações unificado
- Sino de notificações na interface
- Estatísticas de tickets
- Suporte a GLPI 10.x

### Alterado
- Melhorias gerais de interface
- Otimizações de performance

## [1.0.0] - 2023

### Adicionado
- Versão inicial do plugin
- Sistema básico de notificações
- Rastreamento de visualizações de tickets
- Interface de gerenciamento

---

## Guia de Migração

### De 1.x para 2.0

**Importante:** Esta é uma atualização major com mudanças incompatíveis.

#### Antes de atualizar:

1. **Backup:** Faça backup completo do banco de dados
2. **Versão do GLPI:** Certifique-se de estar usando GLPI 11.0 ou superior
3. **Versão do PHP:** Certifique-se de estar usando PHP 8.1 ou superior

#### Processo de atualização:

1. Desative o plugin na interface do GLPI
2. Substitua os arquivos do plugin pela nova versão
3. No painel de plugins, clique em "Atualizar" (não desinstale!)
4. Ative o plugin novamente

A migração do banco de dados será executada automaticamente.

#### Mudanças que podem afetar customizações:

- Ícones: Se você personalizou ícones, atualize de FontAwesome para Tabler Icons
- Paths: Caminhos absolutos foram substituídos por caminhos dinâmicos
- Classes: Verifique referências à classe `PluginTicketanswers` (agora `PluginTicketanswersTicketanswers`)
