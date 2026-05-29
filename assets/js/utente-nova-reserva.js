document.addEventListener("DOMContentLoaded", function() {
    loadServices();

    const form = document.getElementById('internalBookingForm');
    if(form) {
        form.addEventListener('submit', handleBookingSubmit);
        form.addEventListener('change', updatePriceSummary);
        
        const arrivalInput = document.getElementById('arrivalDate');
        const departureInput = document.getElementById('departureDate');
        
        // Restricting selection to today or future dates
        const today = new Date().toISOString().split('T')[0];
        if(arrivalInput) {
            arrivalInput.min = today;
            arrivalInput.addEventListener('input', function() {
                if(departureInput) {
                    departureInput.min = this.value;
                    if(departureInput.value && departureInput.value < this.value) {
                        departureInput.value = this.value;
                    }
                }
                updatePriceSummary();
            });
        }
        if(departureInput) {
            departureInput.min = today;
            departureInput.addEventListener('input', updatePriceSummary);
        }
    }
});

let availableServices = [];

async function loadServices() {
    try {
        const response = await fetch('/tcc_project/api/utente/servicos.php');
        const data = await response.json();
        
        if (response.ok) {
            availableServices = data;
            const select = document.getElementById('serviceSelect');
            if(!select) return;

            select.innerHTML = '<option value="">Selecione um serviço (Quarto, Sala, Mesa...)</option>';
            
            availableServices.forEach(s => {
                const option = document.createElement('option');
                option.value = s.id_serviço;
                option.textContent = `${s.tipos_servicos} - ${parseFloat(s.preço).toLocaleString('pt-PT')} KZ/dia`;
                select.appendChild(option);
            });
            
            if($.fn.niceSelect) {
                $('#serviceSelect').niceSelect('update');
            }

            checkPendingBooking();
            updatePriceSummary();
        }
    } catch (e) {
        console.error("Erro ao carregar serviços:", e);
    }
}

function updatePriceSummary() {
    const serviceId = document.getElementById('serviceSelect').value;
    const checkin = document.getElementById('arrivalDate').value;
    const checkout = document.getElementById('departureDate').value;
    
    const summaryContainer = document.getElementById('booking-summary');
    if(!summaryContainer) return;

    if(!serviceId || !checkin || !checkout) {
        summaryContainer.innerHTML = '<p class="text-muted small text-center py-3">Preencha as datas e selecione um serviço para ver o resumo do pagamento.</p>';
        return;
    }

    const service = availableServices.find(s => s.id_serviço == serviceId);
    if(!service) return;

    let days = 1;
    const d1 = new Date(checkin + 'T00:00:00');
    const d2 = new Date(checkout + 'T00:00:00');
    
    if (!isNaN(d1) && !isNaN(d2)) {
        const diffTime = d2 - d1;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        days = diffDays > 0 ? diffDays : 1;
    }

    const total = days * parseFloat(service.preço);
    
    summaryContainer.innerHTML = `
        <div class="p-3 rounded" style="background: rgba(212, 171, 4, 0.05); border: 1px dashed #d4ab04;">
            <h6 class="mb-2" style="color: #d4ab04; font-weight: 600;">Resumo da Reserva</h6>
            <div class="d-flex justify-content-between mb-1 small">
                <span>Serviço:</span>
                <span class="font-weight-bold">${service.tipos_servicos}</span>
            </div>
            <div class="d-flex justify-content-between mb-1 small">
                <span>Diária:</span>
                <span>${parseFloat(service.preço).toLocaleString('pt-PT')} KZ</span>
            </div>
            <div class="d-flex justify-content-between mb-1 small">
                <span>Duração:</span>
                <span>${days} dia(s)</span>
            </div>
            <hr class="my-2" style="border-top: 1px solid rgba(212, 171, 4, 0.2);">
            <div class="d-flex justify-content-between font-weight-bold">
                <span>Total Estimado:</span>
                <span style="color: #d4ab04; font-size: 1.1rem;">${total.toLocaleString('pt-PT')} KZ</span>
            </div>
        </div>
    `;
}

function checkPendingBooking() {
    const pendingBooking = localStorage.getItem('pendingBooking');
    if (pendingBooking) {
        try {
            const data = JSON.parse(pendingBooking);
            
            const arrivalInput = document.getElementById('arrivalDate');
            const departureInput = document.getElementById('departureDate');
            if(arrivalInput) arrivalInput.value = formatToInputDate(data.arrival);
            if(departureInput) departureInput.value = formatToInputDate(data.departure);
            
            const serviceSelect = document.querySelector('select[name="id_servico"]');
            if(serviceSelect && data.roomType) {
                serviceSelect.value = data.roomType;
            }
            
            if($.fn.niceSelect) {
                $('select').niceSelect('update');
            }

            localStorage.removeItem('pendingBooking');
            if(typeof notify !== 'undefined') notify.info('Dados da reserva preenchidos!');
            updatePriceSummary();
        } catch(e) {
            console.error("Erro ao parsear pendingBooking:", e);
        }
    }
}

function formatToInputDate(str) {
    if(!str) return '';
    if(str.includes('/')) {
        const parts = str.split('/');
        if(parts.length === 3) return `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
    }
    return str;
}

async function handleBookingSubmit(e) {
    e.preventDefault();
    
    const formElements = e.target.elements;
    const checkin = formElements['arrival'].value;
    const checkout = formElements['departure'].value;
    const id_servico = formElements['id_servico'].value;
    const n_pessoa = (parseInt(formElements['adults'].value) || 0) + (parseInt(formElements['children'].value) || 0);

    if(!checkin || !checkout || !id_servico) {
        notify.warning('Preencha todos os campos obrigatórios!');
        return;
    }

    const today = new Date().toISOString().split('T')[0];
    if(checkin < today) {
        notify.warning('A data de check-in não pode ser no passado!');
        return;
    }
    if(checkout <= checkin) {
        notify.warning('A data de check-out deve ser posterior à data de check-in!');
        return;
    }

    try {
        const payload = {
            data_checkin: checkin,
            data_checkout: checkout,
            id_servico: id_servico,
            n_pessoa: n_pessoa || 1
        };

        const response = await fetch('/tcc_project/api/utente/reservas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        
        if (response.ok) {
            notify.success('Reserva efetuada com sucesso!');
            setTimeout(() => {
                window.location.href = 'minhas-reservas.html';
            }, 1500);
        } else {
            notify.error(data.message || 'Erro ao realizar reserva.');
        }
    } catch(e) {
        notify.error('Erro de conexão com o servidor.');
        console.error(e);
    }
}
