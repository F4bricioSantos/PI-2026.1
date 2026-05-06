/**
 * Componente Sidebar reutilizável — ReformAí
 *
 * USO:
 *   import { renderSidebar } from '../src/components/sidebar.js';
 *   renderSidebar('sidebar-container', 'inicio');
 *
 * @param {string} containerId — ID do elemento onde a sidebar será injetada
 * @param {string} activePage  — Identificador da página ativa:
 *    'inicio' | 'detalhes' | 'cadastro' | 'login' | 'perfil'
 *    'novo-servico' | 'gerenciar' | 'portfolio' | 'avaliar'
 */

const NAV_ITEMS = [
  {
    id: 'inicio',
    label: 'Início',
    href: './dashboard.php',
    icon: '<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
  },
  {
    id: 'perfil',
    label: 'Meu Perfil',
    href: './perfil.php',
    icon: '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>',
  },
  {
    id: 'logout',
    label: 'Sair',
    href: './logout.php',
    icon: '<path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>',
  },
];

const PRO_ITEMS = [
  {
    id: 'novo-servico',
    label: 'Novo Serviço',
    href: './novo-servico.php',
    icon: '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>',
  },
  {
    id: 'gerenciar',
    label: 'Gerenciar',
    href: './gerenciar.php',
    icon: '<circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 010 14.14M4.93 4.93a10 10 0 000 14.14"/>',
  },
  {
    id: 'portfolio',
    label: 'Portfólio',
    href: './portfolio.php',
    icon: '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>',
  },
];

function buildNavLink(item, isActive) {
  const activeClasses = 'bg-orange text-white font-semibold';
  const defaultClasses = 'text-white/60 hover:text-white hover:bg-white/5 font-medium';

  return `
    <a href="${item.href}"
       class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm transition-all ${isActive ? activeClasses : defaultClasses}">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        ${item.icon}
      </svg>
      ${item.label}
    </a>
  `;
}

export function renderSidebar(containerId, activePage = '') {
  const container = document.getElementById(containerId);
  if (!container) return;

  const mainNav = NAV_ITEMS
    .map((item) => buildNavLink(item, item.id === activePage))
    .join('');

  const proNav = PRO_ITEMS
    .map((item) => buildNavLink(item, item.id === activePage))
    .join('');

  container.innerHTML = `
    <aside class="w-60 bg-sidebar flex flex-col flex-shrink-0 h-screen">

      <!-- Logo -->
      <div class="flex items-center gap-3 px-5 py-6 border-b border-white/10">
        <div class="w-10 h-10 bg-orange rounded-xl flex items-center justify-center flex-shrink-0">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/>
          </svg>
        </div>
        <div>
          <div class="font-extrabold text-white text-base leading-tight">ReformAí</div>
          <div class="text-[10px] font-semibold text-white/40 tracking-widest uppercase">Marketplace</div>
        </div>
      </div>

      <!-- Nav Principal -->
      <nav class="flex-1 px-3 py-5 space-y-1 overflow-y-auto">
        ${mainNav}

        <!-- Section label Profissional -->
        <div class="pt-5 pb-2 px-4 text-[10px] font-bold text-white/30 uppercase tracking-widest">Profissional</div>

        ${proNav}
      </nav>

      <!-- Rodapé do Usuário -->
      <div class="px-4 py-4 border-t border-white/10 flex items-center gap-3">
        <div class="w-9 h-9 rounded-full bg-teal-500 flex items-center justify-center text-xs font-bold text-white flex-shrink-0">UT</div>
        <div class="min-w-0">
          <div class="text-sm font-semibold text-white truncate">Usuário Teste</div>
          <div class="text-xs text-white/40">Sair do sistema</div>
        </div>
      </div>
    </aside>
  `;
}