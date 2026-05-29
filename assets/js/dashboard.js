/**
 * dashboard.js — Epic Sana Luanda Dashboard Interactions
 * Handles button actions, table filters, and UX for all dashboard pages.
 */

$(function () {

    /* ── Sidebar Mobile Toggle ── */
    $('<button id="sidebarToggle" aria-label="Abrir Menu"><i class="fa fa-bars"></i></button>')
        .prependTo('.top-header')
        .on('click', function () {
            $('.sidebar').toggleClass('open');
            $('body').toggleClass('sidebar-open');
        });

    // Close sidebar when clicking outside on mobile
    $(document).on('click', function (e) {
        if ($('body').hasClass('sidebar-open') &&
            !$(e.target).closest('.sidebar, #sidebarToggle').length) {
            $('.sidebar').removeClass('open');
            $('body').removeClass('sidebar-open');
        }
    });

    /* ── Logout ── */
    $(document).on('click', '.btn-logout', function (e) {
        e.preventDefault();
        notify.confirm(
            'Tem certeza que deseja sair do sistema?',
            function () {
                notify.info('A sair do sistema...');
                if(typeof logout === 'function') {
                    setTimeout(logout, 800);
                } else {
                    setTimeout(function () { window.location.href = '../index.html'; }, 1500);
                }
            }
        );
        return false;
    });

    /* ── Admin: Booking Status Buttons ── */
    $(document).on('click', '.btn-outline-success[title="Confirmar"], .btn-success[title="Confirmar"]', function () {
        var row = $(this).closest('tr');
        notify.confirm('Confirmar esta reserva?', function () {
            row.find('.status-badge')
               .removeClass('badge-pending badge-cancelled')
               .addClass('badge-confirmed')
               .text('Confirmada');
            notify.success('Reserva confirmada com sucesso!');
        });
    });

    $(document).on('click', '.btn-outline-danger[title="Cancelar"], .btn-danger[title="Cancelar"]', function () {
        var row = $(this).closest('tr');
        notify.confirm('Cancelar esta reserva? Esta ação não pode ser revertida.', function () {
            row.find('.status-badge')
               .removeClass('badge-pending badge-confirmed')
               .addClass('badge-cancelled')
               .text('Cancelada');
            notify.warning('Reserva cancelada.');
        });
    });

    $(document).on('click', '.btn-outline-info[title="Detalhes"], .btn-info[title="Detalhes"]', function () {
        notify.info('A carregar detalhes da reserva...');
    });

    $(document).on('click', '.btn-warning[title="Editar"], .btn-outline-warning[title="Editar"]', function () {
        notify.default('Função de edição em desenvolvimento.');
    });

    /* ── Admin: User Management ── */
    $(document).on('click', '.btn-outline-success[title]', function () {
        var title = $(this).attr('title');
        if (title === 'Ativar') {
            var row = $(this).closest('tr');
            row.find('.badge-secondary').removeClass('badge-secondary').addClass('badge-success').text('Ativo');
            notify.success('Utente ativado com sucesso!');
        }
    });

    $(document).on('click', '.btn-outline-danger[title="Suspender"], .btn-outline-danger[title="Bloquear"]', function () {
        notify.confirm('Suspender este utente?', function () {
            notify.warning('Utente suspenso.');
        });
    });

    /* ── Admin: Reports Export ── */
    $(document).on('click', '.btn-outline-dark', function () {
        notify.info('A preparar o relatório em PDF... Por favor aguarde.');
    });

    /* ── Feedback: Hide Review ── */
    $(document).on('click', '.btn-outline-secondary[title="Ocultar"]', function () {
        var row = $(this).closest('tr');
        notify.confirm('Ocultar este comentário do público?', function () {
            row.find('.badge-info').removeClass('badge-info').addClass('badge-secondary').text('Oculto');
            notify.warning('Avaliação ocultada do perfil público.');
        });
    });

    $(document).on('click', '.btn-outline-secondary[title="Responder"]', function () {
        notify.default('A abrir janela de resposta...');
    });

    /* ── Utente: Cancel Booking ── */
    $(document).on('click', '.btn-outline-danger[title="Cancelar"]', function () {
        var row = $(this).closest('tr');
        notify.confirm('Cancelar esta reserva? Esta ação é irreversível.', function () {
            row.find('.status-badge')
               .removeClass('badge-pending badge-confirmed')
               .addClass('badge-cancelled')
               .text('Cancelada');
            notify.warning('Reserva cancelada.');
        });
    });

    /* ── Utente: Leave Feedback ── */
    $(document).on('click', '.btn-outline-warning[title="Deixar Feedback"]', function () {
        notify.info('A redirecionar para o formulário de avaliação...');
    });

    /* ── Utente: Profile Save ── */
    $(document).on('click', 'form .btn[style*="d4ab04"]', function (e) {
        if ($(this).closest('form').find('input[type="password"]').length) {
            e.preventDefault();
            notify.success('Perfil atualizado com sucesso!');
        }
    });

    /* ── Messages: Send ── */
    $(document).on('click', '.chat-input .btn, .chat-input button', function () {
        var input = $(this).closest('.chat-input').find('input[type="text"]');
        var msg = input.val().trim();
        if (!msg) {
            notify.warning('Escreva uma mensagem antes de enviar.');
            return;
        }
        var msgDiv = $('<div class="message sent"></div>').text(msg);
        $(this).closest('.chat-box, .chat-container').find('.chat-messages').append(msgDiv);
        input.val('');
        // Scroll to bottom
        var messages = $(this).closest('.chat-box, .chat-container').find('.chat-messages');
        messages.scrollTop(messages[0].scrollHeight);
    });

    /* ── Filter buttons in reservas.html ── */
    $(document).on('click', '.btn-group .btn-outline-secondary', function () {
        $(this).siblings().removeClass('active');
        $(this).addClass('active');
        var filter = $(this).text().trim();
        var rows = $('tbody tr');
        if (filter === 'Todas') {
            rows.show();
        } else {
            rows.each(function () {
                var badge = $(this).find('.status-badge').text().trim();
                var show =
                    (filter === 'Pendentes'   && badge === 'Pendente')   ||
                    (filter === 'Confirmadas' && badge === 'Confirmada') ||
                    (filter === 'Canceladas'  && badge === 'Cancelada');
                $(this).toggle(show);
            });
        }
    });

    /* ── Inline search ── */
    $(document).on('input', '.input-group input[placeholder^="Pesquisar"]', function () {
        var term = $(this).val().toLowerCase();
        $('tbody tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(term));
        });
    });

});
