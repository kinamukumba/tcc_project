// admin-reservas.js

document.addEventListener("DOMContentLoaded", function() {
    loadReservations();

    // Filtros
    const filterButtons = document.querySelectorAll('.btn-group .btn');
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            filterButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const status = this.textContent.trim();
            loadReservations(status);
        });
    });

    // Pesquisa
    const searchInput = document.querySelector('input[placeholder^="Pesquisar"]');
    if(searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            const rows = document.querySelectorAll('#reservas-admin-table-body tr');
            rows.forEach(row => {
                row.toggleAttribute('hidden', !row.textContent.toLowerCase().includes(term));
            });
        });
    }
});

async function loadReservations(status = 'Todas') {
    try {
        let url = '/tcc_project/api/admin/reservas.php';
        if(status !== 'Todas') {
            // Mapear texto do botão para valor do banco
            const statusMap = {
                'Pendentes': 'pendente',
                'Confirmadas': 'aprovada',
                'Check-in': 'checkin',
                'Check-out': 'checkout',
                'Canceladas': 'cancelada'
            };
            url += `?status=${statusMap[status] || status.toLowerCase()}`;
        }

        const response = await fetch(url);
        const data = await response.json();
        
        const tableBody = document.getElementById('reservas-admin-table-body');
        if(!tableBody) return;

        tableBody.innerHTML = '';
        
        if (response.ok) {
            if (data.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-4">Nenhuma reserva encontrada.</td></tr>';
            } else {
                data.forEach(res => {
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
                        <td>${formatDate(res.data_checkout)}</td>
                        <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-info" onclick="viewDetails(${res.id_reserva})" title="Detalhes"><i class="fa fa-eye"></i></button>
                            ${res.status_reserva === 'pendente' ? `
                                <button class="btn btn-sm btn-success" onclick="updateStatus(${res.id_reserva}, 'aprovada')" title="Confirmar"><i class="fa fa-check"></i></button>
                                <button class="btn btn-sm btn-danger" onclick="updateStatus(${res.id_reserva}, 'rejeitada')" title="Cancelar"><i class="fa fa-times"></i></button>
                            ` : ''}
                            ${res.status_reserva === 'aprovada' ? `
                                <button class="btn btn-sm btn-success" onclick="updateStatus(${res.id_reserva}, 'checkin')" title="Fazer Check-in"><i class="fa fa-sign-in"></i> Check-in</button>
                                <button class="btn btn-sm btn-danger" onclick="updateStatus(${res.id_reserva}, 'cancelada')" title="Cancelar"><i class="fa fa-times"></i></button>
                            ` : ''}
                            ${res.status_reserva === 'checkin' ? `
                                <button class="btn btn-sm btn-warning text-dark" onclick="updateStatus(${res.id_reserva}, 'checkout')" title="Fazer Check-out"><i class="fa fa-sign-out"></i> Check-out</button>
                            ` : ''}
                        </td>
                    `;
                    tableBody.appendChild(row);
                });
            }
        }
    } catch (e) {
        console.error("Erro ao carregar reservas:", e);
    }
}

async function updateStatus(id, newStatus) {
    const action = newStatus === 'aprovada' ? 'confirmar' : 'cancelar';
    notify.confirm(`Deseja realmente ${action} esta reserva?`, async function() {
        try {
            const response = await fetch('/tcc_project/api/admin/reservas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_reserva: id, novo_status: newStatus })
            });
            const data = await response.json();
            if(response.ok) {
                notify.success(data.message);
                loadReservations($('.btn-group .btn.active').text().trim());
            } else {
                notify.error(data.message);
            }
        } catch(e) {
            notify.error("Erro ao conectar com o servidor.");
        }
    });
}

function formatDate(dateStr) {
    if(!dateStr) return '-';
    const date = new Date(dateStr + 'T00:00:00');
    return date.toLocaleDateString('pt-PT');
}

function viewDetails(id) {
    notify.info(`Carregando detalhes da reserva #${id}...`);
    // Aqui poderia abrir um modal com mais dados
}
