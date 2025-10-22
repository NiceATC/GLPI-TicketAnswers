<?php

include ("../../../inc/includes.php");

Session::checkLoginUser();

// Declarar variável global do banco de dados
global $DB;

// Obtém o ID do usuário logado
$users_id = Session::getLoginUserID();

// Obter preferências de notificação do usuário
$query = "SELECT * FROM glpi_plugin_ticketanswers_notification_prefs WHERE users_id = $users_id";
$result = $DB->doQuery($query);

// Valores padrão (todos habilitados)
$user_prefs = [
    'followup' => 1,
    'refused' => 1,
    'group_tickets' => 1,
    'observer' => 1,
    'group_observer' => 1,
    'assigned_tech' => 1
];

// Se o usuário já tem preferências, usar as dele
if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();
    $user_prefs = [
        'followup' => (int)$data['followup'],
        'refused' => (int)$data['refused'],
        'group_tickets' => (int)$data['group_tickets'],
        'observer' => (int)$data['observer'],
        'group_observer' => (int)$data['group_observer'],
        'assigned_tech' => (int)$data['assigned_tech']
    ];
}


// Verifica se há um timestamp 'since' na requisição
$since = isset($_GET['since']) ? intval($_GET['since']) : 0;

// Adicionar log para depuração
error_log("DEBUG: Iniciando verificação de notificações para usuário $users_id");

// Consulta para encontrar tickets atribuídos ao técnico com respostas não vistas
// Modificada para corresponder à consulta usada na página de notificações
$followup_query = "SELECT COUNT(DISTINCT tf.id) as followup_count
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
    AND tf.date > (
        SELECT
            COALESCE(MAX(date), '1970-01-01')
        FROM
            glpi_itilfollowups tf2
        WHERE
            tf2.items_id = t.id
            AND tf2.itemtype = 'Ticket'
            AND tf2.users_id = $users_id
    )";

// Consulta para encontrar tickets recusados
$refused_query = "SELECT COUNT(DISTINCT its.id) as refused_count
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
    LEFT JOIN glpi_plugin_ticketanswers_views v ON (
        v.users_id = $users_id AND
        v.followup_id = its.id
    )
WHERE
    its.users_id_approval <> $users_id  -- ADICIONAR ESTA LINHA
    AND v.id IS NULL
    AND t.status != 6";

// Consulta para encontrar tickets novos atribuídos a grupos técnicos dos quais o usuário faz parte
$group_query = "SELECT COUNT(DISTINCT t.id) as group_count
FROM
    glpi_tickets t
    INNER JOIN glpi_groups_tickets gt ON t.id = gt.tickets_id AND gt.type = 2
    INNER JOIN glpi_groups_users gu ON gt.groups_id = gu.groups_id AND gu.users_id = $users_id
    LEFT JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.users_id = $users_id AND tu.type = 2
    LEFT JOIN glpi_plugin_ticketanswers_views v ON (
        v.users_id = $users_id AND
        v.ticket_id = t.id AND
        v.followup_id = t.id + 10000000
    )
WHERE
    tu.id IS NULL
    AND v.id IS NULL
    AND t.status IN (1, 2)
    AND t.date_creation > DATE_SUB(NOW(), INTERVAL 7 DAY)";

// Consulta para encontrar tickets onde o usuário é observador
$observer_query = "SELECT COUNT(DISTINCT t.id) as observer_count
FROM
    glpi_tickets t
    INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 3 AND tu.users_id = $users_id
    LEFT JOIN glpi_plugin_ticketanswers_views v ON (
        v.users_id = $users_id AND
        v.ticket_id = t.id AND
        v.followup_id = t.id + 20000000
    )
WHERE
    v.id IS NULL
    AND t.status IN (1, 2)
    AND t.date_creation > DATE_SUB(NOW(), INTERVAL 7 DAY)";

// Consulta para encontrar tickets onde o grupo do usuário é observador
$group_observer_query = "SELECT COUNT(DISTINCT t.id) as group_observer_count
FROM
    glpi_tickets t
    INNER JOIN glpi_groups_tickets gt ON t.id = gt.tickets_id AND gt.type = 3
    INNER JOIN glpi_groups_users gu ON gt.groups_id = gu.groups_id AND gu.users_id = $users_id
    LEFT JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.users_id = $users_id
    LEFT JOIN glpi_plugin_ticketanswers_views v ON (
        v.users_id = $users_id AND
        v.ticket_id = t.id AND
        v.followup_id = t.id + 20000000
    )
WHERE
    tu.id IS NULL
    AND v.id IS NULL
    AND t.status IN (1, 2)
    AND t.date_creation > DATE_SUB(NOW(), INTERVAL 7 DAY)";

// Consulta para encontrar tickets recém atribuídos ao técnico
$assigned_tech_query = "SELECT COUNT(DISTINCT t.id) as assigned_tech_count
FROM
    glpi_tickets t
    INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 2 AND tu.users_id = $users_id
    LEFT JOIN glpi_plugin_ticketanswers_views v ON (
        v.users_id = $users_id AND
        v.ticket_id = t.id AND
        v.followup_id = t.id + 30000000
    )
WHERE
    v.id IS NULL
    AND t.status IN (1, 2)
    AND t.date_mod > DATE_SUB(NOW(), INTERVAL 7 DAY)";

// Consulta para encontrar validações pendentes - baseada na consulta de diagnóstico que funciona
$validation_query = "SELECT COUNT(DISTINCT tv.id) as validation_count
FROM glpi_ticketvalidations tv 
JOIN glpi_tickets t ON tv.tickets_id = t.id
LEFT JOIN glpi_plugin_ticketanswers_views v ON (
    v.users_id = $users_id AND 
    v.followup_id = tv.id
)
WHERE 
    tv.users_id_validate = $users_id
    AND tv.status = 2
    AND t.status != 6
    AND v.id IS NULL";  

// Consulta para encontrar respostas a validações solicitadas pelo usuário
$validation_response_query = "SELECT COUNT(DISTINCT tv.id) as validation_response_count
FROM
    glpi_tickets t
    INNER JOIN glpi_ticketvalidations tv ON t.id = tv.tickets_id AND tv.users_id = $users_id
    LEFT JOIN glpi_plugin_ticketanswers_views v ON (
        v.users_id = $users_id AND
        v.ticket_id = t.id AND
        v.followup_id = CONCAT('validation_response_', tv.id)
    )
WHERE
    t.status != 6  -- Excluir chamados fechados
    AND (tv.status = 3 OR tv.status = 4)  -- Aprovado ou recusado
    AND v.id IS NULL
    AND tv.validation_date > DATE_SUB(NOW(), INTERVAL 30 DAY)";

// Consulta para encontrar respostas a solicitações de validação
$validation_request_response_query = "SELECT COUNT(DISTINCT tv.id) as validation_request_response_count
FROM
    glpi_tickets t
    INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.users_id = $users_id AND tu.type = 1
    INNER JOIN glpi_ticketvalidations tv ON t.id = tv.tickets_id
    LEFT JOIN glpi_plugin_ticketanswers_views v ON (
        v.users_id = $users_id AND
        v.ticket_id = t.id AND
        v.followup_id = CONCAT('validation_request_response_', tv.id)
    )
WHERE
    t.status != 6  -- Excluir chamados fechados
    AND (tv.status = 3 OR tv.status = 4)  -- Aprovado ou recusado
    AND v.id IS NULL
    AND tv.users_id <> $users_id
    AND tv.users_id_validate <> $users_id
    AND tv.validation_date > DATE_SUB(NOW(), INTERVAL 30 DAY)";

// Consulta para encontrar respostas de técnicos em chamados do usuário
$technician_response_query = "SELECT COUNT(DISTINCT tf.id) as technician_response_count
FROM
    glpi_tickets t
    INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 1 AND tu.users_id = $users_id
    INNER JOIN glpi_itilfollowups tf ON t.id = tf.items_id AND tf.itemtype = 'Ticket'
    LEFT JOIN glpi_plugin_ticketanswers_views v ON (
        v.users_id = $users_id AND
        v.followup_id = tf.id
    )
WHERE
    v.id IS NULL
    AND t.status != 6
    AND tf.is_private = 0
    AND EXISTS (
        SELECT 1 FROM glpi_tickets_users tech_user
        WHERE tech_user.tickets_id = t.id
        AND tech_user.users_id = tf.users_id
        AND tech_user.type = 2
    )";  // Adicionado fechamento de aspas aqui

// Consulta para encontrar mudanças de status em chamados do usuário
$status_change_query = "SELECT COUNT(DISTINCT t.id) as status_change_count
FROM
    glpi_tickets t
    INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 1 AND tu.users_id = $users_id
    LEFT JOIN glpi_plugin_ticketanswers_views v ON (
        v.users_id = $users_id AND
        v.ticket_id = t.id AND
        v.followup_id = CONCAT('status_', t.id, '_', t.status)
    )
WHERE
    v.id IS NULL
    AND t.status IN (2, 3, 4)
    AND t.date_mod > DATE_SUB(NOW(), INTERVAL 7 DAY)";  // Adicionado fechamento de aspas aqui

// Consulta para encontrar motivos de pendência em chamados do usuário
$pending_reason_query = "SELECT COUNT(DISTINCT t.id) as pending_reason_count
FROM
    glpi_tickets t
    INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 1 AND tu.users_id = $users_id
    LEFT JOIN glpi_pendingreasons pt ON t.pendingreasons_id = pt.id
    LEFT JOIN glpi_plugin_ticketanswers_views v ON (
        v.users_id = $users_id AND
        v.ticket_id = t.id AND
        v.followup_id = CONCAT('pending_', t.id, '_', t.pendingreasons_id)
    )
WHERE
    v.id IS NULL
    AND t.status = 3
    AND t.pendingreasons_id > 0
    AND t.date_mod > DATE_SUB(NOW(), INTERVAL 7 DAY)";  // Adicionado fechamento de aspas aqui


// Executar as novas consultas
$technician_response_result = $DB->doQuery($technician_response_query);
$status_change_result = $DB->doQuery($status_change_query);
$pending_reason_result = $DB->doQuery($pending_reason_query);

// Inicializar os novos contadores
$technician_response_count = 0;
$status_change_count = 0;
$pending_reason_count = 0;

// Obter os resultados das novas contagens
if ($technician_response_result && $technician_response_result->num_rows > 0) {
    $technician_response_data = $technician_response_result->fetch_assoc();
    $technician_response_count = $technician_response_data['technician_response_count'];
}

if ($status_change_result && $status_change_result->num_rows > 0) {
    $status_change_data = $status_change_result->fetch_assoc();
    $status_change_count = $status_change_data['status_change_count'];
}

if ($pending_reason_result && $pending_reason_result->num_rows > 0) {
    $pending_reason_data = $pending_reason_result->fetch_assoc();
    $pending_reason_count = $pending_reason_data['pending_reason_count'];
}



// Executar as consultas de validação
$validation_result = $DB->doQuery($validation_query);
$validation_response_result = $DB->doQuery($validation_response_query);
$validation_request_response_result = $DB->doQuery($validation_request_response_query);

// Obter os resultados
$validation_count = 0;
$validation_response_count = 0;
$validation_request_response_count = 0;

if ($validation_result && $validation_result->num_rows > 0) {
    $validation_data = $validation_result->fetch_assoc();
    $validation_count = $validation_data['validation_count'];
}

if ($validation_response_result && $validation_response_result->num_rows > 0) {
    $validation_response_data = $validation_response_result->fetch_assoc();
    $validation_response_count = $validation_response_data['validation_response_count'];
}

if ($validation_request_response_result && $validation_request_response_result->num_rows > 0) {
    $validation_request_response_data = $validation_request_response_result->fetch_assoc();
    $validation_request_response_count = $validation_request_response_data['validation_request_response_count'];
}


// Se houver um timestamp 'since', adicionar condição para filtrar apenas notificações mais recentes
if ($since > 0) {
    $followup_query .= " AND UNIX_TIMESTAMP(tf.date) > $since";
    $refused_query .= " AND UNIX_TIMESTAMP(its.date_approval) > $since"; // Corrigido para usar its.date_approval
    $group_query .= " AND UNIX_TIMESTAMP(t.date_creation) > $since";
    $observer_query .= " AND UNIX_TIMESTAMP(t.date_creation) > $since";
    $group_observer_query .= " AND UNIX_TIMESTAMP(t.date_creation) > $since";
    $assigned_tech_query .= " AND UNIX_TIMESTAMP(t.date_mod) > $since";
}

// Executar as consultas
$followup_result = $DB->doQuery($followup_query);
$refused_result = $DB->doQuery($refused_query);
$group_result = $DB->doQuery($group_query);
$observer_result = $DB->doQuery($observer_query);
$group_observer_result = $DB->doQuery($group_observer_query);
$assigned_tech_result = $DB->doQuery($assigned_tech_query);

// Inicializar contadores
$followup_count = 0;
$refused_count = 0;
$group_count = 0;
$observer_count = 0;
$group_observer_count = 0;
$assigned_tech_count = 0;
$validation_count = 0;
$validation_response_count = 0;
$validation_request_response_count = 0;
$status_change_count = 0;
$pending_reason_count = 0;

// Obter os resultados das contagens
if ($followup_result && $followup_result->num_rows > 0) {
    $followup_data = $followup_result->fetch_assoc();
    $followup_count = $followup_data['followup_count'];
}

if ($refused_result && $refused_result->num_rows > 0) {
    $refused_data = $refused_result->fetch_assoc();
    $refused_count = $refused_data['refused_count'];
}

if ($group_result && $group_result->num_rows > 0) {
    $group_data = $group_result->fetch_assoc();
    $group_count = $group_data['group_count'];
}

if ($observer_result && $observer_result->num_rows > 0) {
    $observer_data = $observer_result->fetch_assoc();
    $observer_count = $observer_data['observer_count'];
}

if ($group_observer_result && $group_observer_result->num_rows > 0) {
    $group_observer_data = $group_observer_result->fetch_assoc();
    $group_observer_count = $group_observer_data['group_observer_count'];
}

if ($assigned_tech_result && $assigned_tech_result->num_rows > 0) {
    $assigned_tech_data = $assigned_tech_result->fetch_assoc();
    $assigned_tech_count = $assigned_tech_data['assigned_tech_count'];
}

// SOLUÇÃO DE FORÇA BRUTA PARA O CONTADOR DE VALIDAÇÃO
// Esta solução força o contador de validação com base no diagnóstico que sabemos que funciona
$force_validation_sql = "SELECT COUNT(DISTINCT tv.id) as direct_count
                        FROM glpi_ticketvalidations tv 
                        JOIN glpi_tickets t ON tv.tickets_id = t.id
                        LEFT JOIN glpi_plugin_ticketanswers_views v ON (
                            v.users_id = $users_id AND 
                            v.followup_id = tv.id
                        )
                        WHERE 
                            tv.users_id_validate = $users_id
                            AND tv.status = 2
                            AND t.status != 6
                            AND v.id IS NULL";

$force_result = $DB->doQuery($force_validation_sql);
if ($force_result && $force_result->num_rows > 0) {
    $force_data = $force_result->fetch_assoc();
    $direct_count = intval($force_data['direct_count']);
    
    // Sobrescrever diretamente o contador de validação com o valor correto
    $validation_count = $direct_count;
    
    error_log("FORÇA BRUTA: Substituindo validation_count para $direct_count");
}

// SOLUÇÃO DE FORÇA BRUTA PARA O CONTADOR DE OBSERVADOR
// Consulta direta para contar tickets onde o usuário é observador
$force_observer_sql = "SELECT COUNT(DISTINCT t.id) as direct_count
                      FROM glpi_tickets t
                      INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id 
                            AND tu.type = 3 
                            AND tu.users_id = $users_id
                      LEFT JOIN glpi_plugin_ticketanswers_views v ON (
                          v.users_id = $users_id AND
                          v.followup_id = t.id + 20000000
                      )
                      WHERE
                          v.id IS NULL
                          AND t.status IN (1, 2, 3, 4)";  // Incluindo status 3 e 4 também

$force_observer_result = $DB->doQuery($force_observer_sql);
if ($force_observer_result && $force_observer_result->num_rows > 0) {
    $force_observer_data = $force_observer_result->fetch_assoc();
    $direct_observer_count = intval($force_observer_data['direct_count']);
    
    // Sobrescrever diretamente o contador de observador com o valor correto
    $observer_count = $direct_observer_count;
    
    error_log("FORÇA BRUTA: Substituindo observer_count para $direct_observer_count");
}

// SOLUÇÃO DE FORÇA BRUTA PARA O CONTADOR DE MUDANÇAS DE STATUS
$force_status_change_sql = "SELECT COUNT(DISTINCT t.id) as direct_count
                           FROM glpi_tickets t
                           INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id 
                                 AND tu.type = 1 
                                 AND tu.users_id = $users_id
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
                               AND t.date_mod > DATE_SUB(NOW(), INTERVAL 7 DAY)";

$force_status_result = $DB->doQuery($force_status_change_sql);
if ($force_status_result && $force_status_result->num_rows > 0) {
    $force_status_data = $force_status_result->fetch_assoc();
    $direct_status_count = intval($force_status_data['direct_count']);
    
    // Sobrescrever diretamente o contador de mudanças de status
    $status_change_count = $direct_status_count;
    
    error_log("FORÇA BRUTA: Substituindo status_change_count para $direct_status_count");
}



// Calcular o total de notificações
// MODIFICAÇÃO PRINCIPAL: Consulta unificada para contar o total real de notificações
// Esta consulta deve incluir TODOS os tipos de notificações
// Consulta unificada corrigida
// Substitua a consulta unificada atual por esta nova versão
$unified_count_query = "SELECT COUNT(*) as total FROM (
    -- Esta subconsulta seleciona apenas a notificação mais recente para cada combinação de ticket e tipo
    SELECT 
        ticket_id,
        type
    FROM (
        -- Notificações de respostas em chamados atribuídos
        SELECT
            t.id AS ticket_id,
            tf.id AS followup_id,
            tf.date AS notification_date,
            'followup' AS type
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
            -- Condição para evitar que followups que são motivos de recusa apareçam como notificações de resposta
            AND NOT EXISTS (
                SELECT 1
                FROM glpi_itilsolutions its
                WHERE its.items_id = t.id
                AND its.itemtype = 'Ticket'
                AND its.status = 4
                AND its.users_id_approval = tf.users_id
                AND ABS(UNIX_TIMESTAMP(its.date_approval) - UNIX_TIMESTAMP(tf.date)) <= 3
            )
        
        UNION
        
        -- Notificações de chamados recusados
        SELECT
            t.id AS ticket_id,
            its.id AS followup_id,
            its.date_approval AS notification_date,
            'refused' AS type
        FROM
            glpi_tickets t
            INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.users_id = $users_id
            INNER JOIN (
                SELECT items_id, MAX(id) as latest_solution_id
                FROM glpi_itilsolutions
                WHERE status = 4 AND itemtype = 'Ticket'
                GROUP BY items_id
            ) latest ON t.id = latest.items_id
            INNER JOIN glpi_itilsolutions its ON its.id = latest.latest_solution_id
            LEFT JOIN glpi_users u ON its.users_id_approval = u.id
            LEFT JOIN glpi_plugin_ticketanswers_views v ON (
                v.users_id = $users_id AND
                v.followup_id = its.id
            )
        WHERE
            its.users_id_approval <> $users_id
            AND v.id IS NULL
            AND t.status != 6
        
        UNION
        
        -- Notificações de chamados atribuídos a grupos
        SELECT
            t.id AS ticket_id,
            t.id + 10000000 AS followup_id,
            t.date_creation AS notification_date,
            'group' AS type
        FROM
            glpi_tickets t
            INNER JOIN glpi_groups_tickets gt ON t.id = gt.tickets_id AND gt.type = 2
            INNER JOIN glpi_groups_users gu ON gt.groups_id = gu.groups_id AND gu.users_id = $users_id
            LEFT JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.users_id = $users_id AND tu.type = 2
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
        
        UNION
        
        -- Notificações de chamados onde o usuário é observador
        SELECT
            t.id AS ticket_id,
            t.id + 20000000 AS followup_id,
            t.date_mod AS notification_date,
            'observer' AS type
        FROM
            glpi_tickets t
            INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 3 AND tu.users_id = $users_id
            LEFT JOIN glpi_plugin_ticketanswers_views v ON (
                v.users_id = $users_id AND
                v.ticket_id = t.id AND
                v.followup_id = t.id + 20000000
            )
        WHERE
            v.id IS NULL
            AND t.status IN (1, 2, 3, 4)  -- Novo, Em atendimento, Pendente, Solucionado
        
        UNION
        
        -- Notificações de chamados onde o grupo do usuário é observador
        SELECT
            t.id AS ticket_id,
            t.id + 20000000 AS followup_id,
            tf.date AS notification_date,
            'group_observer' AS type
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
        
        UNION
        
        -- Notificações de chamados recém atribuídos ao técnico
        SELECT
            t.id AS ticket_id,
            t.id + 30000000 AS followup_id,
            t.date_mod AS notification_date,
            'assigned_tech' AS type
        FROM
            glpi_tickets t
            INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 2 AND tu.users_id = $users_id
            LEFT JOIN glpi_plugin_ticketanswers_views v ON (
                v.users_id = $users_id AND
                v.ticket_id = t.id AND
                v.followup_id = t.id + 30000000
            )
        WHERE
            v.id IS NULL
            AND t.status IN (1, 2)
            AND t.date_mod > DATE_SUB(NOW(), INTERVAL 7 DAY)
        
        UNION
        
        -- Notificações de respostas de técnicos em chamados do usuário
        SELECT
            t.id AS ticket_id,
            tf.id AS followup_id,
            tf.date AS notification_date,
            'technician_response' AS type
        FROM
            glpi_tickets t
            INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 1 AND tu.users_id = $users_id
            INNER JOIN glpi_itilfollowups tf ON t.id = tf.items_id AND tf.itemtype = 'Ticket'
            LEFT JOIN glpi_plugin_ticketanswers_views v ON (
                v.users_id = $users_id AND
                v.followup_id = tf.id
            )
        WHERE
            v.id IS NULL
            AND t.status != 6
            AND tf.is_private = 0
            AND EXISTS (
                SELECT 1 FROM glpi_tickets_users tech_user
                WHERE tech_user.tickets_id = t.id
                AND tech_user.users_id = tf.users_id
                AND tech_user.type = 2
            )
        
        UNION
        
        -- Notificações de mudanças de status em chamados do usuário
        SELECT
            t.id AS ticket_id,
            CONCAT('status_', t.id, '_', t.status) AS followup_id,
            t.date_mod AS notification_date,
            'status_change' AS type
        FROM
            glpi_tickets t
            INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 1 AND tu.users_id = $users_id
            LEFT JOIN glpi_plugin_ticketanswers_views v ON (
                v.users_id = $users_id AND
                v.ticket_id = t.id AND
                v.followup_id = CONCAT('status_', t.id, '_', t.status)
            )
        WHERE
            v.id IS NULL
            AND t.status IN (2, 3, 4)
            AND t.date_mod > DATE_SUB(NOW(), INTERVAL 7 DAY)
        
        UNION
        
        -- Notificações de motivos de pendência em chamados do usuário
        SELECT
            t.id AS ticket_id,
            CONCAT('pending_', t.id, '_', t.pendingreasons_id) AS followup_id,
            t.date_mod AS notification_date,
            'pending_reason' AS type
        FROM
            glpi_tickets t
            INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 1 AND tu.users_id = $users_id
            LEFT JOIN glpi_pendingreasons pt ON t.pendingreasons_id = pt.id
            LEFT JOIN glpi_plugin_ticketanswers_views v ON (
                v.users_id = $users_id AND
                v.ticket_id = t.id AND
                v.followup_id = CONCAT('pending_', t.id, '_', t.pendingreasons_id)
            )
        WHERE
            v.id IS NULL
            AND t.status = 3
            AND t.pendingreasons_id > 0
            AND t.date_mod > DATE_SUB(NOW(), INTERVAL 7 DAY)
        
        UNION
        
        -- Notificações de validações pendentes (para validadores)
        SELECT
            t.id AS ticket_id,
            tv.id AS followup_id,
            tv.submission_date AS notification_date,
            'validation' AS type
        FROM
            glpi_tickets t
            INNER JOIN glpi_ticketvalidations tv ON t.id = tv.tickets_id
            LEFT JOIN glpi_plugin_ticketanswers_views v ON (
                v.users_id = $users_id AND
                v.ticket_id = t.id AND
                v.followup_id = tv.id
            )
        WHERE
            t.status != 6 -- Excluir chamados fechados
            AND tv.users_id_validate = $users_id
            AND tv.status = 2 -- Aguardando validação
            AND v.id IS NULL
        
        UNION
        
        -- Notificações de respostas para quem solicitou validação
        SELECT
            t.id AS ticket_id,
            CONCAT('validation_response_', tv.id) AS followup_id,
            tv.validation_date AS notification_date,
            'validation_response' AS type
        FROM
            glpi_tickets t
            INNER JOIN glpi_ticketvalidations tv ON t.id = tv.tickets_id AND tv.users_id = $users_id
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
    ) AS all_notifications
    GROUP BY ticket_id, type
) AS unique_notifications";

$unified_count_result = $DB->doQuery($unified_count_query);
$total_count_unified = 0;

if ($unified_count_result && $unified_count_result->num_rows > 0) {
    $unified_count_data = $unified_count_result->fetch_assoc();
    $total_count_unified = $unified_count_data['total'];
}

// Calcular o total também somando os contadores individuais para verificação
$total_count_sum = $followup_count + $refused_count + $group_count + $observer_count +
                  $group_observer_count + $assigned_tech_count + $validation_count +
                  $validation_response_count + $validation_request_response_count +
                  $technician_response_count + $status_change_count + $pending_reason_count;

// Usar o maior valor como total principal
$final_count = max($total_count_unified, $total_count_sum);

// Adicionar log para depuração
error_log("DEBUG: Contagens após agrupamento - unificada=$total_count_unified, soma=$total_count_sum, final=$final_count");

// DIAGNÓSTICO FINAL
error_log("=== CONTADORES FINAIS ===");
error_log("validation_count: $validation_count");
error_log("total_count_sum: $total_count_sum");
error_log("final_count: $final_count");
error_log("unified_count: $total_count_unified");

// Preparar a resposta com o valor correto de contagem
$response = [
    'count' => $final_count,
    'followup_count' => $followup_count,
    'refused_count' => $refused_count,
    'group_count' => $group_count,
    'observer_count' => $observer_count,
    'group_observer_count' => $group_observer_count,
    'assigned_tech_count' => $assigned_tech_count,
    'validation_count' => $validation_count,
    'validation_response_count' => $validation_response_count,
    'validation_request_response_count' => $validation_request_response_count,
    'technician_response_count' => $technician_response_count,
    'status_change_count' => $status_change_count,
    'pending_reason_count' => $pending_reason_count,
    'combined_count' => $final_count,  // Usar o mesmo valor final aqui
    'timestamp' => time()
];




// Se solicitado, incluir detalhes das notificações
if (isset($_GET['get_details']) && $_GET['get_details']) {
    // Consulta para obter detalhes das notificações de validação
$validation_details_query = "SELECT DISTINCT
t.id AS ticket_id,
t.name AS ticket_name,
tv.id AS followup_id,
tv.submission_date AS notification_date,
CONCAT('Solicitação de validação: ', IF(tv.comment_submission IS NOT NULL AND tv.comment_submission != '',
       tv.comment_submission, 'Sem comentários adicionais.')) AS comment_content,
u.name AS requester_name
FROM
glpi_tickets t
INNER JOIN glpi_ticketvalidations tv ON t.id = tv.tickets_id AND tv.users_id_validate = $users_id
LEFT JOIN glpi_users u ON tv.users_id = u.id
LEFT JOIN glpi_plugin_ticketanswers_views v ON (
    v.users_id = $users_id AND
    v.ticket_id = t.id AND
    v.followup_id = tv.id
)
WHERE
t.status != 6
AND tv.status = 2
AND v.id IS NULL
ORDER BY tv.submission_date DESC
LIMIT 10";

$validation_details_result = $DB->doQuery($validation_details_query);
$validation_notifications = [];

while ($data = $validation_details_result->fetch_assoc()) {
$validation_notifications[] = [
    'ticket_id' => $data['ticket_id'],
    'ticket_name' => $data['ticket_name'],
    'followup_id' => $data['followup_id'],
    'notification_date' => Html::convDateTime($data['notification_date']),
    'requester_name' => $data['requester_name'],
    'comment_content' => $data['comment_content'],
    'type' => 'validation'
];
}

// Consulta para obter detalhes das respostas de validação
$validation_response_details_query = "SELECT DISTINCT
t.id AS ticket_id,
t.name AS ticket_name,
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
    ELSE 'Status de validação desconhecido'
END AS response_content,
v.name AS validator_name
FROM
glpi_tickets t
INNER JOIN glpi_ticketvalidations tv ON t.id = tv.tickets_id AND tv.users_id = $users_id
LEFT JOIN glpi_users v ON tv.users_id_validate = v.id
LEFT JOIN glpi_plugin_ticketanswers_views views ON (
    views.users_id = $users_id AND
    views.ticket_id = t.id AND
    views.followup_id = tv.id
)
WHERE
t.status != 6
AND (tv.status = 3 OR tv.status = 4)
AND views.id IS NULL
AND tv.validation_date > DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY tv.validation_date DESC
LIMIT 10";

$validation_response_details_result = $DB->doQuery($validation_response_details_query);
$validation_response_notifications = [];

while ($data = $validation_response_details_result->fetch_assoc()) {
$validation_response_notifications[] = [
    'ticket_id' => $data['ticket_id'],
    'ticket_name' => $data['ticket_name'],
    'followup_id' => $data['followup_id'],
    'notification_date' => Html::convDateTime($data['notification_date']),
    'validator_name' => $data['validator_name'],
    'response_content' => $data['response_content'],
    'type' => 'validation_response'
];
}


// Consulta para obter detalhes das respostas de validação
$validation_response_details_query = "SELECT DISTINCT
t.id AS ticket_id,
t.name AS ticket_name,
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
    ELSE 'Status de validação desconhecido'
END AS response_content,
v.name AS validator_name
FROM
glpi_tickets t
INNER JOIN glpi_ticketvalidations tv ON t.id = tv.tickets_id AND tv.users_id = $users_id
LEFT JOIN glpi_users v ON tv.users_id_validate = v.id
LEFT JOIN glpi_plugin_ticketanswers_views views ON (
    views.users_id = $users_id AND
    views.ticket_id = t.id AND
    views.followup_id = CONCAT('validation_response_', tv.id)
)
WHERE
t.status != 6
AND (tv.status = 3 OR tv.status = 4)
AND views.id IS NULL
AND tv.validation_date > DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY tv.validation_date DESC
LIMIT 10";

$validation_response_details_result = $DB->doQuery($validation_response_details_query);
$validation_response_notifications = [];

while ($data = $validation_response_details_result->fetch_assoc()) {
$validation_response_notifications[] = [
    'ticket_id' => $data['ticket_id'],
    'ticket_name' => $data['ticket_name'],
    'followup_id' => $data['followup_id'],
    'notification_date' => Html::convDateTime($data['notification_date']),
    'validator_name' => $data['validator_name'],
    'response_content' => $data['response_content'],
    'type' => 'validation_response'
];
}


    // Consulta para obter detalhes das notificações de grupo
    $group_details_query = "SELECT DISTINCT
        t.id AS ticket_id,
        t.name AS ticket_name,
        t.content AS ticket_content,
        t.date_creation AS creation_date,
        u.name AS requester_name,
        g.name AS group_name
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
    ORDER BY t.date_creation DESC
    LIMIT 10";
    
    $group_details_result = $DB->doQuery($group_details_query);
    $group_notifications = [];
    
    while ($data = $group_details_result->fetch_assoc()) {
        // Decodificar entidades HTML para o conteúdo do ticket
        $ticket_content = $data['ticket_content'];
        $decoded_content = html_entity_decode($ticket_content);
        // Extrair texto entre tags usando regex
        $plain_text = preg_replace('/<.*?>/', '', $decoded_content);
        $short_text = Html::resume_text($plain_text, 100);
        
        $group_notifications[] = [
            'ticket_id' => $data['ticket_id'],
            'ticket_name' => $data['ticket_name'],
            'creation_date' => Html::convDateTime($data['creation_date']),
            'requester_name' => $data['requester_name'],
            'group_name' => $data['group_name'],
            'ticket_content' => $short_text,
            'type' => 'group'
        ];
    }
    
    $response['followup_notifications'] = $followup_notifications;
    $response['group_notifications'] = $group_notifications;
    
    // Consulta para obter detalhes das notificações de observador
    $observer_details_query = "SELECT DISTINCT
        t.id AS ticket_id,
        t.name AS ticket_name,
        t.content AS ticket_content,
        t.date_creation AS creation_date,
        u.name AS requester_name
    FROM
        glpi_tickets t
        INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id AND tu.type = 3 AND tu.users_id = $users_id
        LEFT JOIN glpi_users u ON t.users_id_recipient = u.id
        LEFT JOIN glpi_plugin_ticketanswers_views v ON (
            v.users_id = $users_id AND
            v.ticket_id = t.id AND
            v.followup_id = t.id + 20000000
        )
    WHERE
        v.id IS NULL
        AND t.status IN (1, 2)
        AND t.date_creation > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY t.date_creation DESC
    LIMIT 10";
    
    $observer_details_result = $DB->doQuery($observer_details_query);
    $observer_notifications = [];
    
    while ($data = $observer_details_result->fetch_assoc()) {
        // Decodificar entidades HTML para o conteúdo do ticket
        $ticket_content = $data['ticket_content'];
        $decoded_content = html_entity_decode($ticket_content);
        // Extrair texto entre tags usando regex
        $plain_text = preg_replace('/<.*?>/', '', $decoded_content);
        $short_text = Html::resume_text($plain_text, 100);
        
        $observer_notifications[] = [
            'ticket_id' => $data['ticket_id'],
            'ticket_name' => $data['ticket_name'],
            'creation_date' => Html::convDateTime($data['creation_date']),
            'requester_name' => $data['requester_name'],
            'ticket_content' => $short_text,
            'type' => 'observer'
        ];
    }
    
    // Consulta para obter detalhes das notificações de recusa
    $refused_details_query = "SELECT DISTINCT
        t.id AS ticket_id,
        t.name AS ticket_name,
        its.id AS followup_id,
        its.date_approval AS followup_date,
        its.content AS followup_content,
        u.name AS user_name
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
        LEFT JOIN glpi_plugin_ticketanswers_views v ON (
            v.users_id = $users_id AND
            v.followup_id = its.id
        )
    WHERE
        v.id IS NULL
        AND t.status != 6
    ORDER BY its.date_approval DESC
    LIMIT 10";
    
    $refused_details_result = $DB->doQuery($refused_details_query);
    $refused_notifications = [];
    
    while ($data = $refused_details_result->fetch_assoc()) {
        // Decodificar entidades HTML
        $followup_content = $data['followup_content'];
        $decoded_content = html_entity_decode($followup_content);
        // Extrair texto entre tags usando regex
        $plain_text = preg_replace('/<.*?>/', '', $decoded_content);
        $short_text = Html::resume_text($plain_text, 100);
        
        $refused_notifications[] = [
            'ticket_id' => $data['ticket_id'],
            'ticket_name' => $data['ticket_name'],
            'followup_id' => $data['followup_id'],
            'followup_date' => Html::convDateTime($data['followup_date']),
            'user_name' => $data['user_name'],
            'followup_content' => $short_text,
            'type' => 'refused'
        ];
    }
    


// Obter os resultados das contagens após todas as consultas
if ($followup_result && $followup_result->num_rows > 0) {
    $followup_data = $followup_result->fetch_assoc();
    $followup_count = $followup_data['followup_count'];
}

    // Consulta para obter detalhes das notificações de técnico atribuído
    $assigned_tech_details_query = "SELECT DISTINCT
        t.id AS ticket_id,
        t.name AS ticket_name,
        t.content AS ticket_content,
        t.date_mod AS assigned_date,
        u.name AS requester_name
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
        AND t.date_mod > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY t.date_mod DESC
    LIMIT 10";
    
    $assigned_tech_details_result = $DB->doQuery($assigned_tech_details_query);
    $assigned_tech_notifications = [];
    
    while ($data = $assigned_tech_details_result->fetch_assoc()) {
        // Decodificar entidades HTML para o conteúdo do ticket
        $ticket_content = $data['ticket_content'];
        $decoded_content = html_entity_decode($ticket_content);
        // Extrair texto entre tags usando regex
        $plain_text = preg_replace('/<.*?>/', '', $decoded_content);
        $short_text = Html::resume_text($plain_text, 100);
        
        $assigned_tech_notifications[] = [
            'ticket_id' => $data['ticket_id'],
            'ticket_name' => $data['ticket_name'],
            'assigned_date' => Html::convDateTime($data['assigned_date']),
            'requester_name' => $data['requester_name'],
            'ticket_content' => $short_text,
            'type' => 'assigned'
        ];
    }
    
    // Adicionar todos os detalhes à resposta
$response['observer_notifications'] = $observer_notifications;
$response['refused_notifications'] = $refused_notifications;
$response['assigned_tech_notifications'] = $assigned_tech_notifications;
$response['validation_notifications'] = $validation_notifications;
$response['validation_response_notifications'] = $validation_response_notifications;
    
    // Combinar todas as notificações em uma única lista ordenada por data
    $all_notifications = [];
    
    foreach ($followup_notifications as $notification) {
        $notification['date'] = strtotime($notification['followup_date']);
        $all_notifications[] = $notification;
    }
    
    foreach ($group_notifications as $notification) {
        $notification['date'] = strtotime($notification['creation_date']);
        $all_notifications[] = $notification;
    }
    
    foreach ($observer_notifications as $notification) {
        $notification['date'] = strtotime($notification['creation_date']);
        $all_notifications[] = $notification;
    }
    
    foreach ($refused_notifications as $notification) {
        $notification['date'] = strtotime($notification['followup_date']);
        $all_notifications[] = $notification;
    }
    
    foreach ($assigned_tech_notifications as $notification) {
        $notification['date'] = strtotime($notification['assigned_date']);
        $all_notifications[] = $notification;
    }
    
    // Adicionar notificações de validação
foreach ($validation_notifications as $notification) {
    $notification['date'] = strtotime($notification['notification_date']);
    $all_notifications[] = $notification;
}

// Adicionar notificações de resposta de validação
foreach ($validation_response_notifications as $notification) {
    $notification['date'] = strtotime($notification['notification_date']);
    $all_notifications[] = $notification;
}

    // Ordenar por data (mais recente primeiro)
    usort($all_notifications, function($a, $b) {
        return $b['date'] - $a['date'];
    });
    
    // Limitar a 10 notificações mais recentes
    $all_notifications = array_slice($all_notifications, 0, 10);
    
    $response['all_notifications'] = $all_notifications;
}

// CÓDIGO DE DIAGNÓSTICO TEMPORÁRIO
// Consulta direta para verificar se existem validações para o usuário
$diagnostic_sql = "SELECT tv.id, t.name, tv.status, tv.submission_date, tv.users_id, tv.users_id_validate, 
                        IF(tv.users_id_validate = $users_id, 'Sou o validador', 'Não sou o validador') as role
                   FROM glpi_ticketvalidations tv 
                   JOIN glpi_tickets t ON tv.tickets_id = t.id
                   WHERE (tv.users_id_validate = $users_id OR tv.users_id = $users_id)
                   AND tv.status = 2
                   AND t.status != 6
                   ORDER BY tv.submission_date DESC
                   LIMIT 5";

$result = $DB->doQuery($diagnostic_sql);
error_log("=== DIAGNÓSTICO DE VALIDAÇÕES ===");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        error_log(json_encode($row));
        
        // Verificar se há um registro de visualização para esta validação
        $view_sql = "SELECT id FROM glpi_plugin_ticketanswers_views 
                     WHERE users_id = $users_id AND followup_id = {$row['id']}";
        $view_result = $DB->doQuery($view_sql);
        $viewed = ($view_result && $view_result->num_rows > 0) ? "SIM" : "NÃO";
        error_log("Validação ID {$row['id']} já foi visualizada? $viewed");
    }
} else {
    error_log("Nenhuma validação pendente encontrada para o usuário $users_id");
}

// Verificar como o contador final está sendo calculado
error_log("=== VERIFICAÇÃO DE CONTADORES ===");

// Retornar a resposta como JSON
header('Content-Type: application/json');
echo json_encode($response);
?>

