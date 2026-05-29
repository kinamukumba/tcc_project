// admin-dashboard.js

document.addEventListener("DOMContentLoaded", function() {
    loadDashboardStats();
});

async function loadDashboardStats() {
    try {
        const response = await fetch('/tcc_project/api/admin/dashboard.php');
        const data = await response.json();
        
        if (response.ok) {
            // Atualiza Cards de Estatísticas
            // Mapeamos os IDs ou classes dos elementos no dashboard.html
            // Como não tinham IDs específicos, vou usar seletores baseados no texto ou ordem
            
            const statsCards = document.querySelectorAll('.stat-details h3');
            if(statsCards.length >= 4) {
                statsCards[0].textContent = data.total_reservas;
                statsCards[1].textContent = data.total_utentes >= 1000 ? (data.total_utentes/1000).toFixed(1) + 'k' : data.total_utentes;
                statsCards[2].textContent = data.receita_total >= 1000 ? (data.receita_total/1000).toFixed(0) + 'k' : data.receita_total;
                statsCards[3].textContent = data.total_feedbacks;
            }

            // Atualiza Tabela de Reservas Recentes
            const tableBody = document.querySelector('.card-table tbody');
            if(tableBody && data.reservas_recentes) {
                tableBody.innerHTML = '';
                data.reservas_recentes.forEach(res => {
                    const row = document.createElement('tr');
                    
                    const statusClass = {
                        'pendente': 'badge-pending',
                        'aprovada': 'badge-confirmed',
                        'rejeitada': 'badge-cancelled',
                        'concluida': 'badge-info'
                    }[res.status_reserva] || 'badge-secondary';

                    const statusText = res.status_reserva.charAt(0).toUpperCase() + res.status_reserva.slice(1);

                    row.innerHTML = `
                        <td>#RSV-${res.id_reserva}</td>
                        <td>${res.utente}</td>
                        <td>${res.servico}</td>
                        <td>${formatDate(res.data_checkin)}</td>
                        <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-info" onclick="viewDetails(${res.id_reserva})" title="Detalhes"><i class="fa fa-eye"></i></button>
                            ${res.status_reserva === 'pendente' ? `
                                <button class="btn btn-sm btn-outline-success" onclick="updateStatus(${res.id_reserva}, 'aprovada')" title="Confirmar"><i class="fa fa-check"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="updateStatus(${res.id_reserva}, 'rejeitada')" title="Cancelar"><i class="fa fa-times"></i></button>
                            ` : ''}
                        </td>
                    `;
                    tableBody.appendChild(row);
                });
            }
        }
    } catch (e) {
        console.error("Erro ao carregar estatísticas do admin:", e);
    }
}

// Funções de ação (CRUD rápido)
async function updateStatus(id, newStatus) {
    const action = newStatus === 'aprovada' ? 'confirmar' : 'cancelar';
    if(!confirm(`Deseja realmente ${action} esta reserva?`)) return;

    try {
        const response = await fetch('/tcc_project/api/admin/reservas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_reserva: id, novo_status: newStatus })
        });
        if(response.ok) {
            notify.success("Status atualizado!");
            loadDashboardStats();
        }
    } catch(e) {
        notify.error("Erro ao processar ação.");
    }
}

function viewDetails(id) {
    window.location.href = `reservas.html?id=${id}`;
}

function formatDate(dateStr) {
    if(!dateStr) return '-';
    const date = new Date(dateStr);
    const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    return `${date.getDate()} ${months[date.getMonth()]}, ${date.getFullYear()}`;
}
