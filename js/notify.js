/**
 * notify.js — Epic Sana Luanda Custom Notification System
 * Usage:
 *   notify.success('Mensagem de sucesso!')
 *   notify.error('Mensagem de erro!')
 *   notify.info('Mensagem informativa.')
 *   notify.warning('Aviso importante.')
 *   notify.confirm('Tem certeza?', onOkCallback)
 */

const notify = (function () {

    // Inject CSS once
    const style = document.createElement('style');
    style.textContent = `
        #notify-container {
            position: fixed;
            top: 25px;
            right: 25px;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 12px;
            pointer-events: none;
        }
        .notify-toast {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            background: #ffffff;
            padding: 16px 20px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            min-width: 300px;
            max-width: 400px;
            pointer-events: all;
            border-left: 5px solid #d4ab04;
            animation: notify-in 0.35s cubic-bezier(.23,1.01,.32,1) forwards;
            font-family: 'Poppins', sans-serif;
        }
        .notify-toast.hiding {
            animation: notify-out 0.35s ease forwards;
        }
        .notify-toast.success { border-color: #2ecc71; }
        .notify-toast.error   { border-color: #e74c3c; }
        .notify-toast.info    { border-color: #52c5fd; }
        .notify-toast.warning { border-color: #f39c12; }

        .notify-icon {
            font-size: 22px;
            margin-top: 2px;
            flex-shrink: 0;
        }
        .notify-toast.success .notify-icon { color: #2ecc71; }
        .notify-toast.error   .notify-icon { color: #e74c3c; }
        .notify-toast.info    .notify-icon { color: #52c5fd; }
        .notify-toast.warning .notify-icon { color: #f39c12; }
        .notify-toast.default .notify-icon { color: #d4ab04; }

        .notify-body { flex: 1; }
        .notify-title {
            font-weight: 600;
            font-size: 14px;
            color: #04091e;
            margin-bottom: 2px;
        }
        .notify-message {
            font-size: 13px;
            color: #555;
            line-height: 1.5;
        }
        .notify-close {
            background: none;
            border: none;
            color: #aaa;
            cursor: pointer;
            font-size: 18px;
            line-height: 1;
            padding: 0;
            flex-shrink: 0;
            margin-top: -2px;
            transition: color 0.2s;
        }
        .notify-close:hover { color: #333; }

        /* Confirm Dialog Overlay */
        #notify-overlay {
            position: fixed; inset: 0;
            background: rgba(4,9,30,0.55);
            z-index: 99998;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(3px);
            animation: notify-in 0.2s ease forwards;
            font-family: 'Poppins', sans-serif;
        }
        .notify-dialog {
            background: #fff;
            border-radius: 16px;
            padding: 35px 40px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        .notify-dialog .dialog-icon {
            font-size: 42px;
            color: #f39c12;
            margin-bottom: 15px;
        }
        .notify-dialog h5 {
            font-weight: 700;
            color: #04091e;
            margin-bottom: 10px;
        }
        .notify-dialog p {
            font-size: 14px;
            color: #666;
            margin-bottom: 25px;
        }
        .notify-dialog .dialog-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        .notify-dialog .btn-cancel {
            padding: 10px 28px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            background: #f8f9fa;
            color: #555;
            font-size: 14px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: 0.2s;
        }
        .notify-dialog .btn-cancel:hover { background: #e9ecef; }
        .notify-dialog .btn-confirm {
            padding: 10px 28px;
            border-radius: 8px;
            border: none;
            background: #d4ab04;
            color: #fff;
            font-size: 14px;
            cursor: pointer;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            transition: 0.2s;
        }
        .notify-dialog .btn-confirm:hover { background: #f8b100; }

        @keyframes notify-in {
            from { opacity: 0; transform: translateX(30px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes notify-out {
            from { opacity: 1; transform: translateX(0); }
            to   { opacity: 0; transform: translateX(40px); }
        }
    `;
    document.head.appendChild(style);

    // Create container
    const container = document.createElement('div');
    container.id = 'notify-container';
    document.body.appendChild(container);

    const icons = {
        success: 'fa fa-check-circle',
        error:   'fa fa-times-circle',
        info:    'fa fa-info-circle',
        warning: 'fa fa-exclamation-triangle',
        default: 'fa fa-bell',
    };

    const titles = {
        success: 'Sucesso',
        error:   'Erro',
        info:    'Informação',
        warning: 'Aviso',
        default: 'Notificação',
    };

    function show(message, type = 'default', duration = 4500) {
        const toast = document.createElement('div');
        toast.className = `notify-toast ${type}`;
        toast.innerHTML = `
            <i class="notify-icon ${icons[type] || icons.default}"></i>
            <div class="notify-body">
                <div class="notify-title">${titles[type] || titles.default}</div>
                <div class="notify-message">${message}</div>
            </div>
            <button class="notify-close" aria-label="Fechar">&times;</button>
        `;

        container.appendChild(toast);

        const closeBtn = toast.querySelector('.notify-close');
        function dismiss() {
            toast.classList.add('hiding');
            toast.addEventListener('animationend', () => toast.remove());
        }
        closeBtn.addEventListener('click', dismiss);

        if (duration > 0) {
            setTimeout(dismiss, duration);
        }
    }

    function confirm(message, onOk, onCancel) {
        const overlay = document.createElement('div');
        overlay.id = 'notify-overlay';
        overlay.innerHTML = `
            <div class="notify-dialog">
                <div class="dialog-icon"><i class="fa fa-question-circle"></i></div>
                <h5>Confirmar Ação</h5>
                <p>${message}</p>
                <div class="dialog-actions">
                    <button class="btn-cancel">Cancelar</button>
                    <button class="btn-confirm">Confirmar</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);

        overlay.querySelector('.btn-confirm').addEventListener('click', function () {
            overlay.remove();
            if (typeof onOk === 'function') onOk();
        });
        overlay.querySelector('.btn-cancel').addEventListener('click', function () {
            overlay.remove();
            if (typeof onCancel === 'function') onCancel();
        });
    }

    return {
        success: (msg, duration) => show(msg, 'success', duration),
        error:   (msg, duration) => show(msg, 'error',   duration),
        info:    (msg, duration) => show(msg, 'info',    duration),
        warning: (msg, duration) => show(msg, 'warning', duration),
        default: (msg, duration) => show(msg, 'default', duration),
        confirm,
    };
})();
