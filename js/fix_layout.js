$(document).ready(function() {
    // Função para corrigir o layout dos botões
    function fixButtonLayout() {
        console.log('Corrigindo layout dos ícones de notificação...');
        
        // Verificar se os botões existem
        if ($('.notification-bell').length > 0 && $('.sound-toggle').length > 0) {
            // Verificar se o botão de som está abaixo do sino
            var bellPos = $('.notification-bell').offset();
            var soundPos = $('.sound-toggle').offset();
            
            if (bellPos && soundPos && Math.abs(soundPos.top - bellPos.top) > 5) {
                console.log('Detectado problema de layout, recriando ícones...');
                
                // Obter o contêiner pai
                var container = $('.notification-bell').parent();
                
                // Criar um novo contêiner para os ícones
                var newContainer = $('<div class="notification-container"></div>');
                
                // Remover a classe btn dos botões para eliminar a aparência de botão
                $('.notification-bell, .sound-toggle').removeClass('btn btn-outline-secondary btn-sm');
                
                // Remover os botões existentes
                var bell = $('.notification-bell').detach();
                var sound = $('.sound-toggle').detach();
                
                // Adicionar os ícones ao novo contêiner
                newContainer.append(bell);
                newContainer.append(sound);
                
                // Adicionar o novo contêiner ao DOM
                container.append(newContainer);
                
                // Aplicar estilos inline para garantir
                $('.notification-container').css({
                    'display': 'inline-flex',
                    'flex-direction': 'row',
                    'align-items': 'center'
                });
                
                $('.notification-bell, .sound-toggle').css({
                    'display': 'inline-block',
                    'vertical-align': 'middle',
                    'border': 'none',
                    'background': 'transparent',
                    'box-shadow': 'none',
                    'padding': '6px',
                    'margin': '0 3px'
                });
                
                // Garantir que os ícones tenham o mesmo tamanho
                $('.notification-bell i, .sound-toggle i').css({
                    'font-size': '18px',
                    'line-height': '1'
                });
                
                // Reconfigurar eventos
                $('.notification-bell').on('click', function() {
                    window.location.href = CFG_GLPI.root_doc + '/plugins/ticketanswers/front/index.php';
                });
                
                $('.sound-toggle').on('click', function() {
                    // Alternar classe do ícone
                    var icon = $(this).find('i');
                    if (icon.hasClass('fa-volume-up')) {
                        icon.removeClass('fa-volume-up').addClass('fa-volume-mute');
                        $(this).attr('title', 'Ativar som de notificações');
                        localStorage.setItem('ticketAnswersSoundEnabled', 'false');
                    } else {
                        icon.removeClass('fa-volume-mute').addClass('fa-volume-up');
                        $(this).attr('title', 'Desativar som de notificações');
                        localStorage.setItem('ticketAnswersSoundEnabled', 'true');
                    }
                });
            }
        }
    }
    
    // Executar a correção após um pequeno atraso
    setTimeout(fixButtonLayout, 1000);
    
    // Executar novamente se a janela for redimensionada
    $(window).on('resize', function() {
        setTimeout(fixButtonLayout, 500);
    });
    
    // Executar novamente após carregamento completo da página
    $(window).on('load', function() {
        setTimeout(fixButtonLayout, 1500);
    });
});