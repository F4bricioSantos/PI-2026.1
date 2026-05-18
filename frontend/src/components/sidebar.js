/**
 * Componente Sidebar Inteligente - ReformAí
 */

const NAV_ITEMS = [
  {
    id: 'inicio',
    label: 'Início',
    href: 'dashboard.php',
    icon: '<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
  },
  {
    id: 'perfil',
    label: 'Meu Perfil',
    href: 'perfil.php',
    icon: '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>',
  },
  {
    id: 'novo-servico',
    label: 'Novo Serviço',
    href: 'novo-servico.php',
    icon: '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>',
  }
];

const WORK_ITEMS = [
  {
    id: 'agendamentos',
    label: 'Meus Pedidos',
    href: 'agendamentos.php',
    icon: '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
    badgeKey: 'badgeAgendamentos'
  },
  {
    id: 'chat',
    label: 'Mensagens',
    href: 'chat.php',
    icon: '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
    badgeKey: 'badgeMensagens'
  }
];

const PRO_ONLY_ITEMS = [
  {
    id: 'gerenciar',
    label: 'Gerenciar Serviços',
    href: 'gerenciar.php',
    icon: '<circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 010 14.14M4.93 4.93a10 10 0 000 14.14"/>',
  },
  {
    id: 'portfolio',
    label: 'Meu Portfólio',
    href: 'portfolio.php',
    icon: '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>',
  },
];

const ADMIN_ITEMS = [
  {
    id: 'admin',
    label: 'Painel Geral',
    href: 'admin_dashboard.php',
    icon: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
  }
];

function buildNavLink(item, isActive, badgeCount = 0) {
  const activeClasses = 'bg-orange text-white font-bold shadow-md shadow-orange/20 scale-[1.01]';
  const defaultClasses = 'text-white/60 hover:text-white hover:bg-white/5 font-medium transition-all';

  const badgeHtml = badgeCount > 0 
    ? `<span class="ml-auto bg-red-500 text-white text-[10px] font-black px-2 py-0.5 rounded-full">${badgeCount}</span>`
    : '';

  return `
    <a href="${item.href}"
       class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm ${isActive ? activeClasses : defaultClasses}">
      <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        ${item.icon}
      </svg>
      <span class="truncate">${item.label}</span>
      ${badgeHtml}
    </a>
  `;
}

export function renderSidebar(containerId, activePage = '', isPro = false, isAdmin = false, badges = {}) {
  const container = document.getElementById(containerId);
  if (!container) return;

  const seguroBadges = badges || {};

  container.className = "w-60 bg-sidebar flex flex-col flex-shrink-0 h-screen border-r border-white/5 z-40";

  const mainNav = NAV_ITEMS
    .map((item) => buildNavLink(item, item.id === activePage))
    .join('');

  const workNav = WORK_ITEMS
    .map((item) => {
      const count = seguroBadges[item.badgeKey] || 0;
      return buildNavLink(item, item.id === activePage, count);
    })
    .join('');

  let proNavHtml = '';
  if (isPro) {
    const proLinks = PRO_ONLY_ITEMS
      .map((item) => buildNavLink(item, item.id === activePage))
      .join('');
    
    proNavHtml = `
      <div class="pt-5 pb-1.5 px-4 text-[9px] font-extrabold text-white/20 uppercase tracking-[0.2em]">Painel do Prestador</div>
      ${proLinks}
    `;
  }

  let adminNavHtml = '';
  if (isAdmin) {
    const adminLinks = ADMIN_ITEMS
      .map((item) => buildNavLink(item, item.id === activePage))
      .join('');

    adminNavHtml = `
      <div class="pt-5 pb-1.5 px-4 text-[9px] font-extrabold text-red-400/40 uppercase tracking-[0.2em]">Moderação & Admin</div>
      ${adminLinks}
    `;
  }

  container.innerHTML = `
    <div class="flex items-center gap-3 px-5 py-6 border-b border-white/10">
      <div class="w-9 h-9 bg-orange rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-orange/10">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/>
        </svg>
      </div>
      <div>
        <div class="font-black text-white text-base tracking-tight leading-tight">ReformAí</div>
        <div class="text-[9px] font-bold text-white/30 tracking-widest uppercase mt-0.5">Plataforma Pro</div>
      </div>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto custom-scroll">
      <div class="pb-1.5 px-4 text-[9px] font-extrabold text-white/20 uppercase tracking-[0.2em]">Navegação</div>
      ${mainNav}
      
      <div class="pt-5 pb-1.5 px-4 text-[9px] font-extrabold text-white/20 uppercase tracking-[0.2em]">Serviços</div>
      ${workNav}
      
      ${proNavHtml}
      ${adminNavHtml}
    </nav>

    <div class="p-3 border-t border-white/5 bg-black/10">
      <a href="logout.php" 
         class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-white/60 hover:text-white hover:bg-red-500/10 transition-all text-sm group">
        <svg class="w-4 h-4 text-white/40 group-hover:text-red-400 transition-colors" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
        </svg>
        <span class="font-medium group-hover:text-red-400 transition-colors">Sair da Conta</span>
      </a>
    </div>
  `;
}