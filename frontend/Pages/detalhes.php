<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ReformAí – Detalhes do Serviço</title>
  <meta name="description" content="Veja os detalhes completos do serviço, portfólio do profissional e entre em contato pelo ReformAí." />
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Manrope', 'sans-serif'] },
          colors: {
            orange:  { DEFAULT: '#F97316', light: '#FFEDD5', dark: '#EA580C' },
            sidebar: '#16213E',
            card:    '#1E2A3A',
            bg:      '#F8F9FA',
          }
        }
      }
    }
  </script>
  <style>
    /* Scrollbar premium */
    .custom-scroll::-webkit-scrollbar { width: 6px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 99px; }
    .custom-scroll::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

    /* Hover scale suave para galeria */
    .gallery-item img,
    .gallery-item > div { transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .gallery-item:hover img,
    .gallery-item:hover > div { transform: scale(1.03); box-shadow: 0 8px 30px rgba(0,0,0,0.12); }

    /* Pulse sutil no badge verificado */
    @keyframes subtle-pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.7; }
    }
    .verified-dot { animation: subtle-pulse 2s ease-in-out infinite; }
  </style>
</head>
<body class="font-sans bg-bg text-gray-800 flex h-screen overflow-hidden">

  <!-- ══════════════ SIDEBAR ══════════════ -->
  <div id="sidebar-container" class="w-60 bg-sidebar flex-shrink-0 h-screen"></div>
  <script type="module">
    import { renderSidebar } from '../src/components/sidebar.js';
    renderSidebar('sidebar-container', 'detalhes');
  </script>

  <!-- ══════════════ MAIN ══════════════ -->
  <main class="flex-1 flex flex-col overflow-hidden">

    <!-- Top bar -->
    <header class="flex items-center justify-between px-8 py-5 border-b border-gray-200 bg-white flex-shrink-0">
      <div class="flex items-center gap-2 text-gray-400">
        <button id="btn-voltar" onclick="history.back()" aria-label="Voltar" class="hover:text-gray-600 transition-colors p-1 -ml-1 rounded-lg hover:bg-gray-100">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <a href="./dashboard.php" class="text-gray-400 text-sm hover:text-orange transition-colors">Início</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        <span class="text-gray-800 font-bold text-lg tracking-tight">Detalhes do Serviço</span>
      </div>
      <div class="flex items-center gap-4">
        <button id="btn-notificacoes" aria-label="Notificações" class="relative text-gray-400 hover:text-gray-700 transition-colors p-2 rounded-xl hover:bg-gray-100">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
          <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-orange rounded-full"></span>
        </button>
        <div class="w-9 h-9 rounded-full bg-orange/80 flex-shrink-0 cursor-pointer hover:opacity-90 transition-opacity"></div>
      </div>
    </header>

    <!-- Scrollable content -->
    <div class="flex-1 overflow-y-auto px-8 py-6 custom-scroll">
      <div class="flex gap-7 max-w-6xl">

        <!-- ── Coluna esquerda ── -->
        <div class="flex-1 min-w-0 space-y-6">

          <!-- Imagem principal com overlay -->
          <div class="w-full h-80 bg-gradient-to-br from-orange-100 via-slate-200 to-slate-300 rounded-2xl overflow-hidden relative group cursor-pointer">
            <div class="absolute inset-0 flex items-center justify-center">
              <svg class="w-16 h-16 text-slate-400/60" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            </div>
            <!-- Overlay gradient no hover -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            <!-- Badge flutuante -->
            <span class="absolute top-4 left-4 bg-orange text-white text-[10px] font-bold px-3 py-1.5 rounded-lg uppercase tracking-wide shadow-lg">
              Reforma Residencial
            </span>
            <!-- Botão expandir -->
            <button aria-label="Expandir imagem" class="absolute bottom-4 right-4 w-10 h-10 bg-white/90 backdrop-blur-sm rounded-xl flex items-center justify-center text-gray-600 opacity-0 group-hover:opacity-100 transition-all duration-300 hover:bg-white shadow-lg">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
            </button>
          </div>

          <!-- Título + Preço + Avaliação -->
          <div class="space-y-3">
            <div class="flex items-start justify-between gap-4">
              <h1 class="text-2xl font-extrabold text-gray-900 leading-tight">
                Pintura e Acabamento Premium de Interiores
              </h1>
              <div class="text-right flex-shrink-0">
                <p class="text-2xl font-extrabold text-orange leading-tight">R$ 85,00</p>
                <p class="text-xs text-gray-400 mt-0.5">por m² estimado</p>
              </div>
            </div>
            <!-- Rating bar -->
            <div class="flex items-center gap-3 flex-wrap">
              <div class="flex items-center gap-1.5 bg-orange/10 px-3 py-1.5 rounded-lg">
                <svg class="w-4 h-4 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                <span class="text-sm font-bold text-orange">4.9</span>
                <span class="text-xs text-gray-500">(124 avaliações)</span>
              </div>
              <div class="flex items-center gap-1.5 text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="text-sm">Juazeiro do Norte, CE</span>
              </div>
            </div>
          </div>

          <!-- Highlights rápidos -->
          <div class="grid grid-cols-3 gap-3">
            <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm text-center hover:shadow-md transition-shadow">
              <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center mx-auto mb-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
              </div>
              <p class="text-xs font-bold text-gray-800">Verificado</p>
              <p class="text-[11px] text-gray-400 mt-0.5">Identidade confirmada</p>
            </div>
            <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm text-center hover:shadow-md transition-shadow">
              <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center mx-auto mb-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              </div>
              <p class="text-xs font-bold text-gray-800">10+ anos</p>
              <p class="text-[11px] text-gray-400 mt-0.5">de experiência</p>
            </div>
            <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm text-center hover:shadow-md transition-shadow">
              <div class="w-10 h-10 bg-orange/10 rounded-xl flex items-center justify-center mx-auto mb-2">
                <svg class="w-5 h-5 text-orange" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              </div>
              <p class="text-xs font-bold text-gray-800">< 1 hora</p>
              <p class="text-[11px] text-gray-400 mt-0.5">tempo de resposta</p>
            </div>
          </div>

          <!-- Sobre o serviço -->
          <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
              <div class="w-1 h-5 bg-orange rounded-full"></div>
              <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wide">Sobre o serviço</h2>
            </div>
            <p class="text-gray-600 text-sm leading-relaxed mb-3">
              Especialista em acabamentos finos com mais de 10 anos de experiência no mercado de reformas residenciais.
              Oferecemos serviços completos de preparação de superfície, aplicação de massa corrida, lixamento sem pó e
              pintura com tintas de alta qualidade.
            </p>
            <p class="text-gray-600 text-sm leading-relaxed mb-5">
              Garantimos limpeza total pós-obra e cumprimento rigoroso dos prazos estabelecidos. Atendemos projetos de
              pequeno a grande porte com o mesmo nível de excelência e atenção aos detalhes.
            </p>

            <!-- Tags de serviços inclusos -->
            <div class="flex flex-wrap gap-2">
              <span class="px-3 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded-lg">Preparação de superfície</span>
              <span class="px-3 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded-lg">Massa corrida</span>
              <span class="px-3 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded-lg">Lixamento sem pó</span>
              <span class="px-3 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded-lg">Pintura premium</span>
              <span class="px-3 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded-lg">Limpeza pós-obra</span>
            </div>
          </div>

          <!-- Portfólio -->
          <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-2">
                <div class="w-1 h-5 bg-orange rounded-full"></div>
                <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wide">Portfólio de Projetos</h2>
              </div>
              <button class="text-xs text-orange font-semibold hover:underline">Ver todos →</button>
            </div>
            <div class="grid grid-cols-3 gap-3">
              <div class="gallery-item h-40 bg-gradient-to-br from-orange-50 to-slate-200 rounded-xl overflow-hidden relative flex items-center justify-center cursor-pointer">
                <svg class="w-8 h-8 text-slate-400/60" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              </div>
              <div class="gallery-item h-40 bg-gradient-to-br from-slate-100 to-slate-200 rounded-xl overflow-hidden relative flex items-center justify-center cursor-pointer">
                <svg class="w-8 h-8 text-slate-400/60" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              </div>
              <div class="gallery-item h-40 bg-gradient-to-br from-amber-50 to-slate-200 rounded-xl overflow-hidden relative flex items-center justify-center cursor-pointer">
                <svg class="w-8 h-8 text-slate-400/60" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              </div>
            </div>
          </div>

          <!-- Avaliações de clientes -->
          <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between mb-5">
              <div class="flex items-center gap-2">
                <div class="w-1 h-5 bg-orange rounded-full"></div>
                <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wide">Avaliações</h2>
              </div>
              <span class="text-xs text-gray-400">124 avaliações</span>
            </div>

            <div class="space-y-4">
              <!-- Review 1 -->
              <div class="flex gap-3.5 p-4 rounded-xl bg-gray-50/70 border border-gray-100 hover:border-gray-200 transition-colors">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-xs font-bold text-blue-600 flex-shrink-0">MC</div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-bold text-gray-800">Maria Clara</p>
                    <span class="text-[11px] text-gray-400">há 3 dias</span>
                  </div>
                  <div class="flex items-center gap-0.5 mt-1 mb-2">
                    <svg class="w-3.5 h-3.5 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <svg class="w-3.5 h-3.5 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <svg class="w-3.5 h-3.5 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <svg class="w-3.5 h-3.5 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <svg class="w-3.5 h-3.5 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                  </div>
                  <p class="text-xs text-gray-500 leading-relaxed">Trabalho impecável! Ficou melhor do que eu imaginava. Muito profissional e pontual. Recomendo demais.</p>
                </div>
              </div>

              <!-- Review 2 -->
              <div class="flex gap-3.5 p-4 rounded-xl bg-gray-50/70 border border-gray-100 hover:border-gray-200 transition-colors">
                <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-xs font-bold text-emerald-600 flex-shrink-0">JF</div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-bold text-gray-800">José Ferreira</p>
                    <span class="text-[11px] text-gray-400">há 1 semana</span>
                  </div>
                  <div class="flex items-center gap-0.5 mt-1 mb-2">
                    <svg class="w-3.5 h-3.5 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <svg class="w-3.5 h-3.5 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <svg class="w-3.5 h-3.5 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <svg class="w-3.5 h-3.5 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <svg class="w-3.5 h-3.5 fill-gray-300" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                  </div>
                  <p class="text-xs text-gray-500 leading-relaxed">Ótimo acabamento e cumpriu o prazo combinado. Apenas um pequeno detalhe na janela que foi corrigido rapidamente.</p>
                </div>
              </div>

              <!-- Review 3 -->
              <div class="flex gap-3.5 p-4 rounded-xl bg-gray-50/70 border border-gray-100 hover:border-gray-200 transition-colors">
                <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-xs font-bold text-purple-600 flex-shrink-0">AL</div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-bold text-gray-800">Ana Luiza</p>
                    <span class="text-[11px] text-gray-400">há 2 semanas</span>
                  </div>
                  <div class="flex items-center gap-0.5 mt-1 mb-2">
                    <svg class="w-3.5 h-3.5 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <svg class="w-3.5 h-3.5 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <svg class="w-3.5 h-3.5 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <svg class="w-3.5 h-3.5 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <svg class="w-3.5 h-3.5 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                  </div>
                  <p class="text-xs text-gray-500 leading-relaxed">Excelente profissional! Minha sala ficou completamente transformada. Super cuidadoso com os móveis e limpeza.</p>
                </div>
              </div>
            </div>

            <button class="w-full mt-4 py-2.5 text-sm font-semibold text-orange border border-orange/20 rounded-xl hover:bg-orange/5 transition-colors">
              Ver todas as avaliações
            </button>
          </div>

        </div>

        <!-- ── Coluna direita (card prestador) ── -->
        <div class="w-80 flex-shrink-0">
          <div class="sticky top-0 space-y-4">

          <!-- Card do Prestador -->
          <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

            <!-- Avatar + Nome + Status -->
            <div class="p-5 border-b border-gray-100">
              <div class="flex items-center gap-3.5">
                <div class="relative">
                  <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange/20 to-orange/5 flex items-center justify-center text-orange font-bold text-lg flex-shrink-0">
                    RS
                  </div>
                  <!-- Indicador online -->
                  <div class="verified-dot absolute -bottom-0.5 -right-0.5 w-4 h-4 bg-emerald-400 rounded-full border-2 border-white"></div>
                </div>
                <div class="min-w-0">
                  <div class="flex items-center gap-1.5">
                    <p class="font-bold text-gray-900 text-sm truncate">Ricardo Silva</p>
                    <!-- Selo verificado -->
                    <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                  </div>
                  <p class="text-xs text-gray-400 flex items-center gap-1 mt-0.5">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Juazeiro do Norte, CE
                  </p>
                </div>
              </div>

              <!-- Mini stats -->
              <div class="flex items-center gap-2 mt-4">
                <div class="flex-1 text-center bg-gray-50 rounded-xl py-2">
                  <p class="text-sm font-extrabold text-gray-800">87</p>
                  <p class="text-[10px] text-gray-400">Projetos</p>
                </div>
                <div class="flex-1 text-center bg-gray-50 rounded-xl py-2">
                  <p class="text-sm font-extrabold text-orange">4.9</p>
                  <p class="text-[10px] text-gray-400">Avaliação</p>
                </div>
                <div class="flex-1 text-center bg-gray-50 rounded-xl py-2">
                  <p class="text-sm font-extrabold text-gray-800">98%</p>
                  <p class="text-[10px] text-gray-400">Aprovação</p>
                </div>
              </div>
            </div>

            <!-- Informações de contato -->
            <div class="px-5 py-4 space-y-2.5">
              <div class="flex items-center gap-3 bg-gray-50 rounded-xl px-4 py-3 border border-gray-100">
                <svg class="w-4 h-4 text-orange flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                <span class="text-sm text-gray-700 font-medium">(88) 98765-4321</span>
              </div>
              <div class="flex items-center gap-3 bg-gray-50 rounded-xl px-4 py-3 border border-gray-100">
                <svg class="w-4 h-4 text-orange flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <span class="text-sm text-gray-700 font-medium">ricardo@email.com</span>
              </div>
            </div>

            <!-- Botões de ação -->
            <div class="px-5 pb-5 space-y-2.5">
              <button id="btn-contato" class="w-full bg-orange hover:bg-orange-600 active:scale-[0.98] text-white font-semibold py-2.5 rounded-xl flex items-center justify-center gap-2 text-xs transition-all duration-200 shadow-md shadow-orange/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                Entrar em contato
              </button>

              <button id="btn-avaliar" class="w-full border-2 border-gray-200 hover:border-orange hover:text-orange active:scale-[0.98] text-gray-700 font-bold py-3 rounded-xl flex items-center justify-center gap-2 text-sm transition-all duration-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                Avaliar prestador
              </button>

              <p class="text-center text-[11px] text-gray-400 pt-1 flex items-center justify-center gap-1">
                <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full"></span>
                Online agora • Responde em menos de 1 hora
              </p>
            </div>
          </div>

          <!-- Ações rápidas -->
          <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex justify-around">
            <button id="btn-compartilhar" aria-label="Compartilhar serviço" class="flex flex-col items-center gap-1.5 text-gray-400 hover:text-orange transition-colors group">
              <div class="w-10 h-10 rounded-xl bg-gray-100 group-hover:bg-orange/10 flex items-center justify-center transition-all duration-200 group-hover:scale-105">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
              </div>
              <span class="text-[10px] font-semibold">Compartilhar</span>
            </button>
            <button id="btn-favoritar" aria-label="Favoritar serviço" class="flex flex-col items-center gap-1.5 text-gray-400 hover:text-rose-500 transition-colors group">
              <div class="w-10 h-10 rounded-xl bg-gray-100 group-hover:bg-rose-50 flex items-center justify-center transition-all duration-200 group-hover:scale-105">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
              </div>
              <span class="text-[10px] font-semibold">Favoritar</span>
            </button>
            <button id="btn-denunciar" aria-label="Denunciar serviço" class="flex flex-col items-center gap-1.5 text-gray-400 hover:text-red-500 transition-colors group">
              <div class="w-10 h-10 rounded-xl bg-gray-100 group-hover:bg-red-50 flex items-center justify-center transition-all duration-200 group-hover:scale-105">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
              </div>
              <span class="text-[10px] font-semibold">Denunciar</span>
            </button>
          </div>

          </div><!-- /sticky wrapper -->
        </div>
      </div>
    </div>
  </main>

</body>
</html>