<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ReformAí – Avaliar Prestador</title>
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
    .custom-scroll::-webkit-scrollbar { width: 6px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 99px; }
  </style>
</head>
<body class="font-sans bg-bg text-gray-800 flex h-screen overflow-hidden">

  <!-- ══════════════ SIDEBAR ══════════════ -->
  <div id="sidebar-container" class="w-60 bg-sidebar flex-shrink-0 h-screen"></div>
  <script type="module">
    import { renderSidebar } from '../src/components/sidebar.js';
    renderSidebar('sidebar-container', 'avaliar');
  </script>

  <!-- ══════════════ MAIN ══════════════ -->
  <main class="flex-1 flex flex-col overflow-hidden">

    <!-- Top bar -->
    <header class="flex items-center justify-between px-8 py-5 border-b border-gray-200 bg-white flex-shrink-0">
      <div class="flex items-center gap-2 text-gray-400">
        <button onclick="history.back()" aria-label="Voltar" class="hover:text-gray-600 transition-colors p-1 -ml-1 rounded-lg hover:bg-gray-100">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <a href="./dashboard.php" class="text-gray-400 text-sm hover:text-orange transition-colors">Início</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        <span class="text-gray-800 font-bold text-lg tracking-tight">Avaliar Prestador</span>
      </div>
      <div class="flex items-center gap-4">
        <button aria-label="Notificações" class="text-gray-400 hover:text-gray-700 transition-colors p-2 rounded-xl hover:bg-gray-100">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
        </button>
      </div>
    </header>

    <!-- Scrollable content -->
    <div class="flex-1 overflow-y-auto px-8 py-10 custom-scroll flex flex-col items-center">
      
      <div class="w-full max-w-2xl">
        
        <!-- Card do Prestador -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-12 flex items-center gap-5">
          <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center text-slate-300">
            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
          </div>
          <div>
            <span class="text-[10px] font-bold text-orange uppercase tracking-wider block mb-0.5">Prestador de Serviço</span>
            <h2 class="text-xl font-extrabold text-slate-900">Ricardo Silva</h2>
            <p class="text-slate-400 text-sm">Eletricista Residencial</p>
          </div>
        </div>

        <!-- Área de Avaliação -->
        <div class="text-center space-y-8">
          <div>
            <h3 class="text-slate-900 font-bold text-lg mb-4">Como foi sua experiência?</h3>
            <div class="flex items-center justify-center gap-2 mb-2">
              <svg class="w-10 h-10 text-orange fill-orange cursor-pointer" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
              <svg class="w-10 h-10 text-orange fill-orange cursor-pointer" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
              <svg class="w-10 h-10 text-orange fill-orange cursor-pointer" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
              <svg class="w-10 h-10 text-orange fill-orange cursor-pointer" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
              <svg class="w-10 h-10 text-slate-200 fill-slate-200 cursor-pointer" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            </div>
            <p class="text-orange font-bold text-sm">Muito bom</p>
          </div>

          <!-- Comentário -->
          <div class="text-left space-y-2">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Comentário (Opcional)</label>
            <textarea rows="5" placeholder="Conte-nos mais sobre o serviço prestado..." 
              class="w-full bg-white border border-gray-100 rounded-2xl px-6 py-4 text-sm focus:outline-none focus:border-orange focus:ring-4 focus:ring-orange/5 transition-all shadow-sm resize-none"></textarea>
          </div>

          <!-- Botões -->
          <div class="space-y-4 pt-4">
            <button class="w-full bg-orange hover:bg-orange-600 text-white py-4 rounded-2xl font-bold text-base shadow-lg shadow-orange/20 transition-all hover:scale-[1.01] active:scale-95 flex items-center justify-center gap-3">
              Enviar avaliação
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14m-7-7l7 7-7 7"/></svg>
            </button>
            <button class="w-full bg-slate-50 hover:bg-slate-100 text-slate-600 py-4 rounded-2xl font-bold text-sm transition-colors">
              Voltar para meus pedidos
            </button>
          </div>
        </div>

        <!-- Média do Profissional -->
        <div class="mt-20 pt-10 border-t border-gray-100">
          <div class="flex items-center justify-between mb-6">
            <h4 class="text-sm font-bold text-slate-900">Média do profissional</h4>
            <div class="flex items-center gap-1.5 text-orange font-extrabold">
              <span>4.8</span>
              <svg class="w-4 h-4 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            </div>
          </div>

          <!-- Barras de Estatística -->
          <div class="space-y-3">
            <div class="flex items-center gap-4">
              <span class="text-[10px] font-bold text-slate-400 w-4">5</span>
              <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-orange rounded-full w-[85%]"></div>
              </div>
            </div>
            <div class="flex items-center gap-4">
              <span class="text-[10px] font-bold text-slate-400 w-4">4</span>
              <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-orange rounded-full w-[10%]"></div>
              </div>
            </div>
            <div class="flex items-center gap-4">
              <span class="text-[10px] font-bold text-slate-400 w-4">3</span>
              <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-orange rounded-full w-[5%]"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <footer class="mt-16 py-8 text-center">
          <p class="text-[11px] text-gray-400 font-medium tracking-wide">
            © 2024 ReformAí - Conectando você aos melhores profissionais.
          </p>
        </footer>

      </div>

    </div>
  </main>

</body>
</html>
