// assets/js/utente-dashboard.js
// Dynamic client dashboard logic

document.addEventListener("DOMContentLoaded", function() {
    loadDashboardData();
    loadNotifications();
});

async function loadDashboardData() {
    try {
        const response = await fetch('/tcc_project/api/utente/dashboard.php');
        const data = await response.json();
        
        if (response.ok) {
            // Populate stats
            document.getElementById('stat-reservas').textContent = data.reservas_ativas < 10 ? '0' + data.reservas_ativas : data.reservas_ativas;
            document.getElementById('stat-pontos').textContent = data.pontos_fidelidade;

            // Populate latest booking
            const bookingContainer = document.getElementById('latest-booking-container');
            const headerContainer = document.getElementById('latest-booking-header');
            
            if (data.ultima_reserva) {
                const r = data.ultima_reserva;
                const statusMap = {
                    'pendente': '<span class="status-badge badge-pending">Em Análise</span>',
                    'aprovada': '<span class="status-badge badge-confirmed" style="background:#2ecc71;color:#fff;">Aprovada</span>',
                    'checkin': '<span class="status-badge badge-confirmed" style="background:#3498db;color:#fff;">Check-in</span>',
                    'checkout': '<span class="status-badge badge-confirmed" style="background:#9b59b6;color:#fff;">Check-out</span>',
                    'concluida': '<span class="status-badge badge-confirmed">Concluída</span>',
                    'cancelada': '<span class="status-badge badge-cancelled">Cancelada</span>'
                };
                
                headerContainer.innerHTML = `<h5>Última Reserva (${r.codigo_reserva || 'S/C'})</h5> ${statusMap[r.status_reserva]}`;
                
                bookingContainer.innerHTML = `
                    <div class="row align-items-center">
                        <div class="col-md-4 mb-2 mb-md-0">
                            <div style="background: #f1f1f1; border-radius: 8px; width: 100%; height: 100px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa fa-bed" style="font-size: 32px; color: #d4ab04;"></i>
                             </div>
                        </div>
                        <div class="col-md-8">
                            <h6 style="font-weight: 700;">${r.tipos_servicos} - ${r.descrição}</h6>
                            <p class="text-muted mb-1" style="font-size:13px;"><i class="fa fa-calendar"></i> Check-in: ${formatDate(r.data_checkin)}</p>
                            <p class="text-muted mb-1" style="font-size:13px;"><i class="fa fa-calendar"></i> Check-out: ${formatDate(r.data_checkout)}</p>
                            <p class="text-muted mb-2" style="font-size:13px;"><i class="fa fa-user"></i> ${r.n_pessoa} Pessoa(s)</p>
                            <a href="minhas-reservas.html" class="btn btn-sm" style="background: #d4ab04; color: white;">Ver Todas</a>
                        </div>
                    </div>
                `;
            } else {
                headerContainer.innerHTML = `<h5>Última Reserva</h5>`;
                bookingContainer.innerHTML = `<p class="text-muted text-center" style="padding: 20px;">Você ainda não possui reservas.</p>`;
            }
        }
    } catch (e) {
        console.error("Erro ao carregar dashboard:", e);
    }
}

async function loadNotifications() {
    try {
        const response = await fetch('/tcc_project/api/utente/notificacoes.php');
        if (response.ok) {
            const data = await response.json();
            
            // Count unread
            const unreadCount = data.filter(n => n.lida == 0).length;
            document.getElementById('stat-notificacoes').textContent = unreadCount < 10 ? '0' + unreadCount : unreadCount;
            
            // Show alert of latest unread notification on dashboard if any
            if(unreadCount > 0) {
                const latestUnread = data.find(n => n.lida == 0);
                notify.info("Lembrete: " + latestUnread.mensagem);
                
                // Mark it as read so it doesn't pop up again next reload
                fetch('/tcc_project/api/utente/notificacoes.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_notificacao: latestUnread.id_notificacao })
                });
            }
        }
    } catch (e) {
        console.error("Erro ao carregar notificações:", e);
    }
}

function formatDate(dateStr) {
    if(!dateStr) return '-';
    var parts = dateStr.split('-');
    if(parts.length === 3) return parts[2] + '/' + parts[1] + '/' + parts[0];
    return dateStr;
}
