document.addEventListener("DOMContentLoaded", function() {
    loadDashboardData();
});

async function loadDashboardData() {
    try {
        const response = await fetch('/tcc_project/api/utente/dashboard.php');
        const data = await response.json();
        
        if (response.ok) {
            // Populate stats
            document.getElementById('stat-reservas').textContent = data.reservas_ativas < 10 ? '0' + data.reservas_ativas : data.reservas_ativas;
            document.getElementById('stat-notificacoes').textContent = '0' + data.notificacoes;
            document.getElementById('stat-pontos').textContent = data.pontos_fidelidade;

            // Populate latest booking
            const bookingContainer = document.getElementById('latest-booking-container');
            const headerContainer = document.getElementById('latest-booking-header');
            
            if (data.ultima_reserva) {
                const r = data.ultima_reserva;
                const statusMap = {
                    'pendente': '<span class="status-badge badge-pending">Em Análise</span>',
                    'aprovada': '<span class="status-badge badge-confirmed" style="background:#2ecc71;color:#fff;">Aprovada</span>',
                    'rejeitada': '<span class="status-badge badge-pending" style="background:#e74c3c;color:#fff;">Rejeitada</span>',
                    'concluida': '<span class="status-badge badge-confirmed">Concluída</span>'
                };
                
                headerContainer.innerHTML = `<h5>Última Reserva</h5> ${statusMap[r.status_reserva]}`;
                
                bookingContainer.innerHTML = `
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <div style="background: #f1f1f1; border-radius: 8px; width: 100%; height: 100px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa fa-bed" style="font-size: 32px; color: #d4ab04;"></i>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <h6 style="font-weight: 700;">${r.tipos_servicos} - ${r.descrição}</h6>
                            <p class="text-muted mb-2"><i class="fa fa-calendar"></i> Check-in: ${r.data_checkin}</p>
                            <p class="text-muted mb-3"><i class="fa fa-user"></i> ${r.n_pessoa} Pessoa(s)</p>
                            <button class="btn btn-sm" style="background: #d4ab04; color: white;">Ver Detalhes</button>
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
