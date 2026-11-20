// m3r-alerts.js
window.m3rAlert = function(mensagem, tipo = 'info') {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'm3r-alert-overlay';
        
        let icon = '';
        if (tipo === 'success') icon = '✅ ';
        else if (tipo === 'danger' || tipo === 'error') icon = '❌ ';
        else if (tipo === 'warning') icon = '⚠️ ';
        else icon = 'ℹ️ ';

        overlay.innerHTML = `
            <div class="m3r-alert-box">
                <h5 class="mb-3">${icon} Aviso</h5>
                <p class="mb-4">${mensagem}</p>
                <button class="btn btn-primary w-100 btn-fechar-alerta">OK</button>
            </div>
        `;
        document.body.appendChild(overlay);

        overlay.querySelector('.btn-fechar-alerta').addEventListener('click', () => {
            overlay.remove();
            resolve(true);
        });
    });
};

window.m3rConfirm = function(mensagem) {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'm3r-alert-overlay';
        
        overlay.innerHTML = `
            <div class="m3r-alert-box">
                <h5 class="mb-3">⚠️ Confirmação</h5>
                <p class="mb-4">${mensagem}</p>
                <div class="d-flex gap-2">
                    <button class="btn btn-secondary w-50 btn-cancelar-alerta">Cancelar</button>
                    <button class="btn btn-danger w-50 btn-confirmar-alerta">Confirmar</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);

        overlay.querySelector('.btn-cancelar-alerta').addEventListener('click', () => {
            overlay.remove();
            resolve(false);
        });

        overlay.querySelector('.btn-confirmar-alerta').addEventListener('click', () => {
            overlay.remove();
            resolve(true);
        });
    });
};

window.m3rDialogOpcoesConflitoSimultaneo = function({ titulo, mensagem, proximoHorario, textoOpcao1 = '1. Escolher Disponível', textoOpcao2 = '2. Agendar Próximo Horário' }) {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'm3r-alert-overlay';

        const btnProxHtml = proximoHorario ? `
            <button type="button" class="btn btn-primary w-100 py-2 d-flex align-items-center justify-content-center gap-2 btn-opcao-proximo">
                <span>⏰</span> <span>${textoOpcao2} (<strong>${proximoHorario}</strong>)</span>
            </button>
        ` : '';

        overlay.innerHTML = `
            <div class="m3r-alert-box text-start" style="max-width: 480px;">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="fs-4">❌</span>
                    <h5 class="mb-0 text-danger fw-bold">${titulo || 'Agendamento Simultâneo Não Permitido'}</h5>
                </div>
                <div class="mb-4 text-dark small" style="white-space: pre-line;">${mensagem}</div>
                <div class="d-flex flex-column gap-2 mb-3">
                    <button type="button" class="btn btn-outline-primary w-100 py-2 d-flex align-items-center justify-content-center gap-2 btn-opcao-disponivel">
                        <span>👤</span> <span>${textoOpcao1}</span>
                    </button>
                    ${btnProxHtml}
                </div>
                <button type="button" class="btn btn-light btn-sm w-100 border text-muted btn-cancelar-conflito">Cancelar</button>
            </div>
        `;
        document.body.appendChild(overlay);

        overlay.querySelector('.btn-opcao-disponivel').addEventListener('click', () => {
            overlay.remove();
            resolve('escolher_disponivel');
        });

        if (proximoHorario) {
            overlay.querySelector('.btn-opcao-proximo').addEventListener('click', () => {
                overlay.remove();
                resolve('proximo_horario');
            });
        }

        overlay.querySelector('.btn-cancelar-conflito').addEventListener('click', () => {
            overlay.remove();
            resolve(null);
        });
    });
};

// Toggle Modo Noturno (Persistente no localStorage)
function initTheme() {
    const isDark = localStorage.getItem('m3r_theme') === 'dark';
    if (isDark) {
        document.body.classList.add('dark-mode');
    }
}
initTheme();

window.toggleDarkMode = function() {
    document.body.classList.toggle('dark-mode');
    if (document.body.classList.contains('dark-mode')) {
        localStorage.setItem('m3r_theme', 'dark');
    } else {
        localStorage.setItem('m3r_theme', 'light');
    }
};
