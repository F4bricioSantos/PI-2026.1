<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ReformAi – Início</title>
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
</head>
<body class="font-sans bg-bg text-gray-800 flex h-screen overflow-hidden">

  <!-- ══════════════ SIDEBAR ══════════════ -->
  <div id="sidebar-container" class="w-60 bg-sidebar flex-shrink-0 h-screen"></div>
  <script type="module">
    import { renderSidebar } from '../src/components/sidebar.js';
    renderSidebar('sidebar-container', 'inicio');
  </script>

  <!-- ══════════════ MAIN ══════════════ -->
  <main class="flex-1 flex flex-col overflow-hidden">

    <!-- Top bar -->
    <header class="flex items-center justify-between px-8 py-5 border-b border-gray-200 bg-white flex-shrink-0">
      <div class="flex items-center gap-2 text-gray-800">
        <div class="w-8 h-8 bg-orange/10 rounded-lg flex items-center justify-center text-orange">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        </div>
        <span class="font-bold text-lg tracking-tight">Início</span>
      </div>
      <div class="flex items-center gap-4">
        <button class="relative text-gray-400 hover:text-gray-700 transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
          <span class="absolute -top-1 -right-1 w-2 h-2 bg-orange rounded-full"></span>
        </button>
        <div class="w-9 h-9 rounded-full bg-orange/80 flex-shrink-0 cursor-pointer hover:opacity-90 transition-opacity"></div>
      </div>
    </header>

    <!-- Scrollable content -->
    <div class="flex-1 overflow-y-auto px-8 py-6">

      <!-- Search -->
      <div class="relative mb-5">
        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <input
          type="text"
          placeholder="Buscar por serviços (ex: encanador, pintor, eletricista)..."
          class="w-full bg-white border border-gray-200 rounded-2xl pl-11 pr-5 py-3.5 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-orange transition-colors shadow-sm"
        />
      </div>

      <!-- Filter chips -->
      <div class="flex items-center gap-2 flex-wrap mb-7">
        <button class="px-5 py-1.5 rounded-full bg-orange text-white text-sm font-semibold">Todos</button>
        <button class="px-5 py-1.5 rounded-full border border-gray-300 text-gray-500 text-sm font-medium hover:border-orange hover:text-orange transition-all">Reformas</button>
        <button class="px-5 py-1.5 rounded-full border border-gray-300 text-gray-500 text-sm font-medium hover:border-orange hover:text-orange transition-all">Elétrica</button>
        <button class="px-5 py-1.5 rounded-full border border-gray-300 text-gray-500 text-sm font-medium hover:border-orange hover:text-orange transition-all">Pintura</button>
        <button class="px-5 py-1.5 rounded-full border border-gray-300 text-gray-500 text-sm font-medium hover:border-orange hover:text-orange transition-all">Hidráulica</button>
        <button class="px-5 py-1.5 rounded-full border border-gray-300 text-gray-500 text-sm font-medium hover:border-orange hover:text-orange transition-all">Jardinagem</button>
        <button class="px-5 py-1.5 rounded-full border border-gray-300 text-gray-500 text-sm font-medium hover:border-orange hover:text-orange transition-all">Limpeza</button>
        <button class="px-5 py-1.5 rounded-full border border-gray-300 text-gray-500 text-sm font-medium hover:border-orange hover:text-orange transition-all">Marcenaria</button>
      </div>

      <!-- Cards Grid -->
      <div class="grid grid-cols-3 gap-5">

        <!-- Card 1 -->
        <div class="bg-white rounded-2xl overflow-hidden flex flex-col shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200">
          <div class="relative">
            <img src="https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&q=80&w=600" alt="Reforma de Cozinha" class="w-full h-44 object-cover" />
            <span class="absolute top-3 left-3 bg-orange text-white text-[10px] font-bold px-2.5 py-1 rounded-md uppercase tracking-wide">Reforma</span>
          </div>
          <div class="p-5 flex flex-col gap-2 flex-1">
            <div class="flex items-start justify-between gap-2">
              <h3 class="font-bold text-gray-900 text-base leading-snug">Reforma de Cozinha Completa</h3>
              <span class="text-orange font-extrabold text-base whitespace-nowrap">R$ 1.500</span>
            </div>
            <p class="text-gray-400 text-xs">João Silva • São Paulo, SP</p>
            <div class="flex items-center justify-between mt-auto pt-3 border-t border-gray-100">
              <div class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                <span class="text-xs font-semibold text-gray-700">4.8</span>
                <span class="text-xs text-gray-400">(12 avaliações)</span>
              </div>
              <a href="detalhes.php" class="text-orange text-xs font-bold hover:underline">Ver detalhes</a>
            </div>
          </div>
        </div>

        <!-- Card 2 -->
        <div class="bg-white rounded-2xl overflow-hidden flex flex-col shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200">
          <div class="relative">
            <img src="https://images.unsplash.com/photo-1621905251189-08b45d6a269e?auto=format&fit=crop&q=80&w=600" alt="Elétrica" class="w-full h-44 object-cover" />
            <span class="absolute top-3 left-3 bg-orange text-white text-[10px] font-bold px-2.5 py-1 rounded-md uppercase tracking-wide">Elétrica</span>
          </div>
          <div class="p-5 flex flex-col gap-2 flex-1">
            <div class="flex items-start justify-between gap-2">
              <h3 class="font-bold text-gray-900 text-base leading-snug">Instalação e Reparo Elétrico</h3>
              <span class="text-orange font-extrabold text-base whitespace-nowrap">R$ 200</span>
            </div>
            <p class="text-gray-400 text-xs">Carlos Souza • Curitiba, PR</p>
            <div class="flex items-center justify-between mt-auto pt-3 border-t border-gray-100">
              <div class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                <span class="text-xs font-semibold text-gray-700">4.9</span>
                <span class="text-xs text-gray-400">(45 avaliações)</span>
              </div>
              <a href="detalhes.php" class="text-orange text-xs font-bold hover:underline">Ver detalhes</a>
            </div>
          </div>
        </div>

        <!-- Card 3 -->
        <div class="bg-white rounded-2xl overflow-hidden flex flex-col shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200">
          <div class="relative">
            <img src="https://images.unsplash.com/photo-1589939705384-5185137a7f0f?auto=format&fit=crop&q=80&w=600" alt="Pintura" class="w-full h-44 object-cover" />
            <span class="absolute top-3 left-3 bg-orange text-white text-[10px] font-bold px-2.5 py-1 rounded-md uppercase tracking-wide">Pintura</span>
          </div>
          <div class="p-5 flex flex-col gap-2 flex-1">
            <div class="flex items-start justify-between gap-2">
              <h3 class="font-bold text-gray-900 text-base leading-snug">Pintura Interna e Externa</h3>
              <span class="text-orange font-extrabold text-base whitespace-nowrap">R$ 800</span>
            </div>
            <p class="text-gray-400 text-xs">Ana Costa • Rio de Janeiro, RJ</p>
            <div class="flex items-center justify-between mt-auto pt-3 border-t border-gray-100">
              <div class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                <span class="text-xs font-semibold text-gray-700">4.7</span>
                <span class="text-xs text-gray-400">(32 avaliações)</span>
              </div>
              <a href="detalhes.php" class="text-orange text-xs font-bold hover:underline">Ver detalhes</a>
            </div>
          </div>
        </div>

        <!-- Card 4 -->
        <div class="bg-white rounded-2xl overflow-hidden flex flex-col shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200">
          <div class="relative">
            <img src="https://images.unsplash.com/photo-1504148455328-436306343aa1?auto=format&fit=crop&q=80&w=600" alt="Hidráulica" class="w-full h-44 object-cover" />
            <span class="absolute top-3 left-3 bg-orange text-white text-[10px] font-bold px-2.5 py-1 rounded-md uppercase tracking-wide">Hidráulica</span>
          </div>
          <div class="p-5 flex flex-col gap-2 flex-1">
            <div class="flex items-start justify-between gap-2">
              <h3 class="font-bold text-gray-900 text-base leading-snug">Manutenção de Tubulação</h3>
              <span class="text-orange font-extrabold text-base whitespace-nowrap">R$ 150</span>
            </div>
            <p class="text-gray-400 text-xs">Marcos Lima • Belo Horizonte, MG</p>
            <div class="flex items-center justify-between mt-auto pt-3 border-t border-gray-100">
              <div class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                <span class="text-xs font-semibold text-gray-700">4.5</span>
                <span class="text-xs text-gray-400">(18 avaliações)</span>
              </div>
              <a href="detalhes.php" class="text-orange text-xs font-bold hover:underline">Ver detalhes</a>
            </div>
          </div>
        </div>

        <!-- Card 5 -->
        <div class="bg-white rounded-2xl overflow-hidden flex flex-col shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200">
          <div class="relative">
            <img src="https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&q=80&w=600" alt="Design de Interiores" class="w-full h-44 object-cover" />
            <span class="absolute top-3 left-3 bg-orange text-white text-[10px] font-bold px-2.5 py-1 rounded-md uppercase tracking-wide">Design</span>
          </div>
          <div class="p-5 flex flex-col gap-2 flex-1">
            <div class="flex items-start justify-between gap-2">
              <h3 class="font-bold text-gray-900 text-base leading-snug">Projeto de Interiores</h3>
              <span class="text-orange font-extrabold text-base whitespace-nowrap">R$ 2.000</span>
            </div>
            <p class="text-gray-400 text-xs">Juliana Reis • Porto Alegre, RS</p>
            <div class="flex items-center justify-between mt-auto pt-3 border-t border-gray-100">
              <div class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                <span class="text-xs font-semibold text-gray-700">5.0</span>
                <span class="text-xs text-gray-400">(58 avaliações)</span>
              </div>
              <a href="detalhes.php" class="text-orange text-xs font-bold hover:underline">Ver detalhes</a>
            </div>
          </div>
        </div>

        <!-- Card 6 -->
        <div class="bg-white rounded-2xl overflow-hidden flex flex-col shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200">
          <div class="relative">
            <img src="https://images.unsplash.com/photo-1632759162352-f117d873d630?auto=format&fit=crop&q=80&w=600" alt="Telhado" class="w-full h-44 object-cover" />
            <span class="absolute top-3 left-3 bg-orange text-white text-[10px] font-bold px-2.5 py-1 rounded-md uppercase tracking-wide">Telhado</span>
          </div>
          <div class="p-5 flex flex-col gap-2 flex-1">
            <div class="flex items-start justify-between gap-2">
              <h3 class="font-bold text-gray-900 text-base leading-snug">Reparo de Telhado e Calhas</h3>
              <span class="text-orange font-extrabold text-base whitespace-nowrap">R$ 1.200</span>
            </div>
            <p class="text-gray-400 text-xs">Ricardo Dias • Salvador, BA</p>
            <div class="flex items-center justify-between mt-auto pt-3 border-t border-gray-100">
              <div class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                <span class="text-xs font-semibold text-gray-700">4.6</span>
                <span class="text-xs text-gray-400">(21 avaliações)</span>
              </div>
              <a href="detalhes.php" class="text-orange text-xs font-bold hover:underline">Ver detalhes</a>
            </div>
          </div>
        </div>

      </div><!-- /grid -->
    </div><!-- /scroll -->
  </main>

  <!-- FAB -->
  <button class="fixed bottom-7 right-7 w-13 h-13 w-[52px] h-[52px] bg-orange text-white rounded-full flex items-center justify-center shadow-lg hover:opacity-90 transition-opacity z-50">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
  </button>

  <script>
    // Filter chip active state
    document.querySelectorAll('.flex.items-center.gap-2 button').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.flex.items-center.gap-2 button').forEach(b => {
          b.classList.remove('bg-orange', 'text-white');
          b.classList.add('border', 'border-white/20', 'text-white/60');
        });
        btn.classList.add('bg-orange', 'text-white');
        btn.classList.remove('border', 'border-white/20', 'text-white/60');
      });
    });
  </script>
</body>
</html>