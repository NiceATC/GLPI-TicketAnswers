<UPDATED_CODE><?php
include ("../../../inc/includes.php");

Session::checkLoginUser();

Html::header("Ticket Answers", $_SERVER['PHP_SELF'], "plugins", "PluginTicketanswersMenu");

// Obter configurações
$config = Config::getConfigurationValues('plugin:ticketanswers');
$check_interval = $config['check_interval'] ?? 5;
$enable_sound = $config['enable_sound'] ?? 1;
$sound_volume = $config['sound_volume'] ?? 70;

// Verificar se há um valor selecionado pelo usuário
$notifications_per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : ($config['notifications_per_page'] ?? 10);

// Garantir que o valor seja um dos permitidos
$allowed_values = [10, 50, 100, 150, 200];
if (!in_array($notifications_per_page, $allowed_values)) {
    $notifications_per_page = 10; // Valor padrão se não for válido
}

// Adicionar configurações JavaScript
echo "<script>
window.ticketAnswersConfig = {
    checkInterval: " . $check_interval . ",
    enableSound: " . ($enable_sound ? 'true' : 'false') . ",
    soundVolume: " . $sound_volume . "
};
</script>";

// Adicionar as funções JavaScript necessárias diretamente no arquivo
echo "<script>
// Função para marcar todas as notificações como lidas
function markAllAsRead() {
    if (confirm('Deseja realmente marcar todas as notificações como lidas?')) {
        $.ajax({
            url: '../ajax/mark_all_as_read.php',
            type: 'GET',
            data: {
                ajax: 1
            },
            success: function(response) {
                // Recarregar a página após marcar todas como lidas
                window.location.reload();
            }
        });
    }
}


// Função para marcar uma notificação como lida
function markNotificationAsRead(ticketId, followupId, type, newTab) {
    // Adicionar logs para depuração
    console.log('Iniciando markNotificationAsRead:', {ticketId, followupId, type, newTab});
    
    // Gerar um message_id único baseado no timestamp atual
    var messageId = 'notification_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
    
    // Fazer a requisição AJAX para marcar como lido
    $.ajax({
        url: '../ajax/mark_notification_as_read.php',
        type: 'GET',
        data: {
            ticket_id: ticketId,
            followup_id: followupId,
            type: type,
            message_id: messageId,
            ajax: 1
        },
        success: function(response) {
            console.log('Resposta do servidor:', response);
            
            // Determinar o ID da linha com base no tipo
            let rowId;
            
            // Para notificações de status, o ID pode estar em um formato específico
            if (type === 'status_change') {
                // Tente encontrar a linha usando diferentes estratégias
                let statusRow = $('tr[data-notification-type=\"status_change\"][data-ticket-id=\"' + ticketId + '\"]');
                
                if (statusRow.length > 0) {
                    // Se encontrou pelo atributo data, use essa linha diretamente
                    statusRow.fadeOut(500, function() {
                        $(this).remove();
                        checkForEmptyTable();
                    });
                    
                    handleNavigation();
                    return; // Saia da função após lidar com a remoção
                }
                
                // Se não encontrou, tente com o ID específico
                rowId = 'notification-row-' + ticketId + '-' + followupId;
            } else if (type === 'followup' ||
                type === 'refused' ||
                type === 'validation' ||
                type === 'validation_request' ||
                type === 'validation_approved' ||
                type === 'validation_refused' ||
                type === 'validation_response' ||
                type === 'validation_request_response' ||
                type === 'pending_reason' ||
                type === 'technician_response') {
                rowId = 'notification-row-' + ticketId + '-' + followupId;
            } else {
                rowId = 'group-notification-row-' + ticketId;
            }
            
            console.log('ID da linha a ser removida:', rowId);
            
            // Animar a remoção da linha da tabela
            $('#' + rowId).fadeOut(500, function() {
                $(this).remove();
                checkForEmptyTable();
            });
            
            handleNavigation();
            
            // Função auxiliar para verificar se a tabela ficou vazia
            function checkForEmptyTable() {
                if ($('table.tab_cadre_fixehov tr').length <= 1) {
                    // Se só sobrou o cabeçalho, mostrar mensagem de 'não há notificações'
                    $('table.tab_cadre_fixehov').replaceWith(
                    \"<div class='alert alert-info'>Não há novas notificações</div>\"
                    );
                }
                
                // Atualizar o contador de notificações
                updateNotificationCount();
            }
            
            // Função auxiliar para lidar com a navegação
            function handleNavigation() {
                // Comportamento diferente baseado no parâmetro newTab
                if (newTab) {
                    // Modificado para usar diretamente o objeto CFG_GLPI
                    var ticketUrl = CFG_GLPI.root_doc + '/front/ticket.form.php?id=' + ticketId;
                    console.log('Abrindo nova aba com URL:', ticketUrl);
                    
                    // Criar um elemento <a> temporário
                    var tempLink = document.createElement('a');
                    tempLink.href = ticketUrl;
                    tempLink.target = '_blank';
                    tempLink.rel = 'noopener noreferrer'; // Por segurança
                    tempLink.style.display = 'none';
                    
                    // Adicionar ao documento, clicar e remover
                    document.body.appendChild(tempLink);
                    tempLink.click();
                    document.body.removeChild(tempLink);
                    
                    // Atualizar a lista de notificações na página atual
                    refreshNotificationsList();
                } else {
                    // Modificado para usar diretamente o objeto CFG_GLPI
                    var redirectUrl = CFG_GLPI.root_doc + '/front/ticket.form.php?id=' + ticketId;
                    console.log('Redirecionando para:', redirectUrl);
                    
                    // Forçar o redirecionamento com setTimeout para garantir execução
                    setTimeout(function() {
                        window.location.href = redirectUrl;
                    }, 100);
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro na requisição AJAX:', error);
            console.error('Status:', status);
            console.error('Resposta:', xhr.responseText);
            alert('Erro ao processar a notificação: ' + error);
        }
    });
}




// Função para atualizar o contador de notificações
function updateNotificationCount() {
    // Contar quantas linhas de notificação ainda existem (excluindo o cabeçalho)
    var count = 0;
    $('table.tab_cadre_fixehov').each(function() {
        count += $(this).find('tr').length - 1;
    });
    
    if (count < 0) count = 0;
    
    // Atualizar o contador na página
    $('#notification-count').text(count);
}

// Função para atualizar a lista de notificações
function refreshNotificationsList() {
    // Preservar a seleção atual de itens por página
    var currentPerPage = $('#per_page').val() || $('#per_page_bottom').val() || 10;
    
    $.ajax({
        url: window.location.href,
        type: 'GET',
        data: {
            ajax: 1,
            per_page: currentPerPage
        },
        success: function(html) {
            // Extrair apenas o conteúdo do container de notificações
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            var newContent = $(tempDiv).find('#notifications-container').html();
            
            // Atualizar o conteúdo
            $('#notifications-container').html(newContent);
            
            // Atualizar o contador
            var count = $(tempDiv).find('#notification-count').text();
            $('#notification-count').text(count);
        }
    });
}

// Função para atualizar o contador de notificações
function updateNotificationCount() {
    // Contar quantas linhas de notificação ainda existem (excluindo o cabeçalho)
    var count = 0;
    $('table.tab_cadre_fixehov').each(function() {
        count += $(this).find('tr').length - 1;
    });
    
    if (count < 0) count = 0;
    
    // Atualizar o contador na página
    $('#notification-count').text(count);
}


// Função para assumir um chamado
function assignTicketToMe(ticketId) {
    console.log('Assumindo chamado:', ticketId); // Log para depuração
    
    if (!ticketId || ticketId <= 0) {
        console.error('ID do chamado inválido:', ticketId);
        alert('ID do chamado inválido.');
        return;
    }
    
    if (confirm('Deseja realmente assumir este chamado?')) {
        $.ajax({
            url: '../ajax/assign_ticket.php',
            type: 'POST',
            data: {
                ticket_id: ticketId
            },
            dataType: 'json', // Especificar que esperamos JSON
            success: function(response) {
                console.log('Resposta recebida:', response);
                
                if (response.success) {
                    // Remover a linha da tabela
                    $('#group-notification-row-' + ticketId).fadeOut(500, function() {
                        $(this).remove();
                        
                        // Verificar se ainda há notificações
                        if ($('table.tab_cadre_fixehov tr').length <= 1) {
                            // Se só sobrou o cabeçalho, mostrar mensagem de 'não há notificações'
                            $('table.tab_cadre_fixehov').replaceWith(
                                \"<div class='alert alert-info'>Não há novas notificações</div>\"
                            );
                        }
                    });
                    
                    // Mostrar mensagem de sucesso
                    alert(response.message || 'Chamado assumido com sucesso!');
                    
                    // Redirecionar para a página do ticket
                    window.location.href = CFG_GLPI.root_doc + '/front/ticket.form.php?id=' + ticketId;
                } else {
                    alert(response.message || 'Erro ao assumir o chamado.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro AJAX:', status, error);
                console.log('Resposta:', xhr.responseText);
                alert('Erro ao comunicar com o servidor: ' + error);
            }
        });
    }
}

</script>";

echo "<div class='center'>";
echo "<h1>Ticket Answers</h1>";


// Obtém o ID do usuário logado
$users_id = Session::getLoginUserID();

// Declarar variável global do banco de dados
global $DB;

// Consulta base sem LIMIT e sem ORDER BY
$combined_query_base = "(
    -- Notificações de respostas em chamados atribuídos
SELECT
    t.id AS ticket_id,
    t.name AS ticket_name,
    t.content AS ticket_content,
    tf.id AS followup_id,
    tf.date AS notification_date,
    tf.content AS followup_content,
    u.name AS user_name,
    NULL AS group_name,
    NULL AS refuse_reason,
    'followup' AS notification_type
FROM
    glpi_tickets t
    INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 2 AND tu.users_id = $users_id
    INNER JOIN glpi_itilfollowups tf ON t.id = tf.items_id AND tf.itemtype = 'Ticket'
    LEFT JOIN glpi_users u ON tf.users_id = u.id
    LEFT JOIN glpi_plugin_ticketanswers_views v ON (
        v.users_id = $users_id AND
        v.followup_id = tf.id
    )
WHERE
    tf.users_id <> $users_id
    AND v.id IS NULL
    AND t.status != 6
    AND tf.is_private = 0
    AND tf.date > (
        SELECT
            COALESCE(MAX(date), '1970-01-01')
        FROM
            glpi_itilfollowups tf2
        WHERE
            tf2.items_id = t.id
            AND tf2.itemtype = 'Ticket'
            AND tf2.users_id = $users_id
    )
    -- Adicionar uma condição mais robusta para filtrar followups de recusa
    AND NOT EXISTS (
        SELECT 1
        FROM glpi_itilsolutions its
        WHERE its.items_id = t.id
        AND its.itemtype = 'Ticket'
        AND its.status = 4
        AND its.users_id_approval = tf.users_id
        AND ABS(UNIX_TIMESTAMP(its.date_approval) - UNIX_TIMESTAMP(tf.date)) <= 3  -- Permite um intervalo de até 3 segundos
    )
)
UNION
(
    -- Notificações de chamados atribuídos a grupos
    SELECT
        t.id AS ticket_id,
        t.name AS ticket_name,
        t.content AS ticket_content,
        0 AS followup_id,
        t.date_creation AS notification_date,
        NULL AS followup_content,
        u.name AS user_name,
        g.name AS group_name,
        NULL AS refuse_reason,
        'group' AS notification_type
    FROM
        glpi_tickets t
        INNER JOIN glpi_groups_tickets gt ON t.id = gt.tickets_id AND gt.type = 2
        INNER JOIN glpi_groups_users gu ON gt.groups_id = gu.groups_id AND gu.users_id = $users_id
        LEFT JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.users_id = $users_id AND tu.type = 2
        LEFT JOIN glpi_users u ON t.users_id_recipient = u.id
        LEFT JOIN glpi_groups g ON gt.groups_id = g.id
        LEFT JOIN glpi_plugin_ticketanswers_views v ON (
            v.users_id = $users_id AND
            v.ticket_id = t.id AND
            v.followup_id = t.id + 10000000
        )
    WHERE
        tu.id IS NULL
        AND v.id IS NULL
        AND t.status IN (1, 2)
        AND t.date_creation > DATE_SUB(NOW(), INTERVAL 7 DAY)
)
UNION
(
    -- Notificações de chamados onde o usuário é observador
SELECT
    t.id AS ticket_id,
    t.name AS ticket_name,
    t.content AS ticket_content,
    0 AS followup_id,
    t.date_mod AS notification_date,  -- Usar data de modificação do ticket
    t.content AS followup_content,    -- Usar conteúdo do ticket
    u_requester.name AS user_name,    -- Nome do solicitante
    NULL AS group_name,
    NULL AS refuse_reason,
    'observer' AS notification_type
FROM
    glpi_tickets t
    INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 3 AND tu.users_id = $users_id
    LEFT JOIN glpi_users u_requester ON t.users_id_recipient = u_requester.id  -- Para obter o solicitante
    LEFT JOIN glpi_plugin_ticketanswers_views v ON (
        v.users_id = $users_id AND
        v.ticket_id = t.id AND
        v.followup_id = t.id + 20000000
    )
WHERE
    v.id IS NULL
    AND t.status IN (1, 2, 3, 4)  -- Novo, Em atendimento, Pendente, Solucionado)
    AND NOT EXISTS (
        SELECT 1 
        FROM glpi_tickets_users requester
        WHERE requester.tickets_id = t.id
        AND requester.users_id = $users_id 
        AND requester.type = 1
    )
    AND NOT EXISTS (
        SELECT 1 
        FROM glpi_tickets_users technician
        WHERE technician.tickets_id = t.id
        AND technician.users_id = $users_id 
        AND technician.type = 2
    )


)
UNION
(
    -- Notificações de chamados onde o grupo do usuário é observador
    SELECT
        t.id AS ticket_id,
        t.name AS ticket_name,
        t.content AS ticket_content,
        0 AS followup_id,
        tf.date AS notification_date,
        tf.content AS followup_content,
        u.name AS user_name,
        g.name AS group_name,
        NULL AS refuse_reason,
        'group_observer' AS notification_type
    FROM
        glpi_tickets t
        INNER JOIN glpi_groups_tickets gt ON t.id = gt.tickets_id AND gt.type = 3
        INNER JOIN glpi_groups_users gu ON gt.groups_id = gu.groups_id AND gu.users_id = $users_id
        INNER JOIN glpi_itilfollowups tf ON t.id = tf.items_id AND tf.itemtype = 'Ticket'
        LEFT JOIN glpi_users u ON tf.users_id = u.id
        LEFT JOIN glpi_groups g ON gt.groups_id = g.id
        LEFT JOIN glpi_plugin_ticketanswers_views v ON (
            v.users_id = $users_id AND
            v.ticket_id = t.id AND
            v.followup_id = t.id + 20000000
        )
    WHERE
        v.id IS NULL
        AND t.status != 6
        AND tf.date > (
            SELECT
                COALESCE(MAX(date), '1970-01-01')
            FROM
                glpi_itilfollowups tf2
            WHERE
                tf2.items_id = t.id
                AND tf2.itemtype = 'Ticket'
                AND tf2.users_id = $users_id
        )
)
UNION
(
    -- Notificações de chamados recusados
SELECT
    t.id AS ticket_id,
    t.name AS ticket_name,
    t.content AS ticket_content,
    its.id AS followup_id,
    its.date_approval AS notification_date,
    its.content AS followup_content,
    u.realname AS user_name,
    NULL AS group_name,
    tf.content AS refuse_reason,  -- Alterado para usar conteúdo do followup
    'refused' AS notification_type
FROM
    glpi_tickets t
    INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.users_id = $users_id
    INNER JOIN (
        -- Subconsulta para obter apenas a solução recusada mais recente para cada ticket
        SELECT items_id, MAX(id) as latest_solution_id
        FROM glpi_itilsolutions
        WHERE status = 4 AND itemtype = 'Ticket'
        GROUP BY items_id
    ) latest ON t.id = latest.items_id
    INNER JOIN glpi_itilsolutions its ON its.id = latest.latest_solution_id
    LEFT JOIN glpi_users u ON its.users_id_approval = u.id
    -- Adicionada junção para buscar o followup que contém o motivo da recusa
    LEFT JOIN glpi_itilfollowups tf ON (
        tf.items_id = t.id 
        AND tf.itemtype = 'Ticket'
        AND tf.users_id = its.users_id_approval
        AND tf.date = its.date_approval
    )
    LEFT JOIN glpi_plugin_ticketanswers_views v ON (
        v.users_id = $users_id AND
        v.followup_id = its.id
    )
WHERE
    its.users_id_approval <> $users_id  -- Filtrar recusas do próprio usuário
    AND v.id IS NULL
    AND t.status != 6  -- Excluir chamados fechados (status 6)

)
UNION
(
    -- Notificações de chamados recém atribuídos ao técnico
SELECT
    t.id AS ticket_id,
    t.name AS ticket_name,
    t.content AS ticket_content,
    0 AS followup_id,
    t.date_mod AS notification_date,
    t.content AS followup_content,
    u.name AS user_name,
    NULL AS group_name,
    NULL AS refuse_reason,
    'assigned' AS notification_type
FROM
    glpi_tickets t
    INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 2 AND tu.users_id = $users_id
    LEFT JOIN glpi_users u ON t.users_id_recipient = u.id
    LEFT JOIN glpi_plugin_ticketanswers_views v ON (
        v.users_id = $users_id AND
        v.ticket_id = t.id AND
        v.followup_id = t.id + 30000000
    )
WHERE
    v.id IS NULL
    AND t.status IN (1, 2)
    -- Modificado para usar diferença entre datas menor que 1 dia
    AND TIMESTAMPDIFF(DAY, t.date_creation, t.date_mod) < 1
    AND t.date_mod > DATE_SUB(NOW(), INTERVAL 7 DAY)

)
UNION
(
    -- Consulta otimizada para todas as validações (como validador ou solicitante)
    SELECT DISTINCT
        t.id AS ticket_id,
        t.name AS ticket_name,
        t.content AS ticket_content,
        tv.id AS followup_id,
        tv.submission_date AS notification_date,
        CONCAT('Solicitação de validação: ', IF(tv.comment_submission IS NOT NULL AND tv.comment_submission != '', 
               tv.comment_submission, 'Sem comentários adicionais.')) AS followup_content,
        CASE
            WHEN tv.users_id_validate = $users_id THEN u_requester.name
            ELSE u_validator.name
        END AS user_name,
        NULL AS group_name,
        NULL AS refuse_reason,
        CASE
            WHEN tv.users_id_validate = $users_id THEN 'validation'
            ELSE 'validation_request'
        END AS notification_type
    FROM
        glpi_ticketvalidations tv
        INNER JOIN glpi_tickets t ON tv.tickets_id = t.id AND t.status != 6 -- Excluir chamados fechados
        LEFT JOIN glpi_users u_requester ON tv.users_id = u_requester.id
        LEFT JOIN glpi_users u_validator ON tv.users_id_validate = u_validator.id
        LEFT JOIN glpi_plugin_ticketanswers_views v ON (
            v.users_id = $users_id AND
            v.ticket_id = t.id AND
            v.followup_id = tv.id
        )
    WHERE
        tv.status = 2 -- Aguardando validação
        AND v.id IS NULL
        AND (
            tv.users_id_validate = $users_id  -- Sou o validador
            OR EXISTS (
                SELECT 1 FROM glpi_tickets_users tu
                WHERE tu.tickets_id = t.id AND tu.users_id = $users_id AND tu.type = 1
            )  -- Sou o solicitante
        )
        AND tv.submission_date > DATE_SUB(NOW(), INTERVAL 90 DAY)  -- Limitar por período
)

UNION(
    -- Notificações de respostas de validação (aprovada ou recusada) para quem solicitou
    SELECT DISTINCT
        t.id AS ticket_id,
        t.name AS ticket_name,
        t.content AS ticket_content,
        tv.id AS followup_id,
        tv.validation_date AS notification_date,
        CASE
            WHEN tv.status = 3 THEN CONCAT('Sua solicitação de validação foi APROVADA', 
                                         IF(tv.comment_validation IS NOT NULL AND tv.comment_validation != '',
                                            CONCAT(': ', tv.comment_validation), 
                                            ': Sem comentários adicionais.'))
            WHEN tv.status = 4 THEN CONCAT('Sua solicitação de validação foi RECUSADA', 
                                         IF(tv.comment_validation IS NOT NULL AND tv.comment_validation != '',
                                            CONCAT(': ', tv.comment_validation), 
                                            ': Sem comentários de recusa.'))
            ELSE IF(tv.comment_validation IS NOT NULL AND tv.comment_validation != '',
                    tv.comment_validation, 'Validação respondida sem comentários.')
        END AS followup_content,
        u_validator.name AS user_name,
        NULL AS group_name,
        tv.comment_validation AS refuse_reason,
        CASE
            WHEN tv.status = 3 THEN 'validation_approved'
            WHEN tv.status = 4 THEN 'validation_refused'
            ELSE 'validation_answered'
        END AS notification_type
    FROM
        glpi_tickets t
        INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.users_id = $users_id AND tu.type = 1
        INNER JOIN glpi_ticketvalidations tv ON t.id = tv.tickets_id AND tv.users_id = $users_id
        LEFT JOIN glpi_users u_validator ON tv.users_id_validate = u_validator.id
        LEFT JOIN glpi_plugin_ticketanswers_views v ON (
            v.users_id = $users_id AND
            v.ticket_id = t.id AND
            v.followup_id = CONCAT('validation_response_', tv.id)
        )
    WHERE
        t.status != 6 -- Excluir chamados fechados
        AND (tv.status = 3 OR tv.status = 4) -- Aprovado (3) ou Recusado (4)
        AND v.id IS NULL
        AND tv.validation_date > DATE_SUB(NOW(), INTERVAL 30 DAY)
)
UNION(
    -- Notificações de respostas para quem solicitou validação
    SELECT
        t.id AS ticket_id,
        t.name AS ticket_name,
        t.content AS ticket_content,
        tv.id AS followup_id,
        tv.validation_date AS notification_date,
        CASE
            WHEN tv.status = 3 THEN CONCAT('Sua validação foi APROVADA', 
                                         IF(tv.comment_validation IS NOT NULL AND tv.comment_validation != '',
                                            CONCAT(': ', tv.comment_validation), 
                                            ': Sem comentários adicionais.'))
            WHEN tv.status = 4 THEN CONCAT('Sua validação foi RECUSADA', 
                                         IF(tv.comment_validation IS NOT NULL AND tv.comment_validation != '',
                                            CONCAT(': ', tv.comment_validation), 
                                            ': Sem comentários de recusa.'))
            ELSE IF(tv.comment_validation IS NOT NULL, tv.comment_validation, 'Sem comentários.')
        END AS followup_content,
        u.name AS user_name,
        NULL AS group_name,
        NULL AS refuse_reason,
        CASE
            WHEN tv.status = 3 THEN 'validation_approved'
            WHEN tv.status = 4 THEN 'validation_refused'
            ELSE 'validation_request_response'
        END AS notification_type
    FROM
        glpi_tickets t
        INNER JOIN glpi_ticketvalidations tv ON t.id = tv.tickets_id
        LEFT JOIN glpi_users u ON tv.users_id_validate = u.id
        LEFT JOIN glpi_plugin_ticketanswers_views v ON (
            v.users_id = $users_id AND
            v.ticket_id = t.id AND
            v.followup_id = tv.id
        )
    WHERE
        t.status != 6 -- Excluir chamados fechados
        AND tv.users_id = $users_id
        AND (tv.status = 3 OR tv.status = 4)
        AND v.id IS NULL
        AND tv.validation_date > DATE_SUB(NOW(), INTERVAL 30 DAY)
)
UNION(
    -- Notificações de respostas de validação (aprovadas ou recusadas) para requerentes do ticket
    SELECT
        t.id AS ticket_id,
        t.name AS ticket_name,
        t.content AS ticket_content,
        tv.id AS followup_id,
        tv.validation_date AS notification_date,
        CASE
            WHEN tv.status = 3 THEN CONCAT('O chamado foi VALIDADO por ', u_validator.name,
                                         IF(tv.comment_validation IS NOT NULL AND tv.comment_validation != '',
                                            CONCAT(': ', tv.comment_validation), 
                                            ': Sem comentários adicionais.'))
            WHEN tv.status = 4 THEN CONCAT('O chamado foi RECUSADO por ', u_validator.name, 
                                         IF(tv.comment_validation IS NOT NULL AND tv.comment_validation != '',
                                            CONCAT(': ', tv.comment_validation), 
                                            ': Sem comentários de recusa.'))
            ELSE IF(tv.comment_validation IS NOT NULL, tv.comment_validation, 'Validação sem comentários.')
        END AS followup_content,
        u_validator.name AS user_name,
        NULL AS group_name,
        NULL AS refuse_reason,
        'validation_response' AS notification_type
    FROM
        glpi_tickets t
        INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.users_id = $users_id AND tu.type = 1
        INNER JOIN glpi_ticketvalidations tv ON t.id = tv.tickets_id
        LEFT JOIN glpi_users u_validator ON tv.users_id_validate = u_validator.id
        LEFT JOIN glpi_plugin_ticketanswers_views v ON (
            v.users_id = $users_id AND
            v.ticket_id = t.id AND
            v.followup_id = CONCAT('validation_response_', tv.id)
        )
    WHERE
        t.status != 6 -- Excluir chamados fechados
        AND (tv.status = 3 OR tv.status = 4) -- Aprovado (3) ou Recusado (4)
        AND v.id IS NULL
        AND tv.validation_date > DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND tv.users_id <> $users_id -- Não mostrar validações solicitadas pelo próprio usuário
        AND tv.users_id_validate <> $users_id -- Não mostrar validações respondidas pelo próprio usuário
)
UNION
(
    -- Notificações de respostas de técnicos em chamados abertos pelo usuário
    SELECT
        t.id AS ticket_id,
        t.name AS ticket_name,
        t.content AS ticket_content,
        tf.id AS followup_id,
        tf.date AS notification_date,
        tf.content AS followup_content,
        u.name AS user_name,
        NULL AS group_name,
        NULL AS refuse_reason,
        'technician_response' AS notification_type
    FROM
        glpi_tickets t
        INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 1 AND tu.users_id = $users_id
        INNER JOIN glpi_itilfollowups tf ON t.id = tf.items_id AND tf.itemtype = 'Ticket'
        LEFT JOIN glpi_users u ON tf.users_id = u.id
        LEFT JOIN glpi_plugin_ticketanswers_views v ON (
            v.users_id = $users_id AND
            v.followup_id = tf.id
        )
    WHERE
        tf.users_id <> $users_id
        AND v.id IS NULL
        AND t.status != 6
        AND tf.is_private = 0  -- Apenas acompanhamentos públicos
        -- Verificar se o autor do followup é um técnico
        AND EXISTS (
            SELECT 1 FROM glpi_tickets_users tech_user
            WHERE tech_user.tickets_id = t.id 
            AND tech_user.users_id = tf.users_id
            AND tech_user.type = 2
        )
)
UNION
(
    -- Notificações de mudanças de status em chamados do usuário
    SELECT
        t.id AS ticket_id,
        t.name AS ticket_name,
        t.content AS ticket_content,
        0 AS followup_id,
        t.date_mod AS notification_date,
        CONCAT('Status alterado para: ',
               CASE
                  WHEN t.status = 1 THEN 'Novo'
                  WHEN t.status = 2 THEN 'Em atendimento'
                  WHEN t.status = 4 THEN 'Pendente'
                  WHEN t.status = 5 THEN 'Solucionado'
                  WHEN t.status = 6 THEN 'Fechado'
                  ELSE 'Outro status'
               END) AS followup_content,
        NULL AS user_name,
        NULL AS group_name,
        NULL AS refuse_reason,
        'status_change' AS notification_type
    FROM
        glpi_tickets t
        INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 1 AND tu.users_id = $users_id
        LEFT JOIN glpi_plugin_ticketanswers_views v ON (
            v.users_id = $users_id AND
            v.ticket_id = t.id AND
            (
                v.followup_id = CONCAT('status_', t.id, '_', t.status) OR
                v.followup_id = CONCAT('status_', t.id, '_any')
            )
        )
    WHERE
        v.id IS NULL
        AND t.status IN (2, 4, 5)  -- Em atendimento, Pendente, Solucionado
        AND t.date_mod > DATE_SUB(NOW(), INTERVAL 7 DAY)
)
UNION
(
    -- Notificações de chamados pendentes
    SELECT
        t.id AS ticket_id,
        t.name AS ticket_name,
        t.content AS ticket_content,
        0 AS followup_id,
        t.date_mod AS notification_date,
        'Chamado pendente' AS followup_content,
        u.name AS user_name,
        NULL AS group_name,
        NULL AS refuse_reason,
        'pending_reason' AS notification_type
    FROM
        glpi_tickets t
        INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 1 AND tu.users_id = $users_id
        LEFT JOIN glpi_users u ON t.users_id_recipient = u.id
        LEFT JOIN glpi_plugin_ticketanswers_views v ON (
            v.users_id = $users_id AND
            v.ticket_id = t.id AND
            v.followup_id = CONCAT('pending_', t.id)
        )
    WHERE
        v.id IS NULL
        AND t.status = 3
        AND t.waiting_duration > 0
        AND t.date_mod > DATE_SUB(NOW(), INTERVAL 7 DAY)
)";






// Consulta para contar o total de notificações únicas (sem LIMIT)
$count_query = "
SELECT COUNT(*) as total FROM (
    SELECT ticket_id, notification_type
    FROM ($combined_query_base) as inner_count_query
    GROUP BY ticket_id, notification_type
) as unique_notifications";

// Consulta principal com filtro para obter apenas a notificação mais recente de cada tipo por ticket
$combined_query = "
SELECT outer_query.*
FROM ($combined_query_base) AS outer_query
JOIN (
    -- Esta subconsulta identifica a notificação mais recente para cada combinação de chamado e tipo
    SELECT 
        ticket_id,
        notification_type,
        MAX(notification_date) AS max_date
    FROM ($combined_query_base) AS inner_query
    GROUP BY ticket_id, notification_type
) AS latest_notifications
ON outer_query.ticket_id = latest_notifications.ticket_id 
AND outer_query.notification_type = latest_notifications.notification_type
AND outer_query.notification_date = latest_notifications.max_date
ORDER BY outer_query.notification_date DESC
LIMIT $notifications_per_page";


// Executar a consulta de contagem
$count_result = $DB->doQuery($count_query);
$total_notifications = 0;
if ($count_result && $count_result->num_rows > 0) {
    $total_data = $count_result->fetch_assoc();
    $total_notifications = $total_data['total'];
}

// Executar a consulta principal
$result = $DB->doQuery($combined_query);
$numNotifications = $result->num_rows;

// Log para depuração
error_log("DEBUG: notifications_per_page=$notifications_per_page, total_notifications=$total_notifications, numNotifications=$numNotifications");

echo "<div id='ticket-notifications'>";
echo "<h2>Notificações</h2>";
echo "<script>
document.addEventListener('DOMContentLoaded', function() {
    // Buscar a contagem atual das notificações via AJAX
    $.ajax({
        url: '../ajax/check_all_notifications.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            // Verificar se temos dados válidos
            if (data && (data.count !== undefined || data.combined_count !== undefined)) {
                // Usar combined_count ou count, o que estiver disponível
                var notificationCount = data.combined_count !== undefined ? data.combined_count : data.count;
                
                // Atualizar o contador na página
                $('#notification-count').text(notificationCount);
                
                // Log para depuração
                console.log('Contador de notificações atualizado:', notificationCount);
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro ao obter contagem de notificações:', error);
        }
    });
});
</script>";
echo "<div id='notifications-container'>";

if ($result && $numNotifications > 0) {
    // Adiciona o botão "Marcar todos como lido" no topo da tabela
    /*echo "<div class='center' style='margin-bottom: 15px;'>";
    echo "<a href='javascript:void(0)' onclick='markAllAsRead()' class='btn btn-warning'>
            <i class='fas fa-check-double'></i> Marcar todos como lido
          </a>";*/
    
    // Adicionar seletor de quantidade por página
    echo "<div style='margin: 10px auto; text-align: center; display: flex; justify-content: center; align-items: center;'>";
    echo "<form method='get' action='' style='display: flex; align-items: center;'>";
    echo "<label for='per_page' style='margin-right: 10px;'>Notificações por página:</label>";
    echo "<select name='per_page' id='per_page' class='form-control' style='width: 80px;' onchange='this.form.submit()'>";
    foreach ([10, 50, 100, 150, 200] as $value) {
        $selected = ($value == $notifications_per_page) ? "selected" : "";
        echo "<option value='$value' $selected>$value</option>";
    }
    echo "</select>";
    echo "</form>";
    echo "</div>";

    
    echo "<table class='tab_cadre_fixehov'>";
    echo "<tr>";
    echo "<th>Ticket</th>";
    echo "<th>Nº do Chamado</th>";
    echo "<th>Data</th>";
    echo "<th>Tipo</th>";
    echo "<th>Usuário/Grupo</th>";
    echo "<th>Conteúdo</th>";
    echo "<th>Ações</th>";
    echo "</tr>";
    
    $i = 0;
    while ($data = $result->fetch_assoc()) {
        $notification_type = $data['notification_type'];
        // Determinar o ID da linha para cada tipo de notificação
$row_id = "";
if ($notification_type == 'followup' || $notification_type == 'refused') {
    // IDs simples para tipos básicos
    $row_id = "notification-row-" . $data['ticket_id'] . "-" . $data['followup_id'];
} elseif (strpos($notification_type, 'validation') !== false) {
    // Para tipos de validação, verificar se o ID já contém "validation_response_"
    if (is_string($data['followup_id']) && preg_match('/^validation_response_(\d+)$/', $data['followup_id'], $matches)) {
        // Extrair apenas o número da validação para o ID da linha
        $row_id = "notification-row-" . $data['ticket_id'] . "-" . $matches[1];
        // Adicionar log para depuração
        error_log("ID de validação convertido: original={$data['followup_id']}, novo={$matches[1]}");
    } else {
        // Usar o ID completo se não corresponder ao padrão
        $row_id = "notification-row-" . $data['ticket_id'] . "-" . $data['followup_id'];
    }
} else {
    // Para outros tipos como group, observer, etc.
    $row_id = "group-notification-row-" . $data['ticket_id'];
}
      
if ($notification_type == 'status_change') {
    echo "<tr id='notification-row-" . $data['ticket_id'] . "-" . $data['followup_id'] . "' 
          data-notification-type='status_change' 
          data-ticket-id='" . $data['ticket_id'] . "' 
          class='tab_bg_" . ($i % 2 + 1) . "'>";
} else {
    echo "<tr id='$row_id' class='tab_bg_" . ($i % 2 + 1) . "'>";
}
        // Ticket
        echo "<td>" . $data['ticket_name'] . "</td>";

        // Nº do Chamado
        echo "<td>" . $data['ticket_id'] . "</td>";
        
        // Data
        echo "<td>" . Html::convDateTime($data['notification_date']) . "</td>";
        
        // Tipo de notificação
echo "<td>";
switch ($notification_type) {
    // Tipos de Resposta/Interação
    case 'followup':
        echo "<span class='badge bg-info text-white'>Resposta</span>";
        break;
    case 'refused':
        echo "<span class='badge bg-danger text-white'>Recusado</span>";
        break;
    
    // Tipos de Atribuição
    case 'group':
        echo "<span class='badge bg-success text-white'>Novo chamado</span>";
        break;
    case 'assigned':
        echo "<span class='badge bg-warning text-white'>Atribuído</span>";
        break;
    
    // Tipos de Observador
    case 'observer':
        echo "<span class='badge bg-primary text-white'>Observador</span>";
        break;
    case 'group_observer':
        echo "<span class='badge bg-primary text-white'>" . "Grupo observador" . "</span>";
        break;
    
    // Tipos de Validação
    case 'validation':
        echo "<span class='badge bg-warning text-white'>" . "Validação" . "</span>";
        break;
    case 'validation_request':
        echo "<span class='badge bg-info text-white'>" . "Solicitação de Validação" . "</span>";
        break;
    case 'validation_request_response':
        // Verificar se a validação foi aprovada ou recusada
        $validation_status = isset($data['validation_status']) ? $data['validation_status'] : 0;
        if ($validation_status == 3) { // Aprovado
            echo "<span class='badge bg-success text-white'>" . "Validação Aprovada" . "</span>";
        } else if ($validation_status == 4) { // Recusado
            echo "<span class='badge bg-danger text-white'>" . "Validação Recusada" . "</span>";
        } else {
            echo "<span class='badge bg-secondary text-white'>" . "Resp. Validação" . "</span>";
        }
        break;
    case 'validation_response':
        // Verificar se a validação foi aprovada ou recusada
        $validation_status = isset($data['validation_status']) ? $data['validation_status'] : 0;
        if ($validation_status == 3) { // Aprovado
            echo "<span class='badge bg-success text-white'>" . "Validação Aprovada" . "</span>";
        } else if ($validation_status == 4) { // Recusado
            echo "<span class='badge bg-danger text-white'>" . "Validação Recusada" . "</span>";
        } else {
            echo "<span class='badge bg-secondary text-white'>" . "Resp. Validação" . "</span>";
        }
        break;
    case 'validation_approved':
        echo "<span class='badge bg-success text-white'>" . "Validação Aprovada" . "</span>";
        break;
    case 'validation_refused':
        echo "<span class='badge bg-danger text-white'>" . "Validação Recusada" . "</span>";
        break;
    
     // Adicionar os novos tipos de notificação
     case 'technician_response':
        echo "<span class='badge bg-secondary text-white'>" . "Resposta técnica" . "</span>";
        break;
    case 'status_change':
        echo "<span class='badge bg-primary text-white'>" . "Status do chamado" . "</span>";
        break;
    case 'pending_reason':
        echo "<span class='badge bg-warning text-white'>" . "Pendente" . "</span>";
        break;
    default:
        echo "<span class='badge bg-secondary text-white'>" . "Outro" . "</span>";
}
echo "</td>";

            
// Usuário/Grupo
echo "<td>";
if ($notification_type == 'followup') {
    echo $data['user_name']; // Nome do usuário que respondeu
} elseif ($notification_type == 'group' || $notification_type == 'group_observer') {
    echo $data['user_name'] . " <small>(" . "para grupo" . ": " . $data['group_name'] . ")</small>";
} elseif ($notification_type == 'refused') {
    echo $data['user_name'] . " <small>(" . "recusou o chamado" . ")</small>";
} elseif ($notification_type == 'validation' || $notification_type == 'validation_request') {
    echo $data['user_name'] . " <small>(" . "solicitou validação" . ")</small>";
} elseif ($notification_type == 'validation_approved' || 
         ($notification_type == 'validation_request_response' && isset($data['validation_status']) && $data['validation_status'] == 3) ||
         ($notification_type == 'validation_response' && isset($data['validation_status']) && $data['validation_status'] == 3)) {
    echo $data['user_name'] . " <small>(" . "aprovou a validação" . ")</small>";
} elseif ($notification_type == 'validation_refused' || 
         ($notification_type == 'validation_request_response' && isset($data['validation_status']) && $data['validation_status'] == 4) ||
         ($notification_type == 'validation_response' && isset($data['validation_status']) && $data['validation_status'] == 4)) {
    echo $data['user_name'] . " <small>(" . "recusou a validação" . ")</small>";
} elseif ($notification_type == 'validation_request_response') {
    echo $data['user_name'] . " <small>(" . "respondeu sua solicitação de validação" . ")</small>";
} elseif ($notification_type == 'validation_response') {
    echo $data['user_name'] . " <small>(" . "respondeu à validação" . ")</small>";
} else {
    echo $data['user_name'];
}
echo "</td>";
    
            // Conteúdo
echo "<td>";
if ($notification_type == 'refused' && !empty($data['refuse_reason'])) {
    // Para chamados recusados, mostrar a razão da recusa
    $refuse_content = $data['refuse_reason'];
    $decoded_content = html_entity_decode($refuse_content);
    $plain_text = preg_replace('/<.*?>/', '', $decoded_content);
    echo "<strong>" . "Motivo da recusa" . ":</strong> ";
    echo Html::resume_text($plain_text, 100);
} elseif ($notification_type == 'followup') {
    // Decodificar entidades HTML
    $followup_content = $data['followup_content'];
    $decoded_content = html_entity_decode($followup_content);
    // Extrair texto entre tags usando regex
    $plain_text = preg_replace('/<.*?>/', '', $decoded_content);
    echo Html::resume_text($plain_text, 100);
} elseif (in_array($notification_type, ['validation', 'validation_request', 
                                        'validation_approved', 'validation_refused', 
                                        'validation_response', 'validation_request_response'])) {
    // Para todos os tipos de validação, usar diretamente o followup_content
    if (!empty($data['followup_content'])) {
        $content = $data['followup_content'];
        $decoded_content = html_entity_decode($content);
        $plain_text = preg_replace('/<.*?>/', '', $decoded_content);
        
        // Determinar o prefixo apropriado baseado no tipo
        if ($notification_type == 'validation' || $notification_type == 'validation_request') {
            echo "<strong>" . "Solicitação:" . "</strong> ";
        } elseif ($notification_type == 'validation_approved') {
            echo "<strong>" . "Aprovação:" . "</strong> ";
        } elseif ($notification_type == 'validation_refused') {
            echo "<strong>" . "Recusa:" . "</strong> ";
        } elseif ($notification_type == 'validation_response' || $notification_type == 'validation_request_response') {
            echo "<strong>" . "Resposta:" . "</strong> ";
        }
        
        echo Html::resume_text($plain_text, 100);
    } else {
        echo "Sem conteúdo disponível";
    }
} else {
    // Para outros tipos, mostrar um resumo do conteúdo do ticket
    $content = !empty($data['followup_content']) ? $data['followup_content'] : $data['ticket_content'];
    $decoded_content = html_entity_decode($content);
    $plain_text = preg_replace('/<.*?>/', '', $decoded_content);
    echo Html::resume_text($plain_text, 100);
}
echo "</td>";
            
            // Ações
            echo "<td class='center'>";
            echo "<div class='btn-group'>";
            switch ($notification_type) {
                case 'followup':
                    // Link para ver na mesma aba - Usar a função unificada
                    echo "<a href='javascript:void(0)' onclick='markNotificationAsRead(" . $data['ticket_id'] . ", " . $data['followup_id'] . ", \"followup\", false)' class='btn btn-info' title='" . "Ver chamado" . "'>
                    <i class='fas fa-eye'></i>
                  </a>";
                    // Link para ver em nova aba
                    echo "<a href='#' onclick='markNotificationAsRead(" . $data['ticket_id'] . ", " . $data['followup_id'] . ", \"followup\", true); return false;' class='btn btn-secondary' title='" . "Ver em nova aba" . "'>
                    <i class='fas fa-external-link-alt'></i>
                  </a>";
                    break;
                
                case 'group':
                    // Link para ver na mesma aba - Usar função unificada
                    echo "<a href='javascript:void(0)' onclick='markNotificationAsRead(" . $data['ticket_id'] . ", 0, \"group\", false)' class='btn btn-info' title='" . "Ver chamado" . "'>
                    <i class='fas fa-eye'></i>
                  </a>";
                    
                    // Botão para assumir o chamado
                    echo "<a href='javascript:void(0)' onclick='assignTicketToMe(" . $data['ticket_id'] . ")' class='btn btn-success' title='" . "Assumir chamado" . "'>
                    <i class='fas fa-user-check'></i>
                  </a>";
                    break;
                
                case 'observer':
                case 'group_observer':
                    // Link para ver na mesma aba
                    echo "<a href='javascript:void(0)' onclick='markNotificationAsRead(" . $data['ticket_id'] . ", 0, \"" . $notification_type . "\", false)' class='btn btn-info' title='" . "Ver chamado" . "'>
                    <i class='fas fa-eye'></i>
                  </a>";
                    // Link para ver em nova aba - CORREÇÃO
                    echo "<a href='#' onclick='markNotificationAsRead(" . $data['ticket_id'] . ", 0, \"" . $notification_type . "\", true); return false;' class='btn btn-secondary' title='" . "Ver em nova aba" . "'>
                    <i class='fas fa-external-link-alt'></i>
                  </a>";
                    break;
                    
                case 'refused':
                    // Link para ver na mesma aba
                    echo "<a href='javascript:void(0)' onclick='markNotificationAsRead(" . $data['ticket_id'] . ", " . $data['followup_id'] . ", \"refused\", false)' class='btn btn-info' title='" . "Ver chamado" . "'>
                    <i class='fas fa-eye'></i>
                  </a>";
                    // Link para ver em nova aba
                    echo "<a href='#' onclick='markNotificationAsRead(" . $data['ticket_id'] . ", " . $data['followup_id'] . ", \"followup\", true); return false;' class='btn btn-secondary' title='" . "Ver em nova aba" . "'>
                    <i class='fas fa-external-link-alt'></i>
                  </a>";
                    break;
                    
                case 'assigned':
                    // Link para ver na mesma aba
                    echo "<a href='javascript:void(0)' onclick='markNotificationAsRead(" . $data['ticket_id'] . ", 0, \"assigned\", false)' class='btn btn-info' title='" . "Ver chamado" . "'>
                    <i class='fas fa-eye'></i>
                  </a>";
                    // Link para ver em nova aba
                    echo "<a href='#' onclick='markNotificationAsRead(" . $data['ticket_id'] . ", 0, \"assigned\", true); return false;' class='btn btn-secondary' title='" . "Ver em nova aba" . "'>
                    <i class='fas fa-external-link-alt'></i>
                  </a>";
                    break;

                case 'validation':
                    // Link para ver na mesma aba
                    echo "<a href='javascript:void(0)' onclick='markNotificationAsRead(" . $data['ticket_id'] . ", " . $data['followup_id'] . ", \"validation\", false)' class='btn btn-info' title='" . "Ver chamado" . "'>
                    <i class='fas fa-eye'></i>
                  </a>";
                    // Link para ver em nova aba
                    echo "<a href='#' onclick='markNotificationAsRead(" . $data['ticket_id'] . ", " . $data['followup_id'] . ", \"validation\", true); return false;' class='btn btn-secondary' title='" . "Ver em nova aba" . "'>
                    <i class='fas fa-external-link-alt'></i>
                  </a>";
                    break;
                
                case 'validation_request':
                    // Link para ver na mesma aba
                    echo "<a href='javascript:void(0)' onclick='markNotificationAsRead(" . $data['ticket_id'] . ", " . $data['followup_id'] . ", \"validation_request\", false)' class='btn btn-info' title='" . "Ver chamado" . "'>
                    <i class='fas fa-eye'></i>
                  </a>";
                    // Link para ver em nova aba
                    echo "<a href='#' onclick='markNotificationAsRead(" . $data['ticket_id'] . ", " . $data['followup_id'] . ", \"validation_request\", true); return false;' class='btn btn-secondary' title='" . "Ver em nova aba" . "'>
                    <i class='fas fa-external-link-alt'></i>
                  </a>";
                    break;
                case 'validation_approved':
                case 'validation_refused':
                    // Link para ver na mesma aba
                    echo "<a href='javascript:void(0)' onclick='markNotificationAsRead(" . $data['ticket_id'] . ", " . $data['followup_id'] . ", \"" . $notification_type . "\", false)' class='btn btn-info' title='" . "Ver chamado" . "'>
                    <i class='fas fa-eye'></i>
                  </a>";
                    // Link para ver em nova aba
                        echo "<a href='#' onclick='markNotificationAsRead(" . $data['ticket_id'] . ", " . $data['followup_id'] . ", \"" . $notification_type . "\", true); return false;' class='btn btn-secondary' title='" . "Ver em nova aba" . "'>
                        <i class='fas fa-external-link-alt'></i>
                      </a>";
                        break;
                case 'technician_response':
                case 'status_change':
                case 'pending_reason':
                    // Link para ver na mesma aba
                    echo "<a href='javascript:void(0)' onclick='markNotificationAsRead(" . $data['ticket_id'] . ", " . $data['followup_id'] . ", \"" . $notification_type . "\", false)' class='btn btn-info' title='" . "Ver chamado" . "'>
                    <i class='fas fa-eye'></i>
                      </a>";
                    // Link para ver em nova aba
                    echo "<a href='#' onclick='markNotificationAsRead(" . $data['ticket_id'] . ", " . $data['followup_id'] . ", \"" . $notification_type . "\", true); return false;' class='btn btn-secondary' title='" . "Ver em nova aba" . "'>
                    <i class='fas fa-external-link-alt'></i>
                      </a>";
                      break;
            }
            echo "</div>";
            echo "</td>";
            echo "</tr>";
            $i++;
        }
        
        echo "</table>";
        
        // Adicionar informação de paginação e seletor no final
        echo "<div style='margin: 20px auto; text-align: center;'>";
        echo "<p>" . sprintf("Exibindo %d de %d notificações", min($numNotifications, $notifications_per_page), $total_notifications) . "</p>";
        echo "<div style='display: flex; justify-content: center; align-items: center;'>";
        echo "<form method='get' action='' style='display: flex; align-items: center;'>";
        echo "<label for='per_page_bottom' style='margin-right: 10px;'>" . "Notificações por página:" . "</label>";
        echo "<select name='per_page' id='per_page_bottom' class='form-control' style='width: 80px;' onchange='this.form.submit()'>";
        foreach ([10, 50, 100, 150, 200] as $value) {
            $selected = ($value == $notifications_per_page) ? "selected" : "";
            echo "<option value='$value' $selected>$value</option>";
        }
        echo "</select>";
        echo "</form>";
        echo "</div>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-info'>" . "Não há novas notificações" . "</div>";
    }
    
    echo "</div>"; // Fim do container de notificações
    echo "</div>"; // Fim do container de ticket-notifications
    echo "</div>"; // Fim do container central
    

    echo "<script>
    $(document).ready(function() {
        // Bloquear botão direito menu de contexto em toda a página de notificações
        $('#notifications-container, .tab_cadre_fixehov').on('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });
        
        // Interceptar Ctrl+clique especificamente nos links
        $('#notifications-container a, .tab_cadre_fixehov a').on('click', function(e) {
            if (e.ctrlKey || e.metaKey) {
                e.preventDefault();
                e.stopPropagation(); // Impede a propagação do evento
                
                // Mostrar mensagem
                showBlockedMessage();
                
                return false;
            }
        });
        
        // Interceptar mousedown para detectar clique do botão do meio
        $('#notifications-container a, .tab_cadre_fixehov a').on('mousedown', function(e) {
            // Botão do meio = 1, botão direito = 2
            if (e.button === 1 || e.button === 2 || (e.button === 0 && (e.ctrlKey || e.metaKey))) {
                e.preventDefault();
                e.stopPropagation();
                
                // Mostrar mensagem
                showBlockedMessage();
                
                return false;
            }
        });
        
        // Também prevenir o comportamento padrão de arrastar links
        $('#notifications-container a, .tab_cadre_fixehov a').on('dragstart', function(e) {
            e.preventDefault();
            return false;
        });
        
        // Função para mostrar mensagem
        function showBlockedMessage() {
            if (!$('#context-menu-warning').length) {
                $('<div id=\"context-menu-warning\" style=\"position:fixed; bottom:10px; right:10px; background:#ffeeee; padding:10px; border:1px solid #ffcccc; border-radius:5px; z-index:9999;\">Botão direito, botão do meio e Ctrl+clique estão desabilitados nesta página.</div>')
                    .appendTo('body')
                    .delay(3000)
                    .fadeOut(500, function() { $(this).remove(); });
            }
        }
        
        // Adicionar atributo oncontextmenu a todos os links (usando método attr do jQuery)
        $('#notifications-container a, .tab_cadre_fixehov a').attr('oncontextmenu', 'return false');
    });
</script>";

Html::footer();

// Se for uma requisição AJAX, retornar apenas o conteúdo HTML sem o header/footer
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    exit();
}
