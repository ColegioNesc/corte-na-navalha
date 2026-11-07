# Plano de Implementação - Barbearia Corte na Navalha
> Baseado nos requisitos de `mods.md` e `requisitos.md`, estruturado para execução modular e eficiente.

---

## 1. Visão Geral dos Tópicos Identificados para Correção e Criação

1. **Atalhos de Teste com Palavra-chave `ACABOUOTESTE`:**
   - Implementar constante global e controle condicional para exibir ou ocultar totalmente os atalhos de login de teste em produção.
2. **Cadastro Público Seguro (Apenas Clientes):**
   - Bloquear criação de qualquer perfil diferente de `cliente` nas rotas públicas de auto-cadastro.
3. **Gestão Exclusiva da Equipe pelo Dono (CRUD + Inativação + Permissões):**
   - Permitir apenas ao dono criar, editar, inativar, reativar, excluir colaboradores e customizar matriz de permissões.
4. **Homes Principais e Redirecionamentos por Perfil:**
   - Dono: Dashboard de faturamento, equipe e configurações.
   - Barbeiro: Seus agendamentos e faturamento/comissões.
   - Recepção: Agenda geral de todos os barbeiros e fluxo de balcão.
   - Cliente: Catálogo de serviços e agendamentos.
5. **Catálogo de Serviços Flexível (Tabelado ou Individual):**
   - Configuração de regra global da barbearia ou liberação individual por barbeiro para criar/precificar seus próprios serviços.
6. **Módulo Duplo de Pagamentos (PIX Fixo vs Mercado Pago + Webhook):**
   - Chave PIX fixa com baixa manual OU gateway Mercado Pago com webhook automático e repasse de comissões.
7. **Caixa da Recepção com Calculadora de Troco:**
   - Modal e fluxo para pagamentos em dinheiro com registro de valor entregue, troco calculado e operador responsável.
8. **Registro Unificado de Transações e Comprovantes:**
   - Registro de toda movimentação financeira em tabela de transações com emissão de comprovante.
9. **Central de E-mail (SMTP/Gmail) e Editor Rico tipo Word:**
   - Configuração de SMTP/Gmail e personalização dos modelos de e-mail (comprovante, agendamento, senha) com formatação rica.

---

## 2. Passos Lógicos de Implementação

### Passo 1: Atualização do Banco de Dados & Configurações Globais
- Criar script SQL de migração e atualização automática em `config/db.php`:
  - Novas tabelas: `transacoes`, `configuracoes_sistema`, `configuracao_pagamentos`, `configuracao_email`, `templates_email`, `barbeiro_servicos`, `permissoes_cargos`.
  - Adicionar colunas necessárias em `usuarios` (ex: `permite_catalogo_proprio`, `ativo`, `permissoes_custom`).
  - Suporte à constante `ACABOUOTESTE` em `config/db.php`.

### Passo 2: Controle de Acesso, Cadastro Público & Palavra-chave `ACABOUOTESTE`
- Modificar `cadastro.php` e `actions/auth_register.php`:
  - Forçar perfil `cliente` em 100% dos cadastros públicos.
- Atualizar `login.php`:
  - Condicionar atalhos de teste à constante `ACABOUOTESTE`. Se `ACABOUOTESTE` for verdadeiro, ocultar atalhos.
- Atualizar `includes/navbar.php` e `includes/auth.php`:
  - Roteamento inteligente para a home de cada perfil.

### Passo 3: Gestão de Equipe e Permissões pelo Dono
- Aprimorar `admin/funcionarios.php` e `actions/funcionario_salvar.php`:
  - Adicionar ações de inativar/ativar e excluir funcionário.
  - Implementar modal de definição de permissões por cargo e por usuário.
  - Adicionar opção de liberar catálogo individual para cada barbeiro.
- Criar `actions/funcionario_status.php` e `actions/funcionario_excluir.php`.

### Passo 4: Catálogo de Serviços Global vs Individual do Barbeiro
- Adicionar opção nas configurações da barbearia: regra global de catálogo vs individual.
- Atualizar `admin/servicos.php`:
  - Indicar serviços gerais da casa e vínculos por barbeiro.
- Criar tela no painel do barbeiro (`barbeiro/meus_servicos.php`) para gerenciar seu catálogo quando liberado pelo dono.
- Atualizar fluxo de agendamento em `cliente/agendar.php` para carregar serviços de acordo com o barbeiro selecionado.

### Passo 5: Módulo de Caixa & Pagamento em Dinheiro com Cálculo de Troco
- Criar tabela/mecanismo de transações e comprovantes.
- Atualizar `recepcao/dashboard.php`:
  - Modal interativo "Receber no Caixa (Dinheiro)":
    - Campo de valor recebido do cliente.
    - Cálculo automático do troco em tempo real via JavaScript.
    - Confirmação e gravação da transação com identificação da recepção.
- Criar ação `actions/caixa_receber_dinheiro.php`.

### Passo 6: Gateway Mercado Pago & Webhook vs PIX Fixo
- Criar painel de configuração em `admin/pagamentos.php`:
  - Alternância entre "PIX Fixo" e "Mercado Pago Gateway".
  - Configurações de credenciais Mercado Pago (Public Key, Access Token, Webhook Secret, Modo Sandbox/Produção).
  - Opção para comissões automáticas vs manuais.
- Criar serviço `includes/mercadopago.php`:
  - Geração de pagamento via PIX Mercado Pago e consulta de status.
- Criar endpoint `api/mercadopago_webhook.php` para confirmação e baixa automática.

### Passo 7: Registro de Transações & Comprovante Digital
- Criar histórico financeiro completo de transações em `admin/transacoes.php`.
- Atualizar `cliente/comprovante.php` para exibir dados de caixa, troco, operador e método de pagamento de forma detalhada e imprimível.

### Passo 8: Central de E-mail (SMTP/Gmail) & Editor de Templates tipo Word
- Criar tela de configuração em `admin/email_config.php`:
  - Configuração de SMTP (Gmail, Hostgator, cPanel, etc.): host, porta, SSL/TLS, usuário, senha de app.
  - Botão de teste de conexão e envio de e-mail ao vivo.
- Criar tela de personalização de templates em `admin/email_templates.php`:
  - Editor rico (WYSIWYG) com formatação completa (estilo Word).
  - Variáveis dinâmicas para interpolação: `{CLIENTE_NOME}`, `{SERVICO}`, `{VALOR}`, `{TROCO}`, `{CODIGO}`, `{DATA}`, `{HORARIO}`, etc.
- Criar classe/helper de envio de e-mail em `includes/mailer.php`.

### Passo 9: Validação e Testes Finais
- Validar fluxo de cada perfil (Cliente, Barbeiro, Recepção, Dono).
- Testar cálculo de troco no caixa.
- Testar catálogo individual do barbeiro.
- Testar alternância do gateway de pagamento e envio de e-mail.
- Testar a flag `ACABOUOTESTE`.

