<?php
include ("../../../inc/includes.php");

Session::checkRight("config", READ);

Html::header("Ticket Answers", $_SERVER['PHP_SELF'], "plugins", "pluginticketanswersmenu", "config");

// Salvar configurações se o formulário foi enviado
if (isset($_POST['update'])) {
    Session::checkRight("config", UPDATE);
    
    // Salvar configurações com sanitização
    Config::setConfigurationValues('plugin:ticketanswers', [
        'check_interval' => intval($_POST['check_interval']),
        'enable_sound' => isset($_POST['enable_sound']) ? 1 : 0,
        'notifications_per_page' => intval($_POST['notifications_per_page']),
        'auto_refresh' => isset($_POST['auto_refresh']) ? 1 : 0
    ]);
    
    Session::addMessageAfterRedirect('Configurações salvas com sucesso', true, INFO);
    Html::back();
}

// Obter configurações atuais
$config = Config::getConfigurationValues('plugin:ticketanswers');

// Valores padrão
$check_interval = $config['check_interval'] ?? 30;
$enable_sound = $config['enable_sound'] ?? 1;
$notifications_per_page = $config['notifications_per_page'] ?? 10;
$auto_refresh = $config['auto_refresh'] ?? 1;

echo "<div class='center'>";
echo "<h1>Configuração do Ticket Answers</h1>";

echo "<form name='form' method='post' action='" . $_SERVER['PHP_SELF'] . "'>";
echo "<table class='tab_cadre_fixe'>";

// Intervalo de verificação
echo "<tr class='tab_bg_1'>";
echo "<td>Intervalo de verificação (segundos)</td>";
echo "<td>";
echo "<input type='number' name='check_interval' value='$check_interval' min='10' max='300'>";
echo "</td>";
echo "</tr>";

// Habilitar som
echo "<tr class='tab_bg_1'>";
echo "<td>Habilitar notificação sonora</td>";
echo "<td>";
echo "<input type='checkbox' name='enable_sound' " . ($enable_sound ? "checked" : "") . ">";
echo "</td>";
echo "</tr>";

// Volume do som
echo "<tr class='tab_bg_1'>";
echo "<td>Volume do som (0-100)</td>";
echo "<td>";
echo "<input type='range' name='sound_volume' min='0' max='100' value='" . ($config['sound_volume'] ?? 50) . "' style='width: 200px;'>";
echo "</td>";
echo "</tr>";

// Atualização automática
echo "<tr class='tab_bg_1'>";
echo "<td>Atualizar automaticamente a página de notificações</td>";
echo "<td>";
echo "<input type='checkbox' name='auto_refresh' " . ($auto_refresh ? "checked" : "") . ">";
echo "</td>";
echo "</tr>";

// Notificações por página
echo "<tr class='tab_bg_1'>";
echo "<td>Notificações por página</td>";
echo "<td>";
echo "<input type='number' name='notifications_per_page' value='$notifications_per_page' min='5' max='50'>";
echo "</td>";
echo "</tr>";

echo "<tr class='tab_bg_2'>";
echo "<td colspan='2' class='center'>";
echo "<input type='submit' name='update' class='submit' value=\"" . _sx('button', 'Salvar') . "\">";
echo "</td>";
echo "</tr>";

echo "</table>";
Html::closeForm();
echo "</div>";

Html::footer();
echo "<div class='center'>";
echo "<h1>Configuração do Ticket Answers</h1>";

echo "<form name='form' method='post' action='" . $_SERVER['PHP_SELF'] . "'>";
echo "<table class='tab_cadre_fixe'>";

// Intervalo de verificação
echo "<tr class='tab_bg_1'>";
echo "<td>Intervalo de verificação (segundos)</td>";
echo "<td>";
echo "<input type='number' name='check_interval' value='$check_interval' min='10' max='300'>";
echo "</td>";
echo "</tr>";

// Habilitar som
echo "<tr class='tab_bg_1'>";
echo "<td>Habilitar notificação sonora</td>";
echo "<td>";
echo "<input type='checkbox' name='enable_sound' " . ($enable_sound ? "checked" : "") . ">";
echo "</td>";
echo "</tr>";

// Atualização automática
echo "<tr class='tab_bg_1'>";
echo "<td>Atualizar automaticamente a página de notificações</td>";
echo "<td>";
echo "<input type='checkbox' name='auto_refresh' " . ($auto_refresh ? "checked" : "") . ">";
echo "</td>";
echo "</tr>";

// Notificações por página
echo "<tr class='tab_bg_1'>";
echo "<td>Notificações por página</td>";
echo "<td>";
echo "<input type='number' name='notifications_per_page' value='$notifications_per_page' min='5' max='50'>";
echo "</td>";
echo "</tr>";

echo "<tr class='tab_bg_2'>";
echo "<td colspan='2' class='center'>";
echo "<input type='submit' name='update' class='submit' value=\"" . _sx('button', 'Salvar') . "\">";
echo "</td>";
echo "</tr>";

echo "</table>";
Html::closeForm();
echo "</div>";

// Após o formulário de configuração
echo "<div class='center' style='margin-top: 20px;'>";
echo "<button type='button' class='btn btn-primary' id='test-sound-button'>Testar Som</button>";
echo "<div id='sound-test-result' style='margin-top: 10px;'></div>";
echo "</div>";

// Adicionar script para testar o som
echo "<script>
$(document).ready(function() {
    $('#test-sound-button').on('click', function() {
        $('#sound-test-result').html('<div class=\"alert alert-info\">Tentando reproduzir som...</div>');
        
        try {
            var audio = new Audio('sound/notification.mp3');
            
            audio.addEventListener('play', function() {
                $('#sound-test-result').html('<div class=\"alert alert-success\">Som reproduzido com sucesso!</div>');
            });
            
            audio.addEventListener('error', function(e) {
                $('#sound-test-result').html('<div class=\"alert alert-danger\">Erro ao reproduzir som: ' + e.message + '</div>');
            });
            
            var playPromise = audio.play();
            
            if (playPromise !== undefined) {
                playPromise.catch(function(error) {
                    $('#sound-test-result').html('<div class=\"alert alert-danger\">Erro ao reproduzir som: ' + error.message + '</div>');
                });
            }
        } catch (e) {
            $('#sound-test-result').html('<div class=\"alert alert-danger\">Exceção ao tentar reproduzir som: ' + e.message + '</div>');
        }
    });
});
</script>";

Html::footer();