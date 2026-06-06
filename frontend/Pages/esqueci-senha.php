<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Esqueci minha senha — ReformAí</title>
  <meta name="description" content="Redefina sua senha do ReformAí de forma rápida e segura." />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../src/assets/output.css" />
  <style>
    body { font-family: 'Inter', sans-serif; }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      20%       { transform: translateX(-5px); }
      40%       { transform: translateX(5px); }
      60%       { transform: translateX(-4px); }
      80%       { transform: translateX(4px); }
    }
    .input-error { animation: shake 0.4s ease; }

    @keyframes fadeSlideIn {
      from { opacity: 0; transform: translateY(10px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .step { animation: fadeSlideIn 0.3s ease forwards; }

    .btn-primary:active { transform: scale(0.98); }

    /* Progress dots */
    .dot { transition: all 0.3s ease; }
    .dot.active { background-color: #f97316; transform: scale(1.2); }
    .dot.done   { background-color: #f97316; opacity: 0.4; }
    .dot.idle   { background-color: #e2e8f0; }
  </style>
</head>

<body class="min-h-screen bg-[#f0f0f0] flex items-center justify-center px-4 py-12">

  <div class="w-full max-w-[440px]">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-6 py-8 sm:px-10 sm:py-10">
      <div class="mb-6 text-center">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-orange-50 mb-4">
          <svg class="w-7 h-7 text-orange-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
          </svg>
        </div>
        <h1 class="text-[26px] font-bold text-slate-900 leading-tight">Esqueci minha senha</h1>
        <p id="subtitulo" class="mt-1.5 text-[14px] text-gray-400">Informe seu e-mail para receber o código de redefinição.</p>
      </div>
      <div class="flex items-center justify-center gap-2 mb-7">
        <div id="dot-1" class="dot w-2.5 h-2.5 rounded-full active" title="Etapa 1: E-mail"></div>
        <div class="w-8 h-px bg-gray-200"></div>
        <div id="dot-2" class="dot w-2.5 h-2.5 rounded-full idle" title="Etapa 2: Código"></div>
        <div class="w-8 h-px bg-gray-200"></div>
        <div id="dot-3" class="dot w-2.5 h-2.5 rounded-full idle" title="Etapa 3: Nova senha"></div>
      </div>
      <div id="etapa-1" class="step flex flex-col gap-5">
        <div class="flex flex-col gap-1.5">
          <label for="email" class="text-[13px] font-medium text-slate-700">E-mail cadastrado</label>
          <div class="relative">
            <input id="email" name="email" type="email"
              placeholder="email@exemplo.com" autocomplete="email"
              class="w-full h-[48px] px-4 pr-11 rounded-lg border border-gray-200 bg-white text-[14px] text-slate-800 placeholder-gray-300
                     focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-all duration-200" />
          </div>
          <p id="email-error" class="hidden text-[12px] text-orange-500 mt-0.5">Insira um e-mail válido.</p>
        </div>
        <button id="btn-enviar-codigo" type="button"
          class="btn-primary w-full h-[52px] bg-orange-500 hover:bg-orange-600 text-white text-[15px] font-semibold rounded-lg
                 shadow-sm focus:outline-none focus:ring-4 focus:ring-orange-200 transition-all duration-200 flex items-center justify-center gap-2">
          <span id="btn-enviar-texto">Enviar código</span>
          <svg id="btn-enviar-spinner" class="hidden w-5 h-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
        </button>

        <p id="global-error-1" class="hidden text-[13px] text-orange-600 text-center font-medium"></p>
      </div>
      <div id="etapa-2" class="hidden flex-col gap-5">

        <div class="bg-orange-50 border border-orange-100 rounded-xl p-4 text-[13px] text-orange-700 text-center leading-relaxed">
          Enviamos um código de 6 dígitos para<br>
          <strong id="email-enviado-label" class="font-semibold"></strong>
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="codigo" class="text-[13px] font-medium text-slate-700">Código de verificação</label>
          <input id="codigo" type="text" placeholder="000000" maxlength="6" autocomplete="one-time-code"
            class="w-full h-[56px] text-center tracking-[0.5em] font-mono text-xl rounded-lg border border-gray-200
                   focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-all" />
          <p id="codigo-error" class="hidden text-[12px] text-orange-500 text-center mt-0.5">Código inválido ou expirado.</p>
        </div>

        <button id="btn-verificar-codigo" type="button" class="btn-primary w-full h-[52px] bg-orange-500 hover:bg-orange-600 text-white text-[15px] font-semibold rounded-lg shadow-sm focus:outline-none focus:ring-4 focus:ring-orange-200 transition-all duration-200 flex items-center justify-center gap-2">
          <span id="btn-verificar-texto">Verificar código</span>
          <svg id="btn-verificar-spinner" class="hidden w-5 h-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
        </button>

        <button id="btn-reenviar" type="button" class="text-[13px] text-orange-500 font-medium hover:text-orange-600 hover:underline transition-colors text-center w-full cursor-pointer">
          Reenviar código
        </button>
      </div>
      <div id="etapa-3" class="hidden flex-col gap-5">

        <div class="flex flex-col gap-1.5">
          <label for="nova-senha" class="text-[13px] font-medium text-slate-700">Nova senha</label>
          <div class="relative">
            <input id="nova-senha" type="password" placeholder="Mínimo 8 caracteres" autocomplete="new-password"
              class="w-full h-[48px] px-4 pr-11 rounded-lg border border-gray-200 bg-white text-[14px] text-slate-800 placeholder-gray-300
                     focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-all duration-200" />
            <button type="button" id="toggle-nova-senha" aria-label="Mostrar senha"
              class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors focus:outline-none">
              <svg id="eye-nova" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                   stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
          <p id="nova-senha-error" class="hidden text-[12px] text-orange-500 mt-0.5">A senha deve ter pelo menos 8 caracteres.</p>
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="confirmar-nova-senha" class="text-[13px] font-medium text-slate-700">Confirmar nova senha</label>
          <div class="relative">
            <input id="confirmar-nova-senha" type="password" placeholder="Repita a nova senha" autocomplete="new-password"
              class="w-full h-[48px] px-4 pr-11 rounded-lg border border-gray-200 bg-white text-[14px] text-slate-800 placeholder-gray-300
                     focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-all duration-200" />
            <button type="button" id="toggle-confirmar-nova" aria-label="Mostrar confirmação"
              class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors focus:outline-none">
              <svg id="eye-confirmar" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                   stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
          <p id="confirmar-nova-error" class="hidden text-[12px] text-orange-500 mt-0.5">As senhas não coincidem.</p>
        </div>
        <button id="btn-redefinir" type="button" class="btn-primary w-full h-[52px] bg-orange-500 hover:bg-orange-600 text-white text-[15px] font-semibold rounded-lg shadow-sm focus:outline-none focus:ring-4 focus:ring-orange-200 transition-all duration-200 flex items-center justify-center gap-2">
          <span id="btn-redefinir-texto">Redefinir senha</span>
          <svg id="btn-redefinir-spinner" class="hidden w-5 h-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
        </button>
        <p id="global-error-3" class="hidden text-[13px] text-orange-600 text-center font-medium"></p>
      </div>
      <div id="etapa-sucesso" class="hidden flex-col items-center gap-5 text-center">
        <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">
          <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
          </svg>
        </div>
        <div>
          <h2 class="text-[20px] font-bold text-slate-900">Senha redefinida!</h2>
          <p class="text-[14px] text-gray-400 mt-1">Sua senha foi alterada com sucesso. Faça login para continuar.</p>
        </div>
        <a href="login.php" class="w-full h-[52px] bg-orange-500 hover:bg-orange-600 text-white text-[15px] font-semibold rounded-lg shadow-sm focus:outline-none focus:ring-4 focus:ring-orange-200 transition-all duration-200 flex items-center justify-center">
          Ir para o login
        </a>
      </div>
      <p id="link-login" class="mt-6 text-center text-[13px] text-gray-400">
        Lembrou a senha?
        <a href="login.php" class="text-orange-500 font-medium hover:text-orange-600 hover:underline transition-colors ml-1">Fazer login</a>
      </p>
    </div>
  </div>

  <script>
    const API = '../../backend/controllers/AuthController.php';
    const etapa1          = document.getElementById('etapa-1');
    const etapa2          = document.getElementById('etapa-2');
    const etapa3          = document.getElementById('etapa-3');
    const etapaSucesso    = document.getElementById('etapa-sucesso');
    const linkLogin       = document.getElementById('link-login');
    const subtitulo       = document.getElementById('subtitulo');
    const emailInput      = document.getElementById('email');
    const emailError      = document.getElementById('email-error');
    const globalError1    = document.getElementById('global-error-1');
    const emailEnviadoLbl = document.getElementById('email-enviado-label');
    const codigoInput     = document.getElementById('codigo');
    const codigoError     = document.getElementById('codigo-error');
    const novaSenhaInput  = document.getElementById('nova-senha');
    const novaSenhaError  = document.getElementById('nova-senha-error');
    const confirmarInput  = document.getElementById('confirmar-nova-senha');
    const confirmarError  = document.getElementById('confirmar-nova-error');
    const globalError3    = document.getElementById('global-error-3');
    const dots = [
      document.getElementById('dot-1'),
      document.getElementById('dot-2'),
      document.getElementById('dot-3'),
    ];
    function setInputError(input, errorEl, msg = null) {
      input.classList.add('border-orange-400', 'bg-orange-50', 'input-error');
      input.classList.remove('border-gray-200', 'bg-white');
      errorEl.classList.remove('hidden');
      if (msg) errorEl.textContent = msg;
      setTimeout(() => input.classList.remove('input-error'), 500);
    }
    function clearInputError(input, errorEl) {
      input.classList.remove('border-orange-400', 'bg-orange-50', 'input-error');
      input.classList.add('border-gray-200', 'bg-white');
      errorEl.classList.add('hidden');
    }
    function setSpinner(btnTexto, btnSpinner, btnEl, loading, texto) {
      btnEl.disabled = loading;
      btnTexto.textContent = texto;
      btnSpinner.classList.toggle('hidden', !loading);
    }
    function irParaEtapa(n) {
      [etapa1, etapa2, etapa3, etapaSucesso].forEach((el, i) => {
        const ativo = (i + 1) === n || (n === 4 && i === 3);
        el.classList.toggle('hidden', !ativo);
        el.classList.toggle('flex', ativo);
        el.classList.toggle('step', ativo);
      });
      linkLogin.classList.toggle('hidden', n === 4);
      dots.forEach((dot, i) => {
        dot.className = 'dot w-2.5 h-2.5 rounded-full';
        if (i + 1 < n)       dot.classList.add('done');
        else if (i + 1 === n) dot.classList.add('active');
        else                  dot.classList.add('idle');
      });
      const subtitulos = {
        1: 'Informe seu e-mail para receber o código de redefinição.',
        2: 'Digite o código de 6 dígitos que enviamos.',
        3: 'Crie uma nova senha segura para sua conta.',
        4: '',
      };
      subtitulo.textContent = subtitulos[n] ?? '';
    }
    document.getElementById('toggle-nova-senha').addEventListener('click', () => {
      const isPwd = novaSenhaInput.type === 'password';
      novaSenhaInput.type = isPwd ? 'text' : 'password';
      document.getElementById('eye-nova').style.opacity = isPwd ? '0.5' : '1';
    });
    document.getElementById('toggle-confirmar-nova').addEventListener('click', () => {
      const isPwd = confirmarInput.type === 'password';
      confirmarInput.type = isPwd ? 'text' : 'password';
      document.getElementById('eye-confirmar').style.opacity = isPwd ? '0.5' : '1';
    });
    async function enviarCodigo() {
      const email = emailInput.value.trim();
      if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        setInputError(emailInput, emailError, 'Insira um e-mail válido.');
        return;
      }
      clearInputError(emailInput, emailError);
      globalError1.classList.add('hidden');
      const btnEl      = document.getElementById('btn-enviar-codigo');
      const btnTexto   = document.getElementById('btn-enviar-texto');
      const btnSpinner = document.getElementById('btn-enviar-spinner');
      setSpinner(btnTexto, btnSpinner, btnEl, true, 'Enviando...');
      try {
        const fd = new FormData();
        fd.append('email', email);
        const res  = await fetch(`${API}?action=enviar_codigo_reset`, { method: 'POST', body: fd });
        const text = await res.text();
        const data = JSON.parse(text.trim());
        if (data.sucesso) {
          if (data.token_desenvolvimento) {
            alert(`[MODO DESENVOLVIMENTO] Use o código: ${data.token_desenvolvimento}`);
          }
          emailEnviadoLbl.textContent = email;
          irParaEtapa(2);
          setTimeout(() => codigoInput.focus(), 100);
        } else {
          globalError1.textContent = data.mensagem ?? 'Erro ao enviar. Tente novamente.';
          globalError1.classList.remove('hidden');
        }
      } catch (err) {
        console.error(err);
        globalError1.textContent = 'Erro de comunicação com o servidor.';
        globalError1.classList.remove('hidden');
      } finally {
        setSpinner(btnTexto, btnSpinner, btnEl, false, 'Enviar código');
      }
    }
    async function verificarCodigo() {
      const codigo = codigoInput.value.trim();
      if (codigo.length !== 6) {
        codigoError.textContent = 'O código precisa ter 6 dígitos.';
        codigoError.classList.remove('hidden');
        return;
      }
      codigoError.classList.add('hidden');
      const btnEl      = document.getElementById('btn-verificar-codigo');
      const btnTexto   = document.getElementById('btn-verificar-texto');
      const btnSpinner = document.getElementById('btn-verificar-spinner');
      setSpinner(btnTexto, btnSpinner, btnEl, true, 'Verificando...');
      try {
        const fd = new FormData();
        fd.append('email', emailInput.value.trim());
        fd.append('codigo_token', codigo);
        const res  = await fetch(`${API}?action=verificar_codigo_reset`, { method: 'POST', body: fd });
        const text = await res.text();
        const data = JSON.parse(text.trim());
        if (data.sucesso) {
          irParaEtapa(3);
          setTimeout(() => novaSenhaInput.focus(), 100);
        } else {
          codigoError.textContent = data.mensagem ?? 'Código inválido ou expirado.';
          codigoError.classList.remove('hidden');
        }
      } catch (err) {
        console.error(err);
        codigoError.textContent = 'Erro de comunicação.';
        codigoError.classList.remove('hidden');
      } finally {
        setSpinner(btnTexto, btnSpinner, btnEl, false, 'Verificar código');
      }
    }
    async function redefinirSenha() {
      const nova      = novaSenhaInput.value;
      const confirmar = confirmarInput.value;
      let valido      = true;
      if (nova.length < 8) {
        setInputError(novaSenhaInput, novaSenhaError, 'A senha deve ter pelo menos 8 caracteres.');
        valido = false;
      } else clearInputError(novaSenhaInput, novaSenhaError);
      if (!confirmar || confirmar !== nova) {
        setInputError(confirmarInput, confirmarError, 'As senhas não coincidem.');
        valido = false;
      } else clearInputError(confirmarInput, confirmarError);
      if (!valido) return;
      globalError3.classList.add('hidden');
      const btnEl      = document.getElementById('btn-redefinir');
      const btnTexto   = document.getElementById('btn-redefinir-texto');
      const btnSpinner = document.getElementById('btn-redefinir-spinner');
      setSpinner(btnTexto, btnSpinner, btnEl, true, 'Salvando...');
      try {
        const fd = new FormData();
        fd.append('email',             emailInput.value.trim());
        fd.append('nova_senha',        nova);
        fd.append('confirmar_senha',   confirmar);
        const res  = await fetch(`${API}?action=redefinir_senha`, { method: 'POST', body: fd });
        const text = await res.text();
        const data = JSON.parse(text.trim());
        if (data.sucesso) {
          irParaEtapa(4);
        } else {
          globalError3.textContent = data.mensagem ?? 'Não foi possível redefinir a senha.';
          globalError3.classList.remove('hidden');
        }
      } catch (err) {
        console.error(err);
        globalError3.textContent = 'Erro de comunicação com o servidor.';
        globalError3.classList.remove('hidden');
      } finally {
        setSpinner(btnTexto, btnSpinner, btnEl, false, 'Redefinir senha');
      }
    }
    document.getElementById('btn-enviar-codigo').addEventListener('click', enviarCodigo);
    document.getElementById('btn-verificar-codigo').addEventListener('click', verificarCodigo);
    document.getElementById('btn-redefinir').addEventListener('click', redefinirSenha);
    document.getElementById('btn-reenviar').addEventListener('click', () => {
      codigoInput.value = '';
      codigoError.classList.add('hidden');
      irParaEtapa(1);
      setTimeout(() => emailInput.focus(), 100);
    });
    emailInput.addEventListener('keydown',   e => { if (e.key === 'Enter') enviarCodigo(); });
    codigoInput.addEventListener('keydown',  e => { if (e.key === 'Enter') verificarCodigo(); });
    novaSenhaInput.addEventListener('keydown', e => { if (e.key === 'Enter') redefinirSenha(); });
    confirmarInput.addEventListener('keydown', e => { if (e.key === 'Enter') redefinirSenha(); });
    emailInput.addEventListener('blur', () => {
      const v = emailInput.value.trim();
      if (v && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) clearInputError(emailInput, emailError);
    });
    codigoInput.addEventListener('input', () => {
      if (codigoInput.value.replace(/\D/g, '').length === 6) verificarCodigo();
    });
    irParaEtapa(1);
  </script>
</body>
</html>
