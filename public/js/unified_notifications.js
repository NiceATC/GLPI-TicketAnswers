/**
 * Notification Bell JavaScript
 * Handles the notification bell icon in the GLPI interface
 */

// Função para adicionar o sino de notificações à interface
function addNotificationBell() {
    console.log('Tentando adicionar o sino de notificações...');
    
    // Verificar se o sino já existe
    if ($('.notification-bell').length > 0) {
        console.log('Sino já existe, não adicionando novamente');
        return;
    }
    
    // Tentar várias abordagens em sequência
    let success = false;
    
    // 1. Método padrão: campo de pesquisa global
    if (!success) {
        const global_search = $('input[name="globalsearch"], input.form-control-search, .search-input');
        if (global_search.length > 0) {
            console.log('Campo de pesquisa global encontrado:', global_search.length);
            let container = global_search.closest('.input-group');
            if (container.length === 0) {
                container = global_search.parent();
            }
            if (container.length > 0) {
                injectNotificationButton(global_search, container);
                success = true;
                console.log('Sino adicionado ao campo de pesquisa global');
            }
        }
    }
    
    // 2. FormCreator: tentar encontrar elementos específicos do FormCreator
    if (!success) {
        const formcreatorHeader = $('.plugin_formcreator_userForm_header, .plugin_formcreator_header');
        if (formcreatorHeader.length > 0) {
            console.log('Cabeçalho do FormCreator encontrado');
            
            // Criar um contêiner para o sino
            const bellContainer = $('<div class="notification-container" style="margin-left: auto; margin-right: 15px; display: flex; align-items: center;"></div>');
            bellContainer.append(getNotificationButton());
            
            // Adicionar ao cabeçalho do FormCreator
            formcreatorHeader.append(bellContainer);
            
            // Configurar eventos de clique
            setupBellEvents(bellContainer);
            success = true;
            console.log('Sino adicionado ao cabeçalho do FormCreator');
        }
    }
    
    // 3. Menu de usuário no canto superior direito
    if (!success) {
        const userMenu = $('.navbar .navbar-nav:last-child, .navbar .ms-auto, header .navbar-nav:last-child, .user-menu');
        if (userMenu.length > 0) {
            console.log('Menu de usuário encontrado');
            
            // Criar um novo item de menu para o sino
            const bellItem = $('<li class="nav-item" style="display: flex; align-items: center; margin-right: 10px;"></li>');
            bellItem.append(getNotificationButton());
            
            // Adicionar antes do menu de usuário
            userMenu.prepend(bellItem);
            
            // Configurar eventos de clique
            setupBellEvents(bellItem);
            success = true;
            console.log('Sino adicionado ao menu de usuário');
        }
    }
    
    // 4. Cabeçalho principal
    if (!success) {
        const header = $('header, .navbar, .main-header, #header_top, .top-bar');
        if (header.length > 0) {
            console.log('Cabeçalho principal encontrado');
            
            // Criar um contêiner para o sino
            const bellContainer = $('<div class="notification-container" style="margin-left: auto; margin-right: 15px; display: flex; align-items: center;"></div>');
            bellContainer.append(getNotificationButton());
            
            // Adicionar ao cabeçalho
            header.first().append(bellContainer);
            
            // Configurar eventos de clique
            setupBellEvents(bellContainer);
            success = true;
            console.log('Sino adicionado ao cabeçalho principal');
        }
    }
    
    // 5. Interface simplificada (self-service)
    if (!success) {
        const selfServiceHeader = $('.navbar.self-service, .self-service .navbar, .self-service-header');
        if (selfServiceHeader.length > 0) {
            console.log('Cabeçalho da interface simplificada encontrado');
            
            // Criar um contêiner para o sino
            const bellContainer = $('<div class="notification-container" style="margin-left: auto; margin-right: 15px; display: flex; align-items: center;"></div>');
            bellContainer.append(getNotificationButton());
            
            // Adicionar ao cabeçalho da interface simplificada
            selfServiceHeader.append(bellContainer);
            
            // Configurar eventos de clique
            setupBellEvents(bellContainer);
            success = true;
            console.log('Sino adicionado ao cabeçalho da interface simplificada');
        }
    }
    
    // 6. Último recurso: adicionar como elemento flutuante
    if (!success) {
        console.log('Nenhum local adequado encontrado, adicionando sino flutuante');
        
        // Criar um contêiner flutuante para o sino
        const floatingBell = $(`
            <div class="floating-notification-container" style="
                position: fixed;
                top: 10px;
                right: 10px;
                z-index: 9999;
                display: flex;
                background-color: #f8f9fa;
                padding: 5px;
                border-radius: 5px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            "></div>
        `);
        
        floatingBell.append(getNotificationButton());
        
        // Adicionar ao corpo da página
        $('body').append(floatingBell);
        
        // Configurar eventos de clique
        setupBellEvents(floatingBell);
        success = true;
        console.log('Sino adicionado como elemento flutuante');
    }
    
    if (!success) {
        console.error('Não foi possível adicionar o sino de notificações em nenhum local');
    }
}

// Função para obter o botão de notificação (agora com funcionalidade de som integrada)
function getNotificationButton() {
    const soundEnabled = getSoundEnabledState();
    const soundClass = soundEnabled ? 'sound-enabled' : 'sound-disabled';
    const soundIcon = 'fa-bell'; // Mantemos o ícone de sino, apenas mudamos o indicador visual
    const soundTitle = soundEnabled ? 'Notificações (som ativado)' : 'Notificações (som desativado)';
    
    return $(`
        <button type="button" class="notification-bell btn btn-outline-secondary ${soundClass}" title="${soundTitle}">
            <i class="fas ${soundIcon} fa-lg"></i>
        </button>`);
}

// Função para injetar o botão de notificação (sem o botão de som separado)
function injectNotificationButton(input_element, container = undefined) {
    if (input_element !== undefined && input_element.length > 0) {
        if (container !== undefined) {
            container.append(getNotificationButton());
        } else {
            input_element.after(getNotificationButton());
            container = input_element.parent();
        }
        // Configurar eventos de clique
        setupBellEvents(container);
    }
}

// Método auxiliar para configurar eventos de clique
function setupBellEvents(container) {
    // Clique normal no sino abre a página de notificações
    container.find('.notification-bell').on('click', function() {
        window.location.href = CFG_GLPI.root_doc + '/plugins/ticketanswers/front/index.php';
    });
    
    // Clique com o botão direito no sino alterna o som
    container.find('.notification-bell').on('contextmenu', function(e) {
        e.preventDefault(); // Prevenir o menu de contexto padrão
        toggleNotificationSound();
        return false;
    });
}

// Função para alternar o som de notificações
function toggleNotificationSound() {
    // Obter o estado atual
    let soundEnabled = getSoundEnabledState();
    
    // Inverter o estado
    soundEnabled = !soundEnabled;
    
    // Atualizar o ícone e o título do botão
    const button = $('.notification-bell');
    
    if (soundEnabled) {
        button.addClass('sound-enabled').removeClass('sound-disabled');
        button.attr('title', 'Notificações (som ativado)');
        // Tocar um som curto para confirmar que está ativado
        playTestSound();
    } else {
        button.addClass('sound-disabled').removeClass('sound-enabled');
        button.attr('title', 'Notificações (som desativado)');
    }
    
    // Salvar preferência no localStorage
    try {
        localStorage.setItem('ticketAnswersSoundEnabled', soundEnabled ? 'true' : 'false');
    } catch (e) {
        console.error('Não foi possível salvar a preferência de som:', e);
    }
    
    console.log('Som de notificações ' + (soundEnabled ? 'ativado' : 'desativado'));
}

// Função para obter o estado atual do som
function getSoundEnabledState() {
    // Verificar se há uma preferência salva no localStorage
    try {
        const savedSoundPreference = localStorage.getItem('ticketAnswersSoundEnabled');
        if (savedSoundPreference !== null) {
            return savedSoundPreference === 'true';
        }
    } catch (e) {
        console.error('Erro ao carregar preferência de som:', e);
    }
    
    // Se não houver preferência salva, usar a configuração global ou o padrão
    return window.ticketAnswersConfig && typeof window.ticketAnswersConfig.enableSound !== 'undefined'
        ? window.ticketAnswersConfig.enableSound
        : true; // Som habilitado por padrão
}

// Função para tocar um som de teste
function playTestSound() {
    try {
        const audio = new Audio(CFG_GLPI.root_doc + 'sound/notification.mp3');
        audio.volume = 0.2; // Volume mais baixo para o teste
        audio.play().catch(error => {
            console.error('Erro ao reproduzir som de teste:', error);
        });
    } catch (e) {
        console.error('Exceção ao tentar tocar som de teste:', e);
    }
}

// Função para tocar o som de notificação
function playNotificationSound() {
    console.log('Reproduzindo som de notificação...');
    
    // Verificar se o som está habilitado
    if (!getSoundEnabledState()) {
        console.log('Som de notificação desabilitado nas configurações');
        return;
    }
    
    try {
        // Verificar se já tocou som recentemente (nos últimos 5 segundos)
        const now = Date.now();
        const lastPlayed = window.lastSoundPlayed || 0;
        
        if ((now - lastPlayed) < 5000) {
            console.log('Som já tocado recentemente, ignorando');
            return;
        }
        
        window.lastSoundPlayed = now;
        
        // Usar um elemento de áudio existente ou criar um novo
        var audioElement = document.getElementById('notification-sound');
        if (!audioElement) {
            audioElement = document.createElement('audio');
            audioElement.id = 'notification-sound';
            audioElement.src = CFG_GLPI.root_doc + 'sound/notification.mp3';
            document.body.appendChild(audioElement);
        }
        
        // Definir volume
        var volume = (window.ticketAnswersConfig && window.ticketAnswersConfig.soundVolume)
            ? window.ticketAnswersConfig.soundVolume / 100
            : 0.5;
        audioElement.volume = volume;
        
        // Forçar o reinício do áudio
        audioElement.currentTime = 0;
        
        // Tentar reproduzir
        var playPromise = audioElement.play();
        if (playPromise !== undefined) {
            playPromise.then(() => {
                console.log('Som de notificação tocado com sucesso');
            }).catch(error => {
                console.error('Erro ao tocar som de notificação:', error);
            });
        }
    } catch (e) {
        console.error('Exceção ao tentar tocar som:', e);
    }
}

// Adicionar estilos CSS necessários
function addNotificationStyles() {
    console.log('Adicionando estilos CSS para notificações');
    
    const css = `
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        .pulse-animation {
            animation: pulse 1s infinite;
        }
        .notification-bell {
            position: relative;
        }
        .notification-bell .has-notifications {
            color:rgb(255, 0, 0);
        }
        .notification-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color:rgba(252, 80, 0, 0.99);
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: bold;
        }
        .notification-bell.sound-disabled {
            opacity: 0.8;
        }
        .notification-bell.sound-disabled:after {
            content: '';
            position: absolute;
            bottom: 3px;
            right: 3px;
            width: 8px;
            height: 8px;
            background-color: #ccc;
            border-radius: 50%;
        }
        .notification-bell.sound-enabled:after {
            content: '';
            position: absolute;
            bottom: 3px;
            right: 3px;
            width: 8px;
            height: 8px;
            background-color: #4CAF50;
            border-radius: 50%;
        }
        @keyframes urgent-pulse {            
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgb(255, 0, 0); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 10px rgba(255, 87, 34, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 87, 34, 0); }
        }
        .urgent-notification {
            animation: urgent-pulse 1.5s infinite;
        }
    `;
    
    $('<style>').prop('type', 'text/css').html(css).appendTo('head');
}

// Verificar se deve mostrar o sino
function shouldShowBell() {
    // Verificar se estamos na interface simplificada (self-service)
    if (typeof CFG_GLPI !== 'undefined') {
        // Verificar explicitamente o layout
        if (CFG_GLPI.layout === 'SelfService' || 
            $('body').hasClass('self-service') || 
            $('.navbar.self-service').length > 0) {
            console.log('Interface self-service detectada, verificando permissões...');
            
            // Verificar se o usuário tem permissão para ver o sino na interface simplificada
            // Se não houver configuração específica, mostrar por padrão
            return window.ticketAnswersConfig && typeof window.ticketAnswersConfig.showInSelfService !== 'undefined'
                ? window.ticketAnswersConfig.showInSelfService
                : true;
        }
    }
    return true;
}

// Função para atualizar o contador de notificações
function updateNotificationCount(count) {
    console.log('Atualizando indicador de notificações (sem contador numérico)');
    if (count > 0) {
        // Há notificações não lidas - apenas colorir o sino
        $('.notification-bell i').addClass('has-notifications');
        
        // Remover o contador numérico
        $('.notification-count').remove();
    } else {
        // Não há notificações não lidas
        $('.notification-bell i').removeClass('has-notifications');
        $('.notification-count').remove();
    }
}

// Função para verificar notificações
function checkNotifications() {
    console.log('Verificando notificações...');
    
    // Armazenar o valor atual antes da verificação
    const previousCount = window.lastNotificationCount || 0;
    
    $.ajax({
        url: CFG_GLPI.root_doc + '/plugins/ticketanswers/ajax/check_all_notifications.php',
        type: 'GET',
        dataType: 'json',
        success: (data) => {
            console.log('Notificações verificadas:', data);
            
            // Atualizar o contador visual
            const currentCount = data.combined_count || data.count || 0;
            updateNotificationCount(currentCount);
            
            // TOCAR SOM APENAS SE HOUVER NOVAS NOTIFICAÇÕES
// Ou seja, se o número atual for MAIOR que o anterior
if (currentCount > previousCount) {
    console.log('Novas notificações detectadas! Anterior:', previousCount, 'Atual:', currentCount);
    
    // Verificar se o usuário está na página de notificações
    const isOnNotificationsPage = window.location.href.indexOf('/plugins/ticketanswers/front/index.php') > -1;
    
    // Sempre aplicar a animação de balançar, independentemente de onde o usuário está
    $('.notification-bell').addClass('bell-super-animation');

setTimeout(() => {
    $('.notification-bell').removeClass('bell-super-animation');
}, 3000);
    
    // Tocar som apenas se o usuário não estiver na página de notificações
    if (!isOnNotificationsPage) {
        // Tocar som se estiver habilitado
        playNotificationSound();
        
        // Tentar mostrar notificação web
        if (Notification.permission === "granted") {
            showWebNotification("Você tem " + currentCount + " novas notificações");
        }
    } else {
        console.log('Usuário já está na página de notificações, não tocando som');
    }
}
     
            // Armazena o número atual de notificações para a próxima verificação
            window.lastNotificationCount = currentCount;
        },
        error: (xhr, status, error) => {
            console.error('Erro ao verificar notificações:', error);
        }
    });
}

// Função para mostrar notificação web
function showWebNotification(message) {
    if (Notification.permission === "granted") {
        const notification = new Notification("GLPI - Ticket Answers", {
            body: message || "Você tem novas notificações no sistema",
            icon: CFG_GLPI.root_doc + "/pics/favicon.ico"
        });
        
        notification.onclick = () => {
            window.focus();
            window.location.href = CFG_GLPI.root_doc + '/plugins/ticketanswers/front/index.php';
            notification.close();
        };
    }
}

// Método para solicitar permissão de notificações web
function requestNotificationPermission() {
    if (!("Notification" in window)) {
        console.log("Este navegador não suporta notificações desktop");
        return;
    }
    
    if (Notification.permission !== "granted" && Notification.permission !== "denied") {
        console.log("Solicitando permissão para notificações...");
        Notification.requestPermission().then(permission => {
            console.log("Permissão de notificação:", permission);
        });
    }
}

// Função de inicialização
function initNotificationBell() {
    console.log('Inicializando sino de notificações...');
    
    // Adicionar estilos CSS
    addNotificationStyles();
    
    // Verificar se deve mostrar o sino
    if (shouldShowBell()) {
        // Adicionar o sino à interface
        addNotificationBell();
        
        // Solicitar permissão para notificações web
        requestNotificationPermission();
        
        // Verificar notificações imediatamente
        setTimeout(() => {
            checkNotifications();
            
            // Configurar verificação periódica
            const checkInterval = (window.ticketAnswersConfig && window.ticketAnswersConfig.checkInterval) 
                ? window.ticketAnswersConfig.checkInterval * 1000 
                :3000; // Padrão: 3 segundos
            
            console.log('Configurando verificação periódica a cada', checkInterval/1000, 'segundos');
            window.notificationInterval = setInterval(checkNotifications, checkInterval);
        }, 2000); // Pequeno atraso inicial
    } else {
        console.log('Sino de notificações não será mostrado para este usuário/interface');
    }
}

// Inicializar quando o documento estiver pronto
$(document).ready(() => {
    console.log('Document ready, inicializando notification_bell.js');
    
    // Verificar se já existe uma instância
    if (typeof window.NotificationBellInitialized === 'undefined' || !window.NotificationBellInitialized) {
        console.log('Primeira inicialização do notification_bell.js');
        window.NotificationBellInitialized = true;
        
        // Inicializar com um pequeno atraso para garantir que tudo esteja carregado
        setTimeout(initNotificationBell, 500);
    } else {
        console.log('notification_bell.js já inicializado');
    }
});



// Exportar funções para uso global
window.NotificationBell = {
    checkNotifications: checkNotifications,
    updateNotificationCount: updateNotificationCount,
    toggleNotificationSound: toggleNotificationSound,
    playNotificationSound: playNotificationSound,
    getSoundEnabledState: getSoundEnabledState,
    updateBellCount: function() {
        console.log('Atualizando contador do sino com o valor total de notificações');
        
        // Obter o valor total de notificações da página, se disponível
        let totalNotifications = 0;
        if ($('#notification-count').length > 0) {
            totalNotifications = parseInt($('#notification-count').text()) || 0;
        }
        
        // Se não estiver na página de notificações, fazer uma chamada AJAX para obter o total
        if ($('#notification-count').length === 0) {
            $.ajax({
                url: CFG_GLPI.root_doc + '/plugins/ticketanswers/ajax/check_all_notifications.php',
                type: 'GET',
                dataType: 'json',
                async: false, // Síncrono para garantir que temos o valor antes de continuar
                success: function(data) {
                    totalNotifications = data.combined_count || data.count || 0;
                }
            });
        }
        
        // Atualizar o contador do sino com o valor total
        updateNotificationCount(totalNotifications);
        window.lastNotificationCount = totalNotifications;
        
        return totalNotifications;
    }
};

// Adicionar um interceptador global para solicitações AJAX
$(document).ready(function() {
    // Armazenar a função original $.ajax
    var originalAjax = $.ajax;
    
    // Substituir a função $.ajax por nossa versão personalizada
    $.ajax = function(options) {
        // Verificar se esta é uma solicitação para marcar uma notificação como lida
        if (options.url && (
            options.url.indexOf('mark_as_read.php') !== -1 ||
            options.url.indexOf('mark_all_as_read.php') !== -1 ||
            options.url.indexOf('mark_notification_as_read.php') !== -1
        )) {
            console.log('Interceptando solicitação AJAX para marcar notificação como lida:', options.url);
            
            // Armazenar a função de sucesso original
            var originalSuccess = options.success;
            
            // Substituir a função de sucesso
            options.success = function(response) {
                // Chamar a função de sucesso original
                if (originalSuccess) {
                    originalSuccess(response);
                }
                
                // Aguardar um momento para garantir que o DOM foi atualizado
                setTimeout(function() {
                    console.log('Atualizando contador do sino após marcar notificação como lida');
                    
                    // Forçar uma verificação de notificações para atualizar o contador do sino
                    if (typeof window.NotificationBell !== 'undefined') {
                        window.NotificationBell.updateBellCount();
                    }
                }, 500);
            };
        }
        
        // Chamar a função $.ajax original com as opções modificadas
        return originalAjax.apply(this, arguments);
    };
});
