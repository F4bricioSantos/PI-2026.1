# ReformAí — Plataforma de Conexão para Reformas e Serviços

**ReformAí** é uma plataforma web premium voltada a conectar prestadores de serviços de reformas (pedreiros, eletricistas, pintores, encanadores, etc.) com clientes que necessitam de serviços residenciais ou comerciais. A aplicação conta com um sistema de busca inteligente, controle de contratações passo a passo, chat em tempo real integrado ao armazenamento online e avaliações mútuas de reputação.

---

## Proposta do Projeto
Encontrar profissionais qualificados para reformas muitas vezes é uma tarefa difícil e informal. O **ReformAí** resolve este problema ao oferecer:
* **Facilidade de busca:** Filtre profissionais por nicho, cidade e faixa de preço.
* **Segurança na contratação:** Fluxo formalizado de propostas, aceites e confirmações de conclusão.
* **Comunicação fluida:** Chat integrado para envio de mensagens de texto e imagens dos trabalhos/orçamentos.
* **Reputação transparente:** Sistema de avaliação com estrelas e comentários tanto para clientes quanto para prestadores.

---

## Funcionalidades Principais
1. **Cadastro Inteligente de Usuários:** Cadastro com fluxo de confirmação utilizando envio de código de verificação via e-mail.
2. **Exploração e Filtros:** Busca inteligente por palavras-chave, cidades com auto-complete dinâmico, categorias e limites de valor.
3. **Anúncio de Serviços:** Painel do prestador para criação (limite de 3 serviços) e edição de seus serviços.
4. **Portfólio Visual:** Upload de fotos reais de trabalhos vinculados aos serviços anunciados (armazenadas em nuvem).
5. **Chat com Envio de Mídia:** Chat dinâmico de negociação com polling automático, edição/exclusão de mensagens e upload direto de imagens.
6. **Gerenciador de Contratos:** Fluxo automatizado de propostas e conclusão (incluindo rotina de auto-conclusão após 15 dias).
7. **Favoritos:** Salve serviços interessantes para acesso rápido posterior.

---

## Pré-requisitos
Para rodar a aplicação localmente, você precisará de:
* **XAMPP** (ou qualquer servidor local com PHP 8.0+)
* Extensões de conexão ao PostgreSQL habilitadas no PHP.

---

## Como Rodar em Ambiente Local (XAMPP)

Como o projeto utiliza banco de dados **PostgreSQL** hospedado na nuvem (Supabase) para maior performance e robustez, você precisará configurar o XAMPP para se conectar a bancos de dados PostgreSQL.

### Passo 1: Habilitar as Extensões de PostgreSQL no XAMPP
Por padrão, o XAMPP vem configurado apenas para bancos MySQL. Siga as instruções abaixo para habilitar o suporte ao PostgreSQL:

1. Abra o **XAMPP Control Panel**.
2. Na linha do **Apache**, clique no botão **Config** e selecione a opção **PHP (php.ini)**.
3. No arquivo de texto que se abrirá, pressione `Ctrl + F` e busque pelas seguintes linhas:
   ```ini
   ;extension=pdo_pgsql
   ;extension=pgsql
   ```
4. **Descomente** ambas as linhas removendo o ponto e vírgula (`;`) do início delas:
   ```ini
   extension=pdo_pgsql
   extension=pgsql
   ```
5. Salve o arquivo e feche o editor.
6. Se o Apache já estiver rodando, clique em **Stop** e depois em **Start** para reiniciar o servidor e aplicar as alterações.

### Passo 2: Clonar/Copiar o Projeto para o Diretório do Servidor
Coloque a pasta completa do projeto `PI-2026.1` dentro da pasta raiz do seu servidor local:
* No Windows: `C:\xampp\htdocs\`

### Passo 3: Configurar a Conexão
Os dados de acesso ao banco hospedado na nuvem e chaves do Supabase já estão configurados no arquivo:
* `backend/config/Conexao.php`

> **Nota:** Certifique-se de que a sua máquina possui acesso à internet para se conectar ao banco hospedado na nuvem do Supabase.

### Passo 4: Executar a Aplicação
Abra o seu navegador de preferência e digite o endereço local:
```http
http://localhost/PI-2026.1/frontend/pages/login.php
```

---

## Estrutura do Banco de Dados
Caso precise recriar o banco de dados em outro servidor PostgreSQL, execute o script disponível na pasta:
* `database/BD ReformAI.sql` (Totalmente atualizado e compatível com as regras de negócio do backend).

---

## Tecnologias Utilizadas
* **Frontend:** HTML5, CSS3, JavaScript, TailwindCSS (Interface responsiva, moderna e fluida).
* **Backend:** PHP (Arquitetura estruturada/MVC com gerenciamento de sessões, rotas e segurança contra ataques CSRF).
* **Banco de Dados:** PostgreSQL (Supabase - hospedado online com conexão SSL ativa).
* **Serviços Cloud:** Supabase Storage (para hospedagem das imagens enviadas no chat e fotos de perfil/portfólio).