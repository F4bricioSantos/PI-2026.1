<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ReformAí</title>
    
    <!-- Importing Inter font for a standard, clean look (Premium feel) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="./src/assets/output.css" />
  </head>
  <body class="font-sans text-gray-900 bg-white antialiased min-h-screen flex flex-col items-center">
    
    <div id="app" class="w-full max-w-[1440px] flex flex-col px-4 sm:px-6 md:px-8 lg:px-12">
      
      <!-- Navbar -->
      <nav id="navbar" class="sticky top-0 z-50 w-full flex items-center justify-between py-4 md:py-5 px-4 mb-4 border-b border-gray-100/50 bg-white/80 backdrop-blur-md transition-all duration-300">
        <a href="#" class="text-2xl font-bold text-orange-500 hover:opacity-80 transition-opacity">ReformAí</a>
        
        <!-- Desktop Menu -->
        <div class="hidden md:flex space-x-8 font-medium text-gray-500 text-[15px]">
          <a href="#inicio" class="nav-link hover:text-slate-900 transition-all border-b-2 border-transparent">Início</a>
          <a href="#como-funciona" class="nav-link hover:text-slate-900 transition-all border-b-2 border-transparent">Como funciona</a>
          <a href="#categorias" class="nav-link hover:text-slate-900 transition-all border-b-2 border-transparent">Categorias</a>
          <a href="#avaliacoes" class="nav-link hover:text-slate-900 transition-all border-b-2 border-transparent">Avaliações</a>
        </div>
        
        <!-- Action Buttons -->
        <div class="hidden md:flex items-center space-x-6 text-[15px]">
          <a href="#" class="font-medium text-gray-500 hover:text-gray-900 transition-colors">Entrar</a>
          <a href="#" class="px-5 py-2.5 bg-orange-500 text-white font-medium rounded-lg hover:bg-orange-600 transition-colors shadow-sm focus:outline-none focus:ring-4 focus:ring-orange-100">
            Cadastre-se grátis
          </a>
        </div>

        <!-- Mobile Menu Button (Hamburger) - Usually hidden on desktop -->
        <button class="md:hidden text-gray-600 hover:text-gray-900 focus:outline-none">
          <i data-lucide="menu" class="w-6 h-6"></i>
        </button>
      </nav>

      <!-- Main Content Area -->
      <main class="flex-1 flex flex-col items-center mt-6 md:mt-12 w-full">
        
        <!-- Hero Section -->
        <section id="inicio" class="relative w-full max-w-[900px] text-center px-4 flex flex-col items-center pt-16 -mt-16">
          
          <!-- Premium SaaS Background Glows -->
          <div class="absolute top-[-10%] left-1/2 -translate-x-1/2 w-full max-w-[800px] h-[400px] bg-orange-400/15 rounded-full blur-[100px] -z-10 pointer-events-none"></div>
          <div class="absolute top-[20%] left-[20%] w-[300px] h-[300px] bg-yellow-400/10 rounded-full blur-[80px] -z-10 pointer-events-none"></div>
          
          <!-- Animated Badge -->
          <div class="mb-8 inline-flex items-center gap-2.5 px-4 py-2 rounded-full border border-orange-500/20 bg-orange-50/50 backdrop-blur-sm text-orange-600 text-[13px] md:text-[14px] font-semibold shadow-[0_4px_12px_-4px_rgba(249,115,22,0.15)] hover:shadow-[0_4px_16px_-4px_rgba(249,115,22,0.3)] transition-all cursor-default relative overflow-hidden group">
            <div class="absolute inset-0 bg-gradient-to-r from-orange-100/0 via-orange-100/80 to-orange-100/0 opacity-0 group-hover:opacity-100 group-hover:translate-x-full transition-all duration-700 w-1/2 blur-sm"></div>
            <span class="flex h-2.5 w-2.5 relative items-center justify-center">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2 w-2 bg-orange-500"></span>
            </span>
            A plataforma <span class="bg-orange-100 px-1.5 py-0.5 rounded text-orange-700">nº 1</span> em reformas seguras
          </div>

          <!-- Main Title -->
          <h1 class="text-[44px] sm:text-[56px] md:text-[72px] font-extrabold text-slate-900 tracking-tight leading-[1.05] drop-shadow-sm">
            O profissional <br class="hidden sm:block" /> 
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#FF7A00] via-[#FA541C] to-[#E53E3E] inline-block animate-gradient relative">
              certo para o seu lar.
              <!-- Subtle bottom line highlight -->
              <svg class="absolute w-full h-[12px] -bottom-2 left-0 text-orange-400/30" viewBox="0 0 100 12" preserveAspectRatio="none">
                <path d="M0,8 C25,2 75,2 100,8 L100,12 L0,12 Z" fill="currentColor"></path>
              </svg>
            </span>
          </h1>
          
          <!-- Subtitle -->
          <p class="mt-8 text-[18px] sm:text-[20px] md:text-[22px] text-gray-500 font-medium max-w-[660px] mx-auto leading-relaxed">
            Encontre serviços de confiança ou ofereça os seus numa rede com mais de <strong class="text-slate-800">10.000 profissionais</strong> qualificados. Simples assim.
          </p>

        </section>

        <!-- Action Cards Container -->
        <div class="mt-10 sm:mt-14 w-full flex flex-col md:flex-row items-center justify-center gap-4 sm:gap-6 px-4">
          
          <!-- Card 1: Seek professionals -->
          <div class="bg-white/80 backdrop-blur-md rounded-[24px] p-6 w-full md:w-[420px] shadow-[0_8px_30px_-12px_rgba(0,0,0,0.06)] border border-gray-100/80 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col items-start relative overflow-hidden group">
            <!-- Subtle Hover BG -->
            <div class="absolute right-0 top-0 w-32 h-32 bg-orange-100/40 rounded-full blur-[40px] -z-10 group-hover:bg-orange-200/60 transition-colors"></div>

            <div class="flex items-center gap-4 mb-5 w-full">
              <div class="w-[50px] h-[50px] rounded-[16px] bg-orange-50/80 text-orange-500 flex items-center justify-center shrink-0 shadow-sm border border-orange-100/50">
                <i data-lucide="search" class="w-6 h-6"></i>
              </div>
              <div class="text-left">
                <h3 class="text-[17px] font-bold text-slate-900 leading-tight">Preciso de um profissional</h3>
                <p class="text-[13px] text-gray-500 mt-0.5">Encontre especialistas avaliados.</p>
              </div>
            </div>
            
            <button class="w-full py-[12px] bg-[#111827] text-white text-[14px] font-semibold rounded-[12px] hover:bg-slate-800 transition-colors shadow-sm focus:outline-none focus:ring-4 focus:ring-slate-200 flex items-center justify-center gap-2">
              Buscar serviços
              <i data-lucide="arrow-right" class="w-4 h-4 text-orange-400 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all"></i>
            </button>
          </div>

          <!-- Card 2: Offer services -->
          <div class="bg-white/80 backdrop-blur-md rounded-[24px] p-6 w-full md:w-[420px] shadow-[0_8px_30px_-12px_rgba(0,0,0,0.06)] border border-gray-100/80 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col items-start relative overflow-hidden group">
            <!-- Subtle Hover BG -->
            <div class="absolute right-0 top-0 w-32 h-32 bg-gray-100/50 rounded-full blur-[40px] -z-10 group-hover:bg-orange-100/40 transition-colors"></div>

            <div class="flex items-center gap-4 mb-5 w-full">
              <div class="w-[50px] h-[50px] rounded-[16px] bg-slate-50 text-slate-500 flex items-center justify-center shrink-0 shadow-sm border border-slate-200/50">
                <i data-lucide="briefcase" class="w-6 h-6"></i>
              </div>
              <div class="text-left">
                <h3 class="text-[17px] font-bold text-slate-900 leading-tight">Quero oferecer serviços</h3>
                <p class="text-[13px] text-gray-500 mt-0.5">Aumente sua renda diária.</p>
              </div>
            </div>
            
            <button class="w-full py-[12px] bg-orange-500 text-white text-[14px] font-semibold rounded-[12px] hover:bg-orange-600 transition-colors shadow-sm focus:outline-none focus:ring-4 focus:ring-orange-100 flex items-center justify-center gap-2">
              Me cadastrar
              <i data-lucide="arrow-right" class="w-4 h-4 text-white opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all"></i>
            </button>
          </div>

        </div>

        <!-- Stats Section -->
        <div class="mt-20 sm:mt-24 mb-20 w-full flex flex-wrap items-center justify-center gap-10 sm:gap-16 md:gap-[120px]">
          
          <div class="text-center">
            <p class="text-[28px] font-bold text-gray-500 leading-none mb-1.5">+10k</p>
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.15em] whitespace-nowrap">
              Profissionais
            </p>
          </div>
          
          <div class="text-center">
            <p class="text-[28px] font-bold text-gray-500 leading-none mb-1.5">4.9/5</p>
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.15em] whitespace-nowrap">
              Avaliação Média
            </p>
          </div>
          
          <div class="text-center">
            <p class="text-[28px] font-bold text-gray-500 leading-none mb-1.5">+50k</p>
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.15em] whitespace-nowrap">
              Reformas Feitas
            </p>
          </div>

        </div>

        <!-- Como Funciona Section -->
        <section id="como-funciona" class="relative max-w-[1000px] w-full mb-28 px-4 flex flex-col items-center pt-24 -mt-24">
          
          <!-- Background Glow -->
          <div class="absolute top-1/4 left-0 w-64 h-64 bg-orange-500/10 rounded-full blur-[80px] -z-10 pointer-events-none"></div>

          <div class="mb-4 inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-50 text-orange-600 text-[13px] font-semibold border border-orange-100/50">
            Simples e Rápido
          </div>
          <h2 class="text-[32px] md:text-[44px] font-extrabold text-slate-900 tracking-tight mb-12 text-center">
            Veja como a plataforma <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#FF7A00] to-[#E53E3E] inline-block">funciona</span>
          </h2>
          
          <!-- Tabs -->
          <div class="flex justify-center border-b border-gray-200 mb-12 w-full max-w-[400px]">
            <button id="tab-cliente" class="flex-1 text-center py-4 text-[15px] font-medium text-orange-500 border-b-2 border-orange-500 transition-colors">
              Sou cliente
            </button>
            <button id="tab-prestador" class="flex-1 text-center py-4 text-[15px] font-medium text-slate-400 hover:text-slate-900 border-b-2 border-transparent transition-colors">
              Sou prestador
            </button>
          </div>
          
          <!-- Steps Content: Cliente -->
          <div id="content-cliente" class="w-full grid grid-cols-1 md:grid-cols-3 gap-12 text-center relative animate-fade-in">
            
            <!-- Step 1 -->
            <div class="flex flex-col items-center z-10 px-2">
              <div class="w-14 h-14 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center text-xl font-bold mb-6">
                1
              </div>
              <h4 class="text-[17px] font-bold text-slate-900 mb-3">Descreva o serviço</h4>
              <p class="text-[14px] text-gray-500 leading-relaxed max-w-[250px]">
                Diga o que você precisa e quando precisa.
              </p>
            </div>
            
            <!-- Step 2 -->
            <div class="flex flex-col items-center z-10 px-2">
              <div class="w-14 h-14 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center text-xl font-bold mb-6">
                2
              </div>
              <h4 class="text-[17px] font-bold text-slate-900 mb-3">Receba orçamentos</h4>
              <p class="text-[14px] text-gray-500 leading-relaxed max-w-[250px]">
                Até 4 profissionais qualificados entrarão em contato.
              </p>
            </div>
            
            <!-- Step 3 -->
            <div class="flex flex-col items-center z-10 px-2">
              <div class="w-14 h-14 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center text-xl font-bold mb-6">
                3
              </div>
              <h4 class="text-[17px] font-bold text-slate-900 mb-3">Escolha e avalie</h4>
              <p class="text-[14px] text-gray-500 leading-relaxed max-w-[250px]">
                Contrate o melhor custo-benefício e avalie o serviço.
              </p>
            </div>
          </div>

          <!-- Steps Content: Prestador -->
          <div id="content-prestador" class="hidden w-full grid grid-cols-1 md:grid-cols-3 gap-12 text-center relative animate-fade-in">
            
            <!-- Step 1 -->
            <div class="flex flex-col items-center z-10 px-2">
              <div class="w-14 h-14 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center mb-6">
                <i data-lucide="image-plus" class="w-6 h-6"></i>
              </div>
              <h4 class="text-[17px] font-bold text-slate-900 mb-3">Crie seu Portfólio</h4>
              <p class="text-[14px] text-gray-500 leading-relaxed max-w-[250px]">
                Inclua fotos de serviços prestados e mostre suas especialidades para encantar os clientes.
              </p>
            </div>
            
            <!-- Step 2 -->
            <div class="flex flex-col items-center z-10 px-2">
              <div class="w-14 h-14 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center mb-6">
                <i data-lucide="calculator" class="w-6 h-6"></i>
              </div>
              <h4 class="text-[17px] font-bold text-slate-900 mb-3">Orçamentos Base</h4>
              <p class="text-[14px] text-gray-500 leading-relaxed max-w-[250px]">
                Defina tabelas de preços e estimativas em projetos bases para gerar confiança automática.
              </p>
            </div>
            
            <!-- Step 3 -->
            <div class="flex flex-col items-center z-10 px-2">
              <div class="w-14 h-14 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center mb-6">
                <i data-lucide="wallet" class="w-6 h-6"></i>
              </div>
              <h4 class="text-[17px] font-bold text-slate-900 mb-3">Feche Negócios</h4>
              <p class="text-[14px] text-gray-500 leading-relaxed max-w-[250px]">
                Receba mensagens qualificadas no seu painel e negocie diretamente os termos do serviço.
              </p>
            </div>
          </div>
        </section>

        <!-- Principais Categorias -->
        <section id="categorias" class="relative w-full bg-white/60 backdrop-blur-xl rounded-[40px] p-8 md:p-12 mb-28 border border-white/80 shadow-[0_8px_40px_-12px_rgba(0,0,0,0.04)] pt-24 -mt-16 overflow-hidden">
          
          <!-- Background Glow -->
          <div class="absolute top-0 right-0 w-[400px] h-[400px] bg-orange-400/10 rounded-full blur-[100px] -z-10 pointer-events-none"></div>
          <div class="absolute bottom-0 left-0 w-[300px] h-[300px] bg-orange-400/10 rounded-full blur-[100px] -z-10 pointer-events-none"></div>

          <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-12 gap-4 relative z-10">
            <div>
              <div class="mb-4 inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100/80 text-slate-600 text-[13px] font-semibold border border-slate-200/50">
                Catálogo Completo
              </div>
              <h2 class="text-[32px] md:text-[44px] font-extrabold text-slate-900 tracking-tight mb-2">Principais <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-500 to-amber-500">Categorias</span></h2>
              <p class="text-[16px] text-gray-500">Os serviços mais procurados da nossa rede.</p>
            </div>
            <a href="#" class="text-[14px] font-medium text-orange-500 hover:text-orange-600 transition-colors flex items-center gap-1 group">
              Ver todas <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
            </a>
          </div>

          <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
            <!-- Categoria 1 -->
            <a href="#" class="bg-white p-6 rounded-[24px] flex flex-col items-center justify-center gap-4 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.02)] hover:shadow-xl hover:-translate-y-1 transition-all duration-300 border border-transparent hover:border-gray-100 group">
              <div class="text-gray-400 group-hover:text-amber-500 transition-colors">
                <i data-lucide="zap" class="w-7 h-7"></i>
              </div>
              <span class="text-[14px] font-medium text-slate-700 group-hover:text-slate-900 transition-colors">Elétrica</span>
            </a>
            
            <!-- Categoria 2 -->
            <a href="#" class="bg-white p-6 rounded-[24px] flex flex-col items-center justify-center gap-4 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.02)] hover:shadow-xl hover:-translate-y-1 transition-all duration-300 border border-transparent hover:border-gray-100 group">
              <div class="text-gray-400 group-hover:text-orange-500 transition-colors">
                <i data-lucide="droplets" class="w-7 h-7"></i>
              </div>
              <span class="text-[14px] font-medium text-slate-700 group-hover:text-slate-900 transition-colors">Hidráulica</span>
            </a>
            
            <!-- Categoria 3 -->
            <a href="#" class="bg-white p-6 rounded-[24px] flex flex-col items-center justify-center gap-4 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.02)] hover:shadow-xl hover:-translate-y-1 transition-all duration-300 border border-transparent hover:border-gray-100 group">
              <div class="text-gray-400 group-hover:text-orange-600 transition-colors">
                <i data-lucide="paint-roller" class="w-7 h-7"></i>
              </div>
              <span class="text-[14px] font-medium text-slate-700 group-hover:text-slate-900 transition-colors">Pintura</span>
            </a>

            <!-- Categoria 4 -->
            <a href="#" class="bg-white p-6 rounded-[24px] flex flex-col items-center justify-center gap-4 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.02)] hover:shadow-xl hover:-translate-y-1 transition-all duration-300 border border-transparent hover:border-gray-100 group">
              <div class="text-gray-400 group-hover:text-orange-500 transition-colors">
                <i data-lucide="hammer" class="w-7 h-7"></i>
              </div>
              <span class="text-[14px] font-medium text-slate-700 group-hover:text-slate-900 transition-colors">Reformas</span>
            </a>

            <!-- Categoria 5 -->
            <a href="#" class="bg-white p-6 rounded-[24px] flex flex-col items-center justify-center gap-4 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.02)] hover:shadow-xl hover:-translate-y-1 transition-all duration-300 border border-transparent hover:border-gray-100 group">
              <div class="text-gray-400 group-hover:text-orange-400 transition-colors">
                <i data-lucide="snowflake" class="w-7 h-7"></i>
              </div>
              <span class="text-[14px] font-medium text-slate-700 group-hover:text-slate-900 transition-colors">Climatização</span>
            </a>

            <!-- Categoria 6 -->
            <a href="#" class="bg-white p-6 rounded-[24px] flex flex-col items-center justify-center gap-4 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.02)] hover:shadow-xl hover:-translate-y-1 transition-all duration-300 border border-transparent hover:border-gray-100 group">
              <div class="text-gray-400 group-hover:text-amber-500 transition-colors">
                <i data-lucide="package" class="w-7 h-7"></i>
              </div>
              <span class="text-[14px] font-medium text-slate-700 group-hover:text-slate-900 transition-colors">Montagem</span>
            </a>
          </div>
        </section>

        <!-- O que dizem sobre nós -->
        <section id="avaliacoes" class="relative w-full mb-32 flex flex-col items-center pt-24 -mt-24">
          
          <!-- Background Glow -->
          <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[400px] bg-amber-400/10 rounded-full blur-[120px] -z-10 pointer-events-none"></div>

          <div class="mb-4 inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 text-amber-600 text-[13px] font-semibold border border-amber-100/50">
            Experiências Reais
          </div>
          <h2 class="text-[32px] md:text-[44px] font-extrabold text-slate-900 tracking-tight mb-16 text-center">
            O que dizem <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-amber-500">sobre nós</span>
          </h2>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full">
            
            <!-- Depoimento 1 -->
            <article class="bg-white/80 backdrop-blur-sm p-8 rounded-[28px] border border-gray-100 shadow-[0_8px_30px_-12px_rgba(0,0,0,0.06)] hover:shadow-xl transition-all duration-300 flex flex-col h-full hover:-translate-y-1">
              <div class="flex gap-[2px] text-orange-400 mb-6">
                <!-- 5 stars -->
                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
              </div>
              <p class="text-[15px] text-gray-600 leading-relaxed mb-8 flex-1">
                "Encontrei um eletricista em menos de 1 hora. O serviço foi impecável e o preço justo. Recomendo muito!"
              </p>
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center font-bold text-sm">
                  RC
                </div>
                <div>
                  <p class="text-[14px] font-bold text-slate-900">Ricardo Costa</p>
                  <p class="text-[12px] text-gray-400">Cliente desde 2023</p>
                </div>
              </div>
            </article>

            <!-- Depoimento 2 -->
            <article class="bg-white/80 backdrop-blur-sm p-8 rounded-[28px] border border-gray-100 shadow-[0_8px_30px_-12px_rgba(0,0,0,0.06)] hover:shadow-xl transition-all duration-300 flex flex-col h-full hover:-translate-y-1">
              <div class="flex gap-[2px] text-orange-400 mb-6">
                <!-- 5 stars -->
                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
              </div>
              <p class="text-[15px] text-gray-600 leading-relaxed mb-8 flex-1">
                "Plataforma muito organizada. Consegui comparar os orçamentos e escolher o que mais me passou segurança."
              </p>
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center font-bold text-sm">
                  AM
                </div>
                <div>
                  <p class="text-[14px] font-bold text-slate-900">Ana Martins</p>
                  <p class="text-[12px] text-gray-400">Cliente desde 2024</p>
                </div>
              </div>
            </article>

            <!-- Depoimento 3 -->
            <article class="bg-white/80 backdrop-blur-sm p-8 rounded-[28px] border border-gray-100 shadow-[0_8px_30px_-12px_rgba(0,0,0,0.06)] hover:shadow-xl transition-all duration-300 flex flex-col h-full hover:-translate-y-1">
              <div class="flex gap-[2px] text-orange-400 mb-6">
                <!-- 5 stars -->
                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
              </div>
              <p class="text-[15px] text-gray-600 leading-relaxed mb-8 flex-1">
                "Sou pintor e minha agenda lotou depois que entrei no ReformAí. O suporte deles é excelente."
              </p>
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-orange-100 text-orange-700 flex items-center justify-center font-bold text-sm">
                  JL
                </div>
                <div>
                  <p class="text-[14px] font-bold text-slate-900">Jorge Lima</p>
                  <p class="text-[12px] text-gray-400">Prestador verificado</p>
                </div>
              </div>
            </article>

          </div>
        </section>

        <!-- CTA Banner -->
        <section class="w-full bg-gradient-to-br from-[#FF8C22] to-[#E95A0C] rounded-[32px] p-10 md:p-14 mb-24 flex flex-col md:flex-row items-center justify-between relative overflow-hidden shadow-[0_20px_40px_-15px_rgba(234,88,12,0.4)]">
          <!-- Decorative Background Patterns -->
          <div class="absolute -top-32 -right-32 w-80 h-80 bg-white opacity-[0.08] rounded-full blur-3xl pointer-events-none"></div>
          <div class="absolute -bottom-32 -left-32 w-80 h-80 bg-orange-900 opacity-20 rounded-full blur-3xl pointer-events-none"></div>
          
          <div class="z-10 text-center md:text-left mb-8 md:mb-0 max-w-[600px] flex-1">
            <h2 class="text-[28px] md:text-[32px] font-bold text-white mb-3 tracking-tight">
              Você é profissional de reformas?
            </h2>
            <p class="text-[15px] md:text-[16px] text-orange-50 font-medium leading-relaxed max-w-[480px] mx-auto md:mx-0">
              Aumente sua clientela e receba pedidos de serviço todos os dias diretamente no seu celular.
            </p>
          </div>

          <div class="z-10 w-full md:w-auto">
            <button class="w-full md:w-auto bg-white text-orange-600 font-bold text-[15px] px-8 py-4 rounded-2xl shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-white/30">
              Quero me cadastrar
            </button>
          </div>
        </section>

      </main>
      
    </div>

    <!-- Footer -->
    <footer class="w-full bg-[#0a0f1c] pt-20 pb-8 flex flex-col items-center">
      <div class="w-full max-w-[1440px] px-4 sm:px-6 md:px-8 lg:px-12 flex flex-col">
        
        <!-- Top Section (Columns) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 lg:gap-8 mb-16 px-2">
          
          <!-- Column 1: Brand -->
          <div class="flex flex-col">
            <a href="#" class="text-[24px] font-bold text-orange-500 mb-6 hover:opacity-80 transition-opacity">ReformAí</a>
            <p class="text-[14px] leading-[1.8] text-slate-400 max-w-[280px]">
              Conectando você aos melhores profissionais de reforma do Brasil de forma rápida e segura.
            </p>
          </div>
          
          <!-- Column 2: Plataforma -->
          <div class="flex flex-col md:mt-2">
            <h4 class="text-white font-bold text-[15px] mb-6">Plataforma</h4>
            <div class="flex flex-col space-y-4">
              <a href="#" class="text-[14px] text-slate-400 hover:text-white transition-colors w-fit">Como funciona</a>
              <a href="#" class="text-[14px] text-slate-400 hover:text-white transition-colors w-fit">Categorias</a>
              <a href="#" class="text-[14px] text-slate-400 hover:text-white transition-colors w-fit">Preços</a>
            </div>
          </div>
          
          <!-- Column 3: Suporte -->
          <div class="flex flex-col md:mt-2">
            <h4 class="text-white font-bold text-[15px] mb-6">Suporte</h4>
            <div class="flex flex-col space-y-4">
              <a href="#" class="text-[14px] text-slate-400 hover:text-white transition-colors w-fit">Central de Ajuda</a>
              <a href="#" class="text-[14px] text-slate-400 hover:text-white transition-colors w-fit">Termos de Uso</a>
              <a href="#" class="text-[14px] text-slate-400 hover:text-white transition-colors w-fit">Privacidade</a>
            </div>
          </div>
          
          <!-- Column 4: Redes Sociais -->
          <div class="flex flex-col md:mt-2">
            <h4 class="text-white font-bold text-[15px] mb-6">Redes Sociais</h4>
            <div class="flex gap-3">
              <a href="#" aria-label="Instagram" class="w-10 h-10 rounded-full border border-slate-700/50 flex items-center justify-center text-slate-400 hover:text-white hover:bg-slate-800 hover:border-slate-600 transition-all duration-300">
                <i data-lucide="instagram" class="w-4 h-4"></i>
              </a>
            </div>
          </div>

        </div>

        <!-- Bottom Line & Copyright -->
        <div class="border-t border-slate-800/60 pt-8 flex justify-center items-center">
          <p class="text-[13px] text-slate-500 text-center">
            © 2024 ReformAí Marketplace de Serviços. Todos os direitos reservados.
          </p>
        </div>

      </div>
    </footer>
    
    <script type="module" src="./src/js/main.js"></script>
  </body>
</html>
