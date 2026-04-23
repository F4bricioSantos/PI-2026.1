<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ReformAí – Meu Perfil</title>
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
    renderSidebar('sidebar-container', 'perfil');
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
        <span class="text-gray-800 font-bold text-lg tracking-tight">Meu Perfil</span>
      </div>
      <div class="flex items-center gap-4">
        <button aria-label="Notificações" class="relative text-gray-400 hover:text-gray-700 transition-colors p-2 rounded-xl hover:bg-gray-100">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
          <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-orange rounded-full"></span>
        </button>
        <div class="w-9 h-9 rounded-full bg-orange/80 flex-shrink-0 cursor-pointer hover:opacity-90 transition-opacity"></div>
      </div>
    </header>

    <!-- Scrollable content -->
    <div class="flex-1 overflow-y-auto px-8 py-8 custom-scroll">
      
      <!-- Título da Seção -->
      <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Meu Perfil</h1>
        <p class="text-gray-500 mt-1">Gerencie suas informações de conta e preferências</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 max-w-6xl">
        
        <!-- COLUNA ESQUERDA -->
        <div class="lg:col-span-4 space-y-6">
          
          <!-- Card de Identificação -->
          <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8 text-center">
            <div class="w-24 h-24 bg-gray-100 rounded-full mx-auto mb-4 flex items-center justify-center relative">
               <svg class="w-12 h-12 text-gray-300" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
               <button class="absolute bottom-0 right-0 w-8 h-8 bg-orange text-white rounded-full border-4 border-white flex items-center justify-center hover:scale-110 transition-transform">
                 <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
               </button>
            </div>
            <h2 class="text-xl font-extrabold text-slate-900">João Silva</h2>
            <span class="inline-block mt-1 px-3 py-1 bg-orange/10 text-orange text-[10px] font-bold uppercase tracking-wider rounded-lg">Conta Profissional</span>
            <p class="text-gray-400 text-xs mt-3 italic">Membro desde Outubro, 2026</p>

            <!-- Tabela de Status -->
            <div class="mt-8 border border-blue-50 rounded-2xl overflow-hidden">
              <div class="grid grid-cols-2 border-b border-blue-50">
                <div class="p-3 text-left bg-blue-50/30 text-[11px] font-bold text-gray-500 uppercase">Especialidade</div>
                <div class="p-3 text-right text-xs font-bold text-slate-700">Pedreiro Master</div>
              </div>
              <div class="grid grid-cols-2 border-b border-blue-50">
                <div class="p-3 text-left bg-blue-50/30 text-[11px] font-bold text-gray-500 uppercase">Experiência</div>
                <div class="p-3 text-right text-xs font-bold text-slate-700">12 anos</div>
              </div>
              <div class="grid grid-cols-2">
                <div class="p-3 text-left bg-blue-50/30 text-[11px] font-bold text-gray-500 uppercase">Status</div>
                <div class="p-3 text-right text-xs font-bold text-emerald-500 flex items-center justify-end gap-1.5">
                  <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                  Disponível
                </div>
              </div>
            </div>
          </div>

          <!-- Alerta de Dados Sensíveis -->
          <div class="bg-orange-50 border border-orange-100 rounded-2xl p-5 flex gap-4">
            <div class="w-10 h-10 bg-white rounded-full flex-shrink-0 flex items-center justify-center text-orange shadow-sm">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            </div>
            <div>
              <h4 class="text-sm font-bold text-orange-900">Dados Sensíveis</h4>
              <p class="text-xs text-orange-800/70 mt-1 leading-relaxed">Sua senha e CPF nunca são exibidos publicamente. Mantenha seus dados atualizados para garantir a segurança da conta.</p>
            </div>
          </div>

        </div>

        <!-- COLUNA DIREITA (FORMULÁRIO) -->
        <div class="lg:col-span-8">
          <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8 h-full">
            <div class="flex items-center gap-3 mb-8">
              <div class="p-2 bg-orange/10 rounded-lg text-orange">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </div>
              <h2 class="text-xl font-bold text-slate-900">Editar Informações</h2>
            </div>

            <form class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nome -->
                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Nome Completo</label>
                  <input type="text" value="João Silva" class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-orange transition-colors font-medium">
                </div>
                <!-- Cidade/UF -->
                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Cidade / UF</label>
                  <input type="text" value="Juazeiro do Norte - CE" class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-orange transition-colors font-medium">
                </div>
                <!-- Telefone -->
                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Telefone</label>
                  <input type="text" value="(88) 98765-4321" class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-orange transition-colors font-medium">
                </div>
                <!-- E-mail -->
                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">E-mail (Privado)</label>
                  <input type="email" value="joao.silva@email.com" class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-orange transition-colors font-medium">
                </div>
              </div>

              <!-- Bio -->
              <div class="space-y-1.5">
                <label class="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Bio Profissional</label>
                <textarea rows="4" class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-orange transition-colors font-medium resize-none">Especialista em reformas residenciais com foco em alvenaria e acabamentos. Compromisso com prazos e qualidade.</textarea>
              </div>

              <!-- Botões de Ação -->
              <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-50 mt-8">
                <button type="button" class="px-6 py-2.5 text-sm font-bold text-gray-400 hover:text-gray-600 transition-colors">Cancelar</button>
                <button type="submit" class="bg-orange hover:bg-orange-600 text-white px-8 py-3 rounded-xl font-bold text-sm shadow-lg shadow-orange/20 transition-all hover:scale-[1.02] active:scale-95">Salvar Alterações</button>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>
  </main>

</body>
</html>
