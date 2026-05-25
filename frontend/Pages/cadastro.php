<!DOCTYPE html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Cadastro — ReformAí</title>
    <meta name="description" content="Crie sua conta no ReformAí e comece a encontrar profissionais ou oferecer seus serviços de reforma." />

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
      .btn-primary:active { transform: scale(0.98); }
    </style>
  </head>

  <body class="min-h-screen bg-[#f0f0f0] flex items-center justify-center px-4 py-12 relative">

    <div class="w-full max-w-[440px]">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-10 py-10">

        <div class="mb-8 text-center">
          <h1 class="text-[28px] font-bold text-slate-900 leading-tight">Cadastro</h1>
          <p class="mt-1.5 text-[14px] text-gray-400">Crie sua conta para começar a reformar.</p>
        </div>

        <form id="cadastro-form" novalidate class="flex flex-col gap-5">

          <input type="hidden" name="fluxo" id="fluxo" value="cliente" />

          <div class="flex flex-col gap-1.5">
            <label for="nome" class="text-[13px] font-medium text-slate-700">Nome completo</label>
            <div class="relative">
              <input
                id="nome" name="nome" type="text"
                placeholder="Seu nome aqui" autocomplete="name"
                class="w-full h-[48px] px-4 rounded-lg border border-gray-200 bg-white text-[14px] text-slate-800 placeholder-gray-300
                       focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-all duration-200"
              />
            </div>
            <p id="nome-error" class="hidden text-[12px] text-orange-500 mt-0.5">Por favor, insira seu nome completo.</p>
          </div>

          <div class="flex flex-col gap-1.5">
            <label for="cpf" class="text-[13px] font-medium text-slate-700">CPF</label>
            <div class="relative">
              <input
                id="cpf" name="cpf" type="text"
                placeholder="000.000.000-00" maxlength="14"
                class="w-full h-[48px] px-4 rounded-lg border border-gray-200 bg-white text-[14px] text-slate-800 placeholder-gray-300
                       focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-all duration-200"
              />
            </div>
            <p id="cpf-error" class="hidden text-[12px] text-orange-500 mt-0.5">Por favor, insira um CPF válido.</p>
          </div>

          <div class="flex flex-col gap-1.5">
            <label for="email" class="text-[13px] font-medium text-slate-700">E-mail</label>
            <div class="relative">
              <input
                id="email" name="email" type="email"
                placeholder="email@exemplo.com" autocomplete="email"
                class="w-full h-[48px] px-4 pr-11 rounded-lg border border-gray-200 bg-white text-[14px] text-slate-800 placeholder-gray-300
                       focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-all duration-200"
              />
              <span id="email-icon" class="hidden absolute right-4 top-1/2 -translate-y-1/2 text-orange-500 pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                  <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
              </span>
            </div>
            <p id="email-error" class="hidden text-[12px] text-orange-500 mt-0.5">Este e-mail já está cadastrado.</p>
          </div>

          <div class="flex flex-col gap-1.5">
            <label for="senha" class="text-[13px] font-medium text-slate-700">Senha</label>
            <div class="relative">
              <input
                id="senha" name="senha" type="password"
                placeholder="Mínimo 8 caracteres" autocomplete="new-password"
                class="w-full h-[48px] px-4 pr-11 rounded-lg border border-gray-200 bg-white text-[14px] text-slate-800 placeholder-gray-300
                       focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-all duration-200"
              />
              <button type="button" id="toggle-senha" aria-label="Mostrar senha"
                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors focus:outline-none">
                <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
            </div>
            <p id="senha-error" class="hidden text-[12px] text-orange-500 mt-0.5">A senha deve ter pelo menos 8 caracteres.</p>
          </div>

          <div class="flex flex-col gap-1.5">
            <label for="confirmar-senha" class="text-[13px] font-medium text-slate-700">Confirmar senha</label>
            <div class="relative">
              <input
                id="confirmar-senha" name="confirmar-senha" type="password"
                placeholder="Repita sua senha" autocomplete="new-password"
                class="w-full h-[48px] px-4 pr-11 rounded-lg border border-gray-200 bg-white text-[14px] text-slate-800 placeholder-gray-300
                       focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-all duration-200"
              />
              <button type="button" id="toggle-confirmar" aria-label="Mostrar confirmação de senha"
                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors focus:outline-none">
                <svg id="eye-icon-confirmar" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
            </div>
            <p id="confirmar-senha-error" class="hidden text-[12px] text-orange-500 mt-0.5">As senhas não coincidem.</p>
          </div>

          <button id="btn-cadastrar" type="submit"
            class="btn-primary mt-1 w-full h-[52px] bg-orange-500 hover:bg-orange-600 active:bg-orange-700
                   text-white text-[15px] font-semibold rounded-lg shadow-sm
                   focus:outline-none focus:ring-4 focus:ring-orange-200
                   transition-all duration-200 flex items-center justify-center gap-2">
            <span id="btn-text">Cadastrar</span>
            <svg id="btn-spinner" class="hidden w-5 h-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
          </button>

        </form>

        <p class="mt-6 text-center text-[13px] text-gray-400">
          Já tem uma conta?
          <a href="login.php" class="text-orange-500 font-medium hover:text-orange-600 hover:underline transition-colors ml-1">Faça login</a>
        </p>

      </div>
    </div>

    <div id="modal-email" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center px-4 z-50">
      <div class="bg-white rounded-2xl p-8 max-w-[400px] w-full text-center shadow-xl border border-gray-100 flex flex-col gap-4">
        <div>
          <h3 class="text-xl font-bold text-slate-900">Verifique seu E-mail</h3>
          <p class="text-sm text-gray-400 mt-1">Insira abaixo o código de 6 dígitos enviado para a sua caixa de entrada.</p>
        </div>
        <div class="flex flex-col gap-1.5 text-left">
          <input 
            type="text" id="codigo-verificacao" placeholder="000000" maxlength="6"
            class="w-full h-[48px] text-center tracking-[0.5em] font-mono text-lg rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-all"
          />
          <p id="codigo-error" class="hidden text-[12px] text-orange-500 text-center mt-1">Código inválido ou expirado.</p>
        </div>
        <div class="flex gap-3 mt-2">
          <button type="button" id="btn-cancelar-modal" class="flex-1 h-[44px] rounded-lg border border-gray-200 text-sm font-medium text-slate-600 hover:bg-gray-50 transition-colors">Cancelar</button>
          <button type="button" id="btn-confirmar-codigo" class="flex-1 h-[44px] rounded-lg bg-orange-500 text-sm font-semibold text-white hover:bg-orange-600 transition-colors">Confirmar</button>
        </div>
      </div>
    </div>

    <script>
      const params = new URLSearchParams(window.location.search);
      document.getElementById('fluxo').value = params.get('fluxo') ?? 'cliente';

      const form           = document.getElementById('cadastro-form');
      const nomeInput      = document.getElementById('nome');
      const cpfInput       = document.getElementById('cpf');
      const emailInput     = document.getElementById('email');
      const senhaInput     = document.getElementById('senha');
      const confirmarInput = document.getElementById('confirmar-senha');

      const nomeError      = document.getElementById('nome-error');
      const cpfError       = document.getElementById('cpf-error');
      const emailError     = document.getElementById('email-error');
      const emailIcon      = document.getElementById('email-icon');
      const senhaError     = document.getElementById('senha-error');
      const confirmarError = document.getElementById('confirmar-senha-error');

      const btnText      = document.getElementById('btn-text');
      const btnSpinner   = document.getElementById('btn-spinner');
      const btnCadastrar = document.getElementById('btn-cadastrar');

      const modalEmail         = document.getElementById('modal-email');
      const codigoInput        = document.getElementById('codigo-verificacao');
      const codigoError        = document.getElementById('codigo-error');
      const btnCancelarModal   = document.getElementById('btn-cancelar-modal');
      const btnConfirmarCodigo = document.getElementById('btn-confirmar-codigo');

      function validaCPF(cpf) {
        cpf = cpf.replace(/\D/g, '');
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
        let soma = 0, resto;
        for (let i = 1; i <= 9; i++) soma += parseInt(cpf.substring(i - 1, i)) * (11 - i);
        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.substring(9, 10))) return false;
        soma = 0;
        for (let i = 1; i <= 10; i++) soma += parseInt(cpf.substring(i - 1, i)) * (12 - i);
        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        return resto === parseInt(cpf.substring(10, 11));
      }

      cpfInput.addEventListener('input', (e) => {
        let v = e.target.value.replace(/\D/g, '');
        if (v.length > 11) v = v.substring(0, 11);
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        e.target.value = v;
      });

      document.getElementById('toggle-senha').addEventListener('click', () => {
        const isPassword = senhaInput.type === 'password';
        senhaInput.type = isPassword ? 'text' : 'password';
        document.getElementById('eye-icon').style.opacity = isPassword ? '0.5' : '1';
      });

      document.getElementById('toggle-confirmar').addEventListener('click', () => {
        const isPassword = confirmarInput.type === 'password';
        confirmarInput.type = isPassword ? 'text' : 'password';
        document.getElementById('eye-icon-confirmar').style.opacity = isPassword ? '0.5' : '1';
      });

      function setError(input, errorEl, iconEl = null, msg = null) {
        input.classList.add('border-orange-400', 'bg-orange-50', 'input-error');
        input.classList.remove('border-gray-200', 'bg-white');
        errorEl.classList.remove('hidden');
        if (msg) errorEl.textContent = msg;
        if (iconEl) iconEl.classList.remove('hidden');
        setTimeout(() => input.classList.remove('input-error'), 500);
      }

      function clearError(input, errorEl, iconEl = null) {
        input.classList.remove('border-orange-400', 'bg-orange-50', 'input-error');
        input.classList.add('border-gray-200', 'bg-white');
        errorEl.classList.add('hidden');
        if (iconEl) iconEl.classList.add('hidden');
      }

      nomeInput.addEventListener('blur', () => {
        if (!nomeInput.value.trim()) setError(nomeInput, nomeError);
        else clearError(nomeInput, nomeError);
      });

      cpfInput.addEventListener('blur', () => {
        if (!validaCPF(cpfInput.value)) setError(cpfInput, cpfError);
        else clearError(cpfInput, cpfError);
      });

      emailInput.addEventListener('blur', () => {
        const v = emailInput.value.trim();
        const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
        if (!v || !valid) setError(emailInput, emailError, emailIcon, 'Por favor, insira um e-mail válido.');
        else clearError(emailInput, emailError, emailIcon);
      });

      senhaInput.addEventListener('blur', () => {
        if (senhaInput.value.length < 8) setError(senhaInput, senhaError);
        else clearError(senhaInput, senhaError);
      });

      confirmarInput.addEventListener('blur', () => {
        if (!confirmarInput.value || confirmarInput.value !== senhaInput.value) setError(confirmarInput, confirmarError);
        else clearError(confirmarInput, confirmarError);
      });

      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        let valid = true;

        if (!nomeInput.value.trim()) { setError(nomeInput, nomeError); valid = false; }
        if (!validaCPF(cpfInput.value)) { setError(cpfInput, cpfError); valid = false; }

        const emailVal = emailInput.value.trim();
        if (!emailVal || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
          setError(emailInput, emailError, emailIcon, 'Por favor, insira um e-mail válido.');
          valid = false;
        }

        if (senhaInput.value.length < 8) { setError(senhaInput, senhaError); valid = false; }
        if (!confirmarInput.value || confirmarInput.value !== senhaInput.value) { setError(confirmarInput, confirmarError); valid = false; }

        if (!valid) return;

        btnCadastrar.disabled = true;
        btnText.textContent   = 'Enviando código...';
        btnSpinner.classList.remove('hidden');

        try {
          const res = await fetch('/PI-2026.1/backend/controllers/AuthController.php?action=enviar_codigo_verificacao', {
            method: 'POST',
            body: new FormData(form),
          });
          
          const textData = await res.text();
          const data = JSON.parse(textData.trim());

          if (res.ok || data.sucesso) {
            if (data.token_desenvolvimento) {
              alert(`[MODO DESENVOLVIMENTO] Use o código: ${data.token_desenvolvimento}`);
            }
            modalEmail.classList.remove('hidden');
            codigoError.classList.add('hidden');
          } else {
            if (data.erros?.email) setError(emailInput, emailError, emailIcon, data.erros.email);
            if (data.erros?.cpf) setError(cpfInput, cpfError, null, data.erros.cpf);
            if (data.mensagem) alert(data.mensagem);
          }
        } catch (err) {
          console.error(err);
          alert('Erro na comunicação com o servidor.');
        } finally {
          btnCadastrar.disabled = false;
          btnText.textContent   = 'Cadastrar';
          btnSpinner.classList.add('hidden');
        }
      });

      btnCancelarModal.addEventListener('click', () => {
        modalEmail.classList.add('hidden');
        codigoInput.value = '';
      });

      btnConfirmarCodigo.addEventListener('click', async (e) => {
        e.preventDefault();
        
        const codigo = codigoInput.value.trim();
        if (codigo.length !== 6) {
          codigoError.classList.remove('hidden');
          codigoError.textContent = 'O código precisa conter 6 algarismos.';
          return;
        }

        btnConfirmarCodigo.disabled = true;
        btnConfirmarCodigo.textContent = 'Autenticando...';

        try {
          const formData = new FormData(form);
          formData.append('codigo_token', codigo);

          const res = await fetch('/PI-2026.1/backend/controllers/AuthController.php?action=cadastrar', {
            method: 'POST',
            body: formData
          });
          
          const textData = await res.text();
          const cleanText = textData.trim().substring(textData.indexOf('{'));
          const data = JSON.parse(cleanText);

          if (data.sucesso === true) {
            window.location.href = data.redirect;
          } else {
            codigoError.classList.remove('hidden');
            codigoError.textContent = data.mensagem ?? 'Código inválido ou expirado.';
            
            btnConfirmarCodigo.disabled = false;
            btnConfirmarCodigo.textContent = 'Confirmar';
          }
        } catch (err) {
          console.error(err);
          alert('Erro de comunicação. Abra o console (F12) para ver detalhes.');
          
          btnConfirmarCodigo.disabled = false;
          btnConfirmarCodigo.textContent = 'Confirmar';
        }
      });
    </script>
  </body>
</html>