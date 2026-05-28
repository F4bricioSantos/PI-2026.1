import { confirmar } from './modalConfirmacao.js';

const comQuemId = new URLSearchParams(window.location.search).get('com');
const urlApiContratos = '../../backend/controllers/ContratoController.php';

// ─── Modal de seleção de serviço (injetado dinamicamente) ────────────────────
function _criarModalServico(servicos) {
    document.getElementById('modal-escolha-servico')?.remove();

    const modal = document.createElement('div');
    modal.id = 'modal-escolha-servico';
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" id="overlay-escolha-servico"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 flex flex-col gap-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-orange-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-[#F97316]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 12h6m-3-3v6M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Qual serviço deseja contratar?</h3>
                    <p class="text-[11px] text-gray-400">Selecione um dos serviços disponíveis</p>
                </div>
            </div>
            <div class="flex flex-col gap-2" id="lista-servicos-escolha">
                ${servicos.map(s => `
                    <button
                        type="button"
                        data-id="${s.id}"
                        class="servico-opcao w-full text-left px-4 py-3 rounded-xl border border-gray-200 hover:border-[#F97316] hover:bg-orange-50 transition-all cursor-pointer group"
                    >
                        <span class="block text-xs font-bold text-gray-900 group-hover:text-[#F97316] transition-colors">
                            ${s.nome_servico}
                        </span>
                        ${s.preco ? `<span class="block text-[11px] text-gray-400 mt-0.5">A partir de R$ ${parseFloat(s.preco).toFixed(2).replace('.', ',')}</span>` : ''}
                    </button>
                `).join('')}
            </div>
            <button type="button" id="btn-cancelar-escolha-servico"
                class="w-full px-4 py-2 rounded-xl text-xs font-bold text-gray-500 bg-gray-100 hover:bg-gray-200 transition-all cursor-pointer">
                Cancelar
            </button>
        </div>
    `;

    document.body.appendChild(modal);
    return modal;
}

function _fecharModalServico() {
    document.getElementById('modal-escolha-servico')?.remove();
}

// ─── Fluxo principal ─────────────────────────────────────────────────────────
export async function abrirModalContrato() {
    if (!comQuemId) return;

    let servicos = [];
    try {
        const resp = await fetch(`${urlApiContratos}?acao=listar_servicos&prestador_id=${comQuemId}`);
        if (!resp.ok) throw new Error();
        servicos = await resp.json();
    } catch {
        _mostrarToastContrato('Erro ao buscar serviços. Tente novamente.');
        return;
    }

    if (!servicos || servicos.length === 0) {
        _mostrarToastContrato('Este usuário não possui serviços disponíveis.');
        return;
    }

    if (servicos.length === 1) {
        await _confirmarEEnviar(servicos[0]);
        return;
    }

    const modal = _criarModalServico(servicos);

    modal.querySelectorAll('.servico-opcao').forEach(btn => {
        btn.addEventListener('click', async () => {
            const servicoId = parseInt(btn.dataset.id);
            const servico   = servicos.find(s => s.id === servicoId);
            _fecharModalServico();
            await _confirmarEEnviar(servico);
        });
    });

    document.getElementById('btn-cancelar-escolha-servico')
        ?.addEventListener('click', _fecharModalServico);
    document.getElementById('overlay-escolha-servico')
        ?.addEventListener('click', _fecharModalServico);

    const escHandler = (e) => {
        if (e.key === 'Escape') { _fecharModalServico(); document.removeEventListener('keydown', escHandler); }
    };
    document.addEventListener('keydown', escHandler);
}

// ─── Mini-modal de confirmação antes de enviar ────────────────────────────────
function _confirmarEEnviar(servico) {
    return new Promise((resolve) => {
        document.getElementById('modal-confirmar-contrato')?.remove();

        const modal = document.createElement('div');
        modal.id = 'modal-confirmar-contrato';
        modal.className = 'fixed inset-0 z-50 flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 flex flex-col gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-orange-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-[#F97316]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">Confirmar proposta</h3>
                        <p class="text-[11px] text-gray-400">O prestador irá aceitar ou recusar</p>
                    </div>
                </div>
                <div class="bg-gray-50 rounded-xl px-4 py-3 border border-gray-100">
                    <p class="text-[11px] text-gray-500 mb-0.5">Serviço selecionado</p>
                    <p class="text-xs font-bold text-gray-900">${servico.nome_servico}</p>
                    ${servico.preco ? `<p class="text-[11px] text-gray-400 mt-0.5">A partir de R$ ${parseFloat(servico.preco).toFixed(2).replace('.', ',')}</p>` : ''}
                </div>
                <p class="text-[11px] text-gray-400 leading-relaxed">
                    Data e preço final serão combinados diretamente no chat.
                </p>
                <div class="flex gap-2">
                    <button type="button" id="btn-cancelar-confirmacao"
                        class="flex-1 px-4 py-2 rounded-xl text-xs font-bold text-gray-500 bg-gray-100 hover:bg-gray-200 transition-all cursor-pointer">
                        Cancelar
                    </button>
                    <button type="button" id="btn-confirmar-envio"
                        class="flex-1 px-4 py-2 rounded-xl text-xs font-bold text-white bg-[#F97316] hover:bg-[#EA580C] transition-all cursor-pointer shadow-md">
                        Enviar Proposta
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        document.getElementById('btn-cancelar-confirmacao').addEventListener('click', () => {
            modal.remove();
            resolve(false);
        });

        document.getElementById('btn-confirmar-envio').addEventListener('click', async () => {
            modal.remove();
            await _enviarContrato(servico.id);
            resolve(true);
        });
    });
}

async function _enviarContrato(servicoId) {
    try {
        const response = await fetch(`${urlApiContratos}?acao=propor_contrato`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                prestador_id: comQuemId,
                servico_id: servicoId
            })
        });

        const resultado = await response.json();
        if (response.ok && resultado.sucesso) {
            window.location.reload();
        } else {
            _mostrarToastContrato(resultado.erro || 'Falha ao enviar proposta.');
        }
    } catch {
        _mostrarToastContrato('Erro de conexão ao criar proposta.');
    }
}

function _mostrarToastContrato(msg) {
    const t = document.createElement('div');
    t.className = 'fixed bottom-6 right-6 z-[999] bg-red-500 text-white text-xs font-bold px-4 py-3 rounded-xl shadow-lg';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

// ─── Funções mantidas (usadas pelo chat.php) ─────────────────────────────────
export function fecharModalContrato() {
    document.getElementById('modal-contrato')?.classList.add('hidden');
    document.getElementById('modal-contrato')?.classList.remove('flex');
}

export async function enviarPropostaContrato(e) {
    if (e) e.preventDefault();
}

// ─── Configurações de modal por status ───────────────────────────────────────
const _configPorStatus = {
    aceito: {
        titulo:          'Aceitar proposta?',
        descricao:       'O contrato será registrado como aceito e o serviço entra em andamento.',
        labelConfirmar:  'Aceitar',
        corConfirmar:    'bg-green-600 hover:bg-green-700',
        icone: `<svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M4.5 12.75l6 6 9-13.5"/>
                </svg>`,
    },
    cancelado: {
        titulo:          'Cancelar contrato?',
        descricao:       'O registro do contrato será encerrado. Esta ação não pode ser desfeita.',
        labelConfirmar:  'Sim, cancelar',
        corConfirmar:    'bg-red-500 hover:bg-red-600',
        icone: `<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M6 18L18 6M6 6l12 12"/>
                </svg>`,
    },
    concluido: {
        titulo:          'Marcar como concluído?',
        descricao:       'O contrato será registrado como concluído para ambas as partes.',
        labelConfirmar:  'Concluir',
        corConfirmar:    'bg-[#F97316] hover:bg-[#EA580C]',
        icone: `<svg class="w-5 h-5 text-[#F97316]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>`,
    },
};

export async function alterarStatusContrato(id, status) {
    const config = _configPorStatus[status] ?? {
        titulo:          'Atualizar contrato?',
        descricao:       'Confirme para prosseguir com a alteração.',
        labelConfirmar:  'Confirmar',
        corConfirmar:    'bg-[#F97316] hover:bg-[#EA580C]',
    };

    const confirmado = await confirmar(config);
    if (!confirmado) return;

    try {
        const response = await fetch(`${urlApiContratos}?acao=mudar_status`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, status })
        });

        if (response.ok) {
            window.location.reload();
        } else {
            const erro = await response.json();
            _mostrarToastContrato(erro.erro || 'Erro ao atualizar.');
        }
    } catch {
        _mostrarToastContrato('Erro de conexão.');
    }
}