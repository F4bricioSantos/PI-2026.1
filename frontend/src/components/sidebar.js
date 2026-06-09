const NAV_ITEMS = [
  {
    id: 'inicio',
    label: 'Explorar Serviços',
    href: '/dashboard',
    icon: '<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
  },
];

const CLIENT_ITEMS = [
  {
    id: 'agendamentos',
    label: 'Minhas Contratações',
    href: '/meus-pedidos',
    icon: '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
    badgeKey: 'badgeAgendamentos'
  },
  {
    id: 'favoritos',
    label: 'Serviços Favoritos',
    href: '/dashboard?categoria=Favoritos',
    icon: '<path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>',
  },
  {
    id: 'chat',
    label: 'Minhas Conversas',
    href: '/chat',
    icon: '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
    badgeKey: 'badgeMensagens'
  }
];

const PRO_ONLY_ITEMS = [
  {
    id: 'novo-servico',
    label: 'Anunciar Serviço',
    href: '/novo-servico',
    icon: '<path d="M12 5v14M5 12h14"/>',
  },
  {
    id: 'gerenciar',
    label: 'Gerenciar Serviços',
    href: '/gerenciar',
    icon: '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
  },
  {
    id: 'portfolio',
    label: 'Meu Portfólio',
    href: '/portfolio',
    icon: '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>',
  },
];

const ADMIN_ITEMS = [
  {
    id: 'admin',
    label: 'Painel Geral',
    href: '/admin',
    icon: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
  }
];

const SETTINGS_ITEMS = [
  {
    id: 'perfil',
    label: 'Meu Perfil',
    href: '/perfil',
    icon: '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>',
  }
];

function buildNavLink(item, isActive, badgeCount = 0) {
  const activeClasses = 'bg-orange/10 text-orange border-l-4 border-orange font-bold';
  const defaultClasses = 'text-white/60 hover:text-white hover:bg-white/5 font-medium border-l-4 border-transparent transition-all';

  const badgeHtml = badgeCount > 0
    ? `<span class="sidebar-msg-badge ml-auto bg-red-500 text-white text-[10px] font-black px-2 py-0.5 rounded-full">${badgeCount}</span>`
    : '';

  return `
    <a href="${item.href}"
       class="flex items-center gap-3 px-6 py-3 text-sm ${isActive ? activeClasses : defaultClasses}">
      <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        ${item.icon}
      </svg>
      <span class="truncate">${item.label}</span>
      ${badgeHtml}
    </a>
  `;
}

export function renderSidebar(containerId, activePage = '', isPro = false, isAdmin = false, badges = {}, user = null) {
  const container = document.getElementById(containerId);
  if (!container) return;

  const seguroBadges = badges || {};
  const currentPath = window.location.pathname.replace(/\/$/, '') || '/dashboard';
  const currentSearch = window.location.search;

  const isItemActive = (item) => {
    if (activePage === item.id) return true;
    const itemPath = item.href.split('?')[0];
    if (itemPath !== currentPath) return false;
    const itemSearch = item.href.includes('?') ? '?' + item.href.split('?')[1] : '';
    if (itemSearch) return itemSearch === currentSearch;
    return !currentSearch || currentSearch === '';
  };

  container.className = "fixed inset-y-0 left-0 z-50 w-60 bg-sidebar flex flex-col h-screen border-r border-white/5 transition-transform duration-300 ease-in-out transform -translate-x-full md:relative md:translate-x-0";

  const mainNav = NAV_ITEMS
    .map((item) => buildNavLink(item, isItemActive(item)))
    .join('');

  const clientNav = CLIENT_ITEMS
    .map((item) => {
      const count = seguroBadges[item.badgeKey] || 0;
      return buildNavLink(item, isItemActive(item), count);
    })
    .join('');

  let proNavHtml = '';
  if (isPro) {
    const proLinks = PRO_ONLY_ITEMS
      .map((item) => buildNavLink(item, isItemActive(item)))
      .join('');

    proNavHtml = `
      <div class="pt-6 pb-2 px-6 text-[10px] font-bold text-white/30 uppercase tracking-widest">Meu Trabalho</div>
      ${proLinks}
    `;
  } else {
    proNavHtml = `
      <div class="pt-6 pb-2 px-6 text-[10px] font-bold text-white/30 uppercase tracking-widest">Seja um Profissional</div>
      <a href="/novo-servico" class="flex items-center gap-3 px-6 py-3 text-sm text-orange hover:bg-orange/5 transition-all font-bold">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
        Anunciar Meus Serviços
      </a>`;
  }

  let adminNavHtml = '';
  if (isAdmin) {
    const adminLinks = ADMIN_ITEMS
      .map((item) => buildNavLink(item, isItemActive(item)))
      .join('');

    adminNavHtml = `
      <div class="pt-6 pb-2 px-6 text-[10px] font-bold text-red-400/40 uppercase tracking-widest">Administração</div>
      ${adminLinks}
    `;
  }

  const settingsNav = SETTINGS_ITEMS
    .map((item) => buildNavLink(item, isItemActive(item)))
    .join('');

  const urlBaseSupabase = (window.SB_URL || "https://yplpxzmwtkencrrtxmof.supabase.co") + "/storage/v1/object/public/fotos/";
  const userPhoto = (user && user.foto && user.foto !== 'default.png')
    ? urlBaseSupabase + user.foto
    : null;

  const userInitial = user ? user.nome.charAt(0).toUpperCase() : 'U';
  const avatarHtml = userPhoto
    ? `<img src="${userPhoto}" class="w-full h-full object-cover">`
    : `<div class="w-full h-full flex items-center justify-center bg-orange/20 text-orange font-bold text-xs">${userInitial}</div>`;

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

    <nav class="flex-1 py-4 overflow-y-auto custom-scroll">
      <div class="pb-2 px-6 text-[10px] font-bold text-white/30 uppercase tracking-widest">Explorar</div>
      ${mainNav}
      
      <div class="pt-6 pb-2 px-6 text-[10px] font-bold text-white/30 uppercase tracking-widest">Minha Conta</div>
      ${clientNav}
      
      ${proNavHtml}
      ${adminNavHtml}

      <div class="pt-6 pb-2 px-6 text-[10px] font-bold text-white/30 uppercase tracking-widest">Configurações</div>
      ${settingsNav}
    </nav>

    <div class="mt-auto p-4 border-t border-white/5 bg-black/20">
      <div class="flex items-center justify-between gap-2">
        <a href="/perfil" class="flex items-center gap-3 flex-1 min-w-0 group">
          <div class="w-10 h-10 rounded-full overflow-hidden border-2 border-white/10 group-hover:border-orange transition-all flex-shrink-0">
            ${avatarHtml}
          </div>
          <div class="min-w-0">
            <div class="text-xs font-bold text-white truncate group-hover:text-orange transition-colors">
              ${user ? user.nome : 'Usuário'}
            </div>
            <div class="text-[10px] text-white/40 font-medium">Meu Perfil</div>
          </div>
        </a>
        
        <a href="/logout" 
           class="w-9 h-9 flex items-center justify-center rounded-xl text-white/40 hover:text-red-400 hover:bg-red-400/10 transition-all"
           title="Sair da Conta">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
          </svg>
        </a>
      </div>
    </div>
  `;

  if (!window.toggleSidebar) {
    window.toggleSidebar = function () {
      const sidebar = document.getElementById(containerId);
      let overlay = document.getElementById('sidebar-overlay');

      if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'sidebar-overlay';
        overlay.className = 'fixed inset-0 bg-black/50 z-40 hidden md:hidden backdrop-blur-sm transition-opacity duration-300 opacity-0';
        overlay.onclick = window.toggleSidebar;
        document.body.appendChild(overlay);
      }

      const isClosed = sidebar.classList.contains('-translate-x-full');
      if (isClosed) {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        void overlay.offsetWidth;
        overlay.classList.remove('opacity-0');
        overlay.classList.add('opacity-100');
      } else {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.remove('opacity-100');
        overlay.classList.add('opacity-0');
        setTimeout(() => overlay.classList.add('hidden'), 300);
      }
    };
  }

  window.updateSidebarBadge = function (newCount) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const chatLink = container.querySelector('a[href="/chat"]');
    if (!chatLink) return;
    const oldBadge = chatLink.querySelector('.sidebar-msg-badge');
    if (oldBadge) oldBadge.remove();
    if (newCount > 0) {
      const badge = document.createElement('span');
      badge.className = 'sidebar-msg-badge ml-auto bg-red-500 text-white text-[10px] font-black px-2 py-0.5 rounded-full animate-pulse';
      badge.textContent = newCount;
      chatLink.appendChild(badge);
    }
  };

  const API_UNREAD = '/backend/controllers/RoteadorChat.php?acao=unread_count';
  async function _pollUnreadCount() {
    try {
      const resp = await fetch(API_UNREAD);
      if (!resp.ok) return;
      const data = await resp.json();
      if (typeof data.total === 'number') {
        window.updateSidebarBadge(data.total);
      }
    } catch (e) { /* silencioso */ }
  }
  setTimeout(_pollUnreadCount, 500);
  setInterval(_pollUnreadCount, 3000);
}
