// ─── Modal de Confirmação Genérico ───────────────────────────────────────────
// Uso:
//   const confirmado = await confirmar({ titulo, descricao, labelConfirmar, corConfirmar });
//   if (confirmado) { ... }

export function confirmar({
    titulo         = 'Tem certeza?',
    descricao      = '',
    labelConfirmar = 'Confirmar',
    labelCancelar  = 'Cancelar',
    corConfirmar   = 'bg-orange hover:bg-orange-dark',  // tailwind classes
    icone          = null  // HTML string do SVG, opcional
} = {}) {
    return new Promise((resolve) => {
        // Remove instância anterior se existir
        document.getElementById('modal-confirmacao-generico')?.remove();

        const iconeHtml = icone ?? `
            <svg class="w-5 h-5 text-[#F97316]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>`;

        const modal = document.createElement('div');
        modal.id = 'modal-confirmacao-generico';
        modal.className = 'fixed inset-0 z-[60] flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" id="_overlay-confirmacao"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 flex flex-col gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-orange-50 flex items-center justify-center flex-shrink-0">
                        ${iconeHtml}
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">${titulo}</h3>
                        ${descricao ? `<p class="text-[11px] text-gray-400 leading-relaxed mt-0.5">${descricao}</p>` : ''}
                    </div>
                </div>
                <div class="flex gap-2 justify-end">
                    <button id="_btn-cancelar-confirmacao"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-gray-500 bg-gray-100 hover:bg-gray-200 transition-all cursor-pointer">
                        ${labelCancelar}
                    </button>
                    <button id="_btn-confirmar-confirmacao"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white ${corConfirmar} transition-all cursor-pointer shadow-md">
                        ${labelConfirmar}
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const fechar = (resultado) => {
            modal.remove();
            document.removeEventListener('keydown', escHandler);
            resolve(resultado);
        };

        const escHandler = (e) => { if (e.key === 'Escape') fechar(false); };

        document.getElementById('_btn-confirmar-confirmacao').addEventListener('click', () => fechar(true));
        document.getElementById('_btn-cancelar-confirmacao').addEventListener('click',  () => fechar(false));
        document.getElementById('_overlay-confirmacao').addEventListener('click',       () => fechar(false));
        document.addEventListener('keydown', escHandler);
    });
}