<?php
include ("../../../inc/includes.php");

Session::checkLoginUser();

// Verificar se os parâmetros necessários foram fornecidos
$ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
$users_id = Session::getLoginUserID();
$success = false;
$message = "";

error_log("Tentando assumir chamado: ticket=$ticket_id, user=$users_id");

if ($ticket_id > 0) {
    global $DB;
    
    // Verificar se o ticket existe
    $ticket = new Ticket();
    if ($ticket->getFromDB($ticket_id)) {
        error_log("Ticket encontrado: " . print_r($ticket->fields, true));
        
        // Verificar se o usuário pertence a um dos grupos atribuídos ao ticket
        $query = "SELECT COUNT(*) as count
                  FROM glpi_groups_tickets gt
                  JOIN glpi_groups_users gu ON gt.groups_id = gu.groups_id
                  WHERE gt.tickets_id = $ticket_id
                  AND gt.type = 2
                  AND gu.users_id = $users_id";
        
        error_log("Executando query: $query");
        $result = $DB->query($query);
        $data = $DB->fetchAssoc($result);
        error_log("Resultado da query: " . print_r($data, true));
        
        if ($data['count'] > 0) {
            // Verificar se o usuário já está atribuído ao ticket
            $ticket_user = new Ticket_User();
            $existing = $ticket_user->find([
                'tickets_id' => $ticket_id,
                'users_id' => $users_id,
                'type' => CommonITILActor::ASSIGN
            ]);
            
            if (empty($existing)) {
                // Adicionar o usuário como técnico atribuído ao ticket
                $user_data = [
                    'tickets_id' => $ticket_id,
                    'users_id' => $users_id,
                    'type' => CommonITILActor::ASSIGN, // 2 = Técnico
                    'use_notification' => 1
                ];
                
                error_log("Tentando adicionar usuário ao ticket: " . print_r($user_data, true));
                
                if ($ticket_user->add($user_data)) {
                    error_log("Usuário adicionado com sucesso ao ticket");
                    $success = true;
                    
                    // Atualizar o status do ticket para "Em atendimento" (2) se estiver como "Novo" (1)
                    if ($ticket->fields['status'] == Ticket::INCOMING) {
                        error_log("Atualizando status do ticket para Em atendimento");
                        $ticket->update([
                            'id' => $ticket_id,
                            'status' => Ticket::ASSIGNED
                        ]);
                    }
                    
                    $message = "Chamado assumido com sucesso";
                    error_log($message);
                } else {
                    $message = "Erro ao adicionar usuário ao ticket";
                    error_log($message);
                }
            } else {
                $success = true; // Considerar como sucesso se o usuário já estiver atribuído
                $message = "Você já está atribuído a este chamado";
                error_log($message);
            }
        } else {
            $message = "Você não pertence a nenhum grupo atribuído a este chamado";
            error_log($message);
        }
    } else {
        $message = "Chamado não encontrado";
        error_log($message);
    }
}

// Retornar resposta JSON
header('Content-Type: application/json');
echo json_encode(['success' => $success, 'message' => $message]);
