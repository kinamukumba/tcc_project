document.addEventListener("DOMContentLoaded", function() {
    loadServices();

    const form = document.getElementById('internalBookingForm');
    if(form) {
        form.addEventListener('submit', handleBookingSubmit);
    }
});

async function loadServices() {
    try {
        const response = await fetch('/tcc_project/api/utente/servicos.php');
        const servicos = await response.json();
        
        const select = document.getElementById('serviceSelect');
        select.innerHTML = '<option value="">Selecione um serviço</option>';
        
        if (response.ok && servicos.length > 0) {
            servicos.forEach(s => {
                const option = document.createElement('option');
                option.value = s.id_serviço;
                option.textContent = `${s.tipos_servicos} - ${s.descrição} (${s.preço} KZ/dia)`;
                select.appendChild(option);
            });
            
            // Re-initialize nice-select if it's being used by the template
            if($.fn.niceSelect) {
                $('#serviceSelect').niceSelect('update');
            }
        } else {
            select.innerHTML = '<option value="">Nenhum serviço disponível no momento</option>';
        }
    } catch (e) {
        console.error("Erro ao carregar serviços:", e);
    }
}

async function handleBookingSubmit(e) {
    e.preventDefault();
    
    // O formulário usa name="arrival" e name="departure"
    const formElements = e.target.elements;
    const checkin = formElements['arrival'].value;
    const checkout = formElements['departure'].value;
    const id_servico = formElements['id_servico'].value;
    const n_pessoa = parseInt(formElements['adults'].value) + parseInt(formElements['children'].value);

    if(!checkin || !checkout || !id_servico) {
        notify.warning('Preencha todos os campos obrigatórios!');
        return;
    }

    // Converter as datas de MM/DD/YYYY ou similar para YYYY-MM-DD
    // O template original pode estar usando um formato específico no input. 
    // Vamos assumir que a API precisa de YYYY-MM-DD, então seria bom ter certeza. 
    // Como simplificação para o fetch (caso seja string pura yyyy-mm-dd do HTML5 date):
    // Mas os inputs do template não são type="date". Se der erro no BD, o ideal é formatar.
    
    try {
        // Formatar datas caso venham no formato PT (dd/mm/yyyy) para o BD MySQL (yyyy-mm-dd)
        // Isso depende de como o datetimepicker está enviando o valor. 
        // Vamos apenas enviar como está para o fetch, se o backend aceitar o string ou tratar.
        // O ideal: tentar converter se houver barras.
        let parsedCheckin = checkin;
        let parsedCheckout = checkout;
        
        if(checkin.includes('/')) {
            const parts = checkin.split(' ')[0].split('/');
            if(parts.length === 3) parsedCheckin = `${parts[2]}-${parts[0]}-${parts[1]}`; // Assumindo MM/DD/YYYY do datepicker JS
        }
        if(checkout.includes('/')) {
            const parts = checkout.split(' ')[0].split('/');
            if(parts.length === 3) parsedCheckout = `${parts[2]}-${parts[0]}-${parts[1]}`;
        }

        const payload = {
            data_checkin: parsedCheckin,
            data_checkout: parsedCheckout,
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
