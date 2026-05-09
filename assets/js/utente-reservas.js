document.addEventListener("DOMContentLoaded", function() {
    loadReservations();
});

async function loadReservations() {
    try {
        const response = await fetch('/tcc_project/api/utente/reservas.php');
        const reservas = await response.json();
        
        const tbody = document.getElementById('reservas-table-body');
        tbody.innerHTML = '';
        
        if (response.ok && reservas.length > 0) {
            reservas.forEach(r => {
                const statusMap = {
                    'pendente': '<span class="status-badge badge-pending">Pendente</span>',
                    'aprovada': '<span class="status-badge badge-confirmed" style="background:#2ecc71;color:#fff;">Aprovada</span>',
                    'rejeitada': '<span class="status-badge badge-pending" style="background:#e74c3c;color:#fff;">Rejeitada</span>',
                    'concluida': '<span class="status-badge badge-confirmed">Concluída</span>'
                };
                
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>#RSV-${r.id_reserva}</td>
                    <td>${r.servico || '-'}</td>
                    <td>${r.data_checkin}</td>
                    <td>${r.data_checkout}</td>
                    <td>${r.preco_total || '-'}</td>
                    <td>${statusMap[r.status_reserva]}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-info" title="Ver Detalhes"><i class="fa fa-eye"></i></button>
                        ${r.status_reserva === 'pendente' ? `<button class="btn btn-sm btn-outline-danger" title="Cancelar"><i class="fa fa-trash"></i></button>` : ''}
                        ${r.status_reserva === 'concluida' ? `<button class="btn btn-sm btn-outline-warning" title="Deixar Feedback"><i class="fa fa-star"></i></button>` : ''}
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">Nenhuma reserva encontrada.</td></tr>';
        }
    } catch (e) {
        console.error("Erro ao carregar reservas:", e);
        document.getElementById('reservas-table-body').innerHTML = '<tr><td colspan="7" class="text-center text-danger">Erro ao buscar dados.</td></tr>';
    }
}
