# Atualizações do Sistema - Barbearia Corte na Navalha

Todas as correções e melhorias solicitadas foram implementadas com sucesso:

### 1. Campo de Pesquisa em Todo o Sistema
- O campo que antes aparecia como um input cinza de leitura (`readonly`) sem descrição no comprovante foi totalmente reestruturado como uma área oficial e descrita de **PIX Copia e Cola**.
- Adicionados campos de busca descritos e com digitação fluida em tempo real:
  - **Navbar:** Campo de busca global com atalho e ícone para pesquisar cortes, procedimentos e barbeiros.
  - **Agendamento:** Campo para filtrar cortes e serviços em tempo real enquanto digita.
  - **Painel da Recepção:** Campo para busca de atendimentos do balcão por nome de cliente, CPF, telefone ou código.
  - **Painel do Barbeiro:** Campo para busca na agenda do dia.
  - **Área do Cliente:** Campo para busca nos agendamentos e histórico.
  - **Gestão (Admin):** Campos de busca nas tabelas de funcionários e serviços.

### 2. QR Code ao Finalizar Atendimento e Escuta de Pagamento
- O QR Code agora é acionado no momento da conclusão do atendimento pelo barbeiro ou recepção.
- Ao clicar em **"Finalizar & Cobrar PIX"**, o sistema conclui o corte e abre instantaneamente o modal de cobrança com o QR Code.
- Criada a API `api/agendamento_pix.php` com escuta em tempo real (polling a cada 3 segundos). Ao detectar o pagamento, o modal notifica com sucesso e atualiza o status automaticamente.

### 3. Notificação de Erro em Vermelho de Alta Visibilidade
- As notificações de erro (`danger`) no Toast e alertas do sistema agora possuem cabeçalho vermelho vibrante (`bg-danger text-white`), badge destacado `[ERRO]`, borda vermelha e corpo em alto contraste.
- Nenhum sinal ou mensagem de erro é exibido em texto branco invisível ou sem contraste.

### 4. Design Atrativo com Coloração Vermelha (Barbershop Crimson & Charcoal)
- Paleta inteira migrada para tons de vermelho rubro (`#dc2626`, `#b91c1c`, `#ef4444`) com gradientes elegantes e acabamento premium sobre fundo escuro carvão (`#0a0b0e`).
- Efeitos de brilho suave (glow vermelho) em botões, foco em formulários e cards de atendimento.

### 5. Reconhecimento e Visualização: Cliente vs Profissional
- O backend de autenticação (`actions/auth_login.php`) agora analisa as credenciais e compreende automaticamente se o usuário é um **CLIENTE** ou um **PROFISSIONAL** (`barbeiro`, `recepcao`, `dono`), direcionando-o diretamente ao painel correto sem bloqueios de abas.
- O Navbar e os painéis agora exibem badges exclusivos de categoria:
  - `CLIENTE` (azul discreto)
  - `PROFISSIONAL • BARBEIRO / RECEPÇÃO / DONO` (vermelho em destaque)
- Criada a página de perfil (`perfil.php`) oficial com dados, segurança e gestão de chave PIX para repasse aos profissionais.

### 6. Botão de QR Code Funcional e Clicável
- O botão de QR Code antes inacessível ou estático foi transformado em um componente ativo presente no painel do cliente, do barbeiro, da recepção e no comprovante.
- Abre o **Modal de Pagamento PIX** com botão funcional de cópia da chave, visualização nítida do QR Code e confirmação imediata.

### 7. Nova Logo Oficial e Tipografia Barbershop
- Criado o emblema vetorial exclusivo em SVG (`includes/logo.php`) com navalha afiada, lâmina polida em gradiente metálico, tesoura de acabamento e detalhes em vermelho.
- Incorporadas as fontes consagradas em barbearias clássicas: **'Oswald'** e **'Bebas Neue'**, com fonte de leitura limpa **'Plus Jakarta Sans'**.

### 8. Adequação de Linguagem ("Barbearia" em vez de "Estabelecimento")
- Todas as descrições que tratavam o app genericamente como "estabelecimento" foram substituídas por **"Barbearia"**, **"nossa barbearia"** ou **"Corte na Navalha"**.
