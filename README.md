<div align="center">

# 💈 Corte na Navalha Barbearia
### Sistema Completo de Gestão, Agendamento, Caixa & Pagamentos PIX

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![PIX](https://img.shields.io/badge/PIX-EMV_BR_Code-32BCAD?style=for-the-badge&logo=pix&logoColor=white)
![Status](https://img.shields.io/badge/Status-Concluído-22c55e?style=for-the-badge)

<p align="center">
  Plataforma web completa desenvolvida para a <strong>Barbearia Corte na Navalha</strong>, integrando agendamentos online e de balcão, geração de QR Code PIX dinâmico com escuta em tempo real, livro caixa contábil, comissionamento de barbeiros, recibos fiscais e política de cancelamento em conformidade com o Código de Defesa do Consumidor (CDC).
</p>

---

[Funcionalidades](#-funcionalidades-por-perfil) •
[Diferenciais Técnicos](#-diferenciais-técnicos) •
[Estrutura do Projeto](#-estrutura-do-projeto) •
[Instalação](#-como-executar-o-projeto) •
[Credenciais de Demonstração](#-credenciais-de-demonstração)

</div>

---

## 🎯 Visão Geral

O sistema **Corte na Navalha** oferece uma experiência unificada com **autenticação inteligente**: através de um único formulário de login (por E-mail ou CPF), a plataforma identifica automaticamente o perfil do usuário e o direciona para o seu respectivo painel operacional.

A interface segue uma identidade visual **Dark Charcoal & Crimson Red** (`#111319` / `#dc2626` / `#c5a059`), responsiva para computadores, tablets de recepção e smartphones, com navegação organizada por abas, busca rápida expansível e notificações interativas via *Toasts* e *Modais*.

---

## 👥 Funcionalidades por Perfil

### 🧔 1. Portal do Cliente (`/cliente`)
- **Catálogo Interativo de Serviços:** Acesso imediato à tabela de procedimentos da barbearia e serviços exclusivos de cada profissional ao fazer login.
- **Agendamento Antecipado:** Escolha de múltiplos procedimentos (corte, barba, sobrancelha, combos), seleção de barbeiro, data e horário disponível em tempo real.
- **Pagamento via PIX com Escuta Automática:** Geração de QR Code PIX válido (padrão EMV BR Code com CRC16) e código *Copia e Cola*, com verificação automática de confirmação de pagamento na tela.
- **Comprovante Digital Oficial:** Histórico de agendamentos e emissão de recibo detalhado pronto para impressão ou salvamento em PDF.
- **Avaliação de Atendimento:** Sistema de avaliação por estrelas e comentários após a conclusão do serviço.
- **Cancelamento Transparente (CDC):** Visualização clara da política de cancelamento da barbearia antes de confirmar ou cancelar uma reserva.

### ✂️ 2. Portal do Barbeiro (`/barbeiro`)
- **Agenda Diária e Futura:** Visualização dos clientes atribuídos, procedimentos contratados, horários e status de pagamento.
- **Gestão do Fluxo de Atendimento:** Atualização rápida de status (*Confirmado*, *Em Andamento*, *Concluído* ou *Cancelado*).
- **Catálogo Próprio (Quando Habilitado):** Permissão configurável para que o barbeiro cadastre e gerencie seus próprios serviços e preços especiais.
- **Extrato de Comissões e Avaliações:** Acompanhamento de ganhos por atendimento, chave PIX pessoal cadastrada e feedbacks recebidos dos clientes.

### 🖥️ 3. Painel da Recepção (`/recepcao`)
- **Controle de Fluxo e Check-in:** Painel em abas para gerenciamento rápido dos atendimentos do dia e fila de espera.
- **Agendamento Rápido no Balcão:**
  - **QR Code de Cadastro Expresso:** Modal dedicado na tela para que o cliente escaneie com o celular e se cadastre rapidamente informando apenas *Nome, E-mail e CPF*.
  - **Atendimento Não Identificado (Avulso):** Opção para registrar agendamentos imediatos de balcão sem exigir cadastro prévio.
  - **Isenção Automática:** Agendamentos presenciais de balcão são automaticamente isentos de taxa de cancelamento.
- **Frente de Caixa com Calculadora de Troco:** Recebimento presencial em Dinheiro, Cartão ou PIX no balcão, com atalhos de cédulas (`R$ 20`, `R$ 50`, `R$ 100`, `R$ 200`), cálculo de troco em tempo real e emissão imediata de comprovante.

### 👑 4. Painel do Proprietário / Administração (`/admin`)
- **Dashboard Executivo:** Indicadores de faturamento total, comissões, clientes ativos e desempenho da equipe.
- **Motor de Agendamento de Balcão Integrado:** Conta com o mesmo módulo ágil de agendamento presencial e QR Code de cadastro rápido da recepção.
- **Gestão de Equipe e Transações Individuais:**
  - Cadastro completo de funcionários (Barbeiros e Recepcionistas), definição de percentuais de comissão e permissões granulares.
  - **Auditoria Individual por Profissional:** Consulta detalhada de cada transação, serviço executado e repasse financeiro de cada colaborador individualmente.
  - **Pagamento de Comissões via PIX:** Geração de QR Code PIX direto para a chave do barbeiro com registro no livro caixa.
- **Política de Cancelamento & Conformidade CDC:**
  - Configuração exclusiva pelo proprietário da taxa de retenção (respeitando o limite legal máximo de **20%** do Código de Defesa do Consumidor) e do prazo mínimo de aviso prévio.
  - Decisão individualizada no momento do cancelamento: opção de cobrar a taxa padrão, reduzir o percentual (exclusivo do dono) ou isentar 100% o cliente, calculando automaticamente a retenção e o valor de reembolso.
- **Livro Caixa & Gateway Mercado Pago:** Relatório contábil completo de entradas, repasses e estornos, além de integração configurável com API PIX / Mercado Pago e notificações por e-mail.

---

## ⚡ Diferenciais Técnicos

- **Segurança Role-Based (RBAC) no Backend e Frontend:** A taxa de cancelamento é protegida por validação de sessão no servidor (`actions/agendamento_cancelar.php`), impedindo alterações via inspeção de elemento ou injeção de requisições por perfis não autorizados.
- **QR Code PIX Padrão Banco Central (EMVCo):** Gerador próprio em PHP (`includes/pix_qrcode.php`) com cálculo de checksum `CRC16-CCITT`, compatível com qualquer aplicativo bancário.
- **UX/UI Limpa e Despoluída:**
  - Navegação estruturada em abas (`Bootstrap 5 Pills`) em todos os painéis.
  - Barra de pesquisa global retrátil no cabeçalho (ícone que se expande ao clicar).
  - Substituição de alertas nativos do navegador por componentes visuais modernos (*Toasts* flutuantes e *Modais* customizados).

---

## 📂 Estrutura do Projeto

```text
navalha/
├── actions/                  # Controladores POST e processamento de regras de negócio
│   ├── auth_login.php        # Autenticação unificada com redirecionamento por perfil
│   ├── agendamento_salvar.php# Criação de reservas online e de balcão
│   ├── agendamento_cancelar.php # Cancelamento com cálculo de retenção/reembolso CDC
│   ├── caixa_receber.php     # Baixa de pagamentos presenciais e cálculo de troco
│   └── ...
├── admin/                    # Painel do Proprietário (Dashboard, Equipe, Caixa, Empresa)
├── api/                      # Endpoints JSON (Consulta de status PIX em tempo real)
├── assets/                   # Folhas de estilo CSS customizadas e scripts JS
├── barbeiro/                 # Painel do Barbeiro e catálogo individual
├── cliente/                  # Painel do Cliente, agendamento e comprovante fiscal
├── config/                   # Conexão PDO MySQL e configurações globais (db.php)
├── database/                 # Scripts de migração automática e atualização de schema
├── includes/                 # Componentes reutilizáveis (Header, Navbar, Modais, PIX, Balcão)
├── install/                  # Assistente interativo de instalação (Setup Wizard para novos hosts)
├── recepcao/                 # Painel de Atendimento e Caixa da Recepção
├── sql/                      # Script SQL completo de criação e seed inicial (schema.sql)
├── cadastro.php              # Cadastro completo de clientes
├── cadastro_rapido.php       # Cadastro expresso via QR Code no balcão (Nome, E-mail e CPF)
├── index.php                 # Landing Page institucional ("A Barbearia")
├── login.php                 # Tela única de autenticação
└── perfil.php                # Gestão de dados cadastrais e senha para todos os usuários
```

---

## 🚀 Como Executar o Projeto (Com Instalador Automático `/install`)

### Pré-requisitos
- Qualquer hospedagem web (Hostinger, cPanel, InfinityFree, Locaweb) ou ambiente local (**XAMPP**, **WAMP**, **Laragon**) com:
  - **PHP 8.0+** (com extensão `pdo_mysql` habilitada)
  - **MySQL 5.7+ / 8.0+** ou **MariaDB**
  - **Servidor Apache / Nginx**

### Opção 1: Instalação Interativa via Assistente Web (Recomendado para Alunos e Hospedagens)

1. **Suba os arquivos do projeto** para a pasta pública do seu servidor (`public_html` na hospedagem ou `htdocs` no XAMPP).
2. **Acesse o endereço do site no navegador pela primeira vez:**
   - Ao identificar que o banco de dados ainda não está conectado ou migrado, o sistema **redirecionará automaticamente** para o assistente **`/install/index.php`**.
3. **Siga os 4 passos na tela:**
   - **Passo 1 (Diagnóstico):** Valida a versão do PHP, extensões `PDO`/`MySQL` e permissão de escrita em `config/`.
   - **Passo 2 (Banco do Host):** Informe o `Host`, `Porta`, `Nome do Banco`, `Usuário` e `Senha` do seu servidor e clique em **"1º Testar Conexão"**. O botão de avanço é liberado assim que a conexão for validada.
   - **Passo 3 (Migração SQL):** Cria automaticamente todas as **11 tabelas**, aplica as colunas de atualização e importa os dados e usuários iniciais, gravando o arquivo `config/db_credentials.php`.
   - **Passo 4 (Finalização Segura):** Clique em **"Remover Pasta /install e Iniciar o Sistema"** — o assistente exclui a pasta `/install` do servidor por segurança e abre a página inicial pronta para uso!

### Opção 2: Importação Manual via SQL (Opcional)
- Caso prefira importar manualmente via **phpMyAdmin**, basta executar o arquivo [`sql/schema.sql`](sql/schema.sql) e ajustar as credenciais em `config/db.php` (ou `config/db_credentials.php`).

---

## 🔑 Credenciais de Demonstração

Na tela de login (`login.php`), você pode utilizar os botões de **Acesso Rápido de Demonstração** ou digitar manualmente qualquer uma das contas abaixo:

| Perfil | E-mail de Acesso | Senha Padrão | Destino Automático |
| :--- | :--- | :---: | :--- |
| **👑 Proprietário (Dono)** | `dono@cortenanavalha.com.br` | `123456` | `/admin/dashboard.php` |
| **📋 Recepção / Caixa** | `recepcao@cortenanavalha.com.br` | `123456` | `/recepcao/dashboard.php` |
| **✂️ Barbeiro** | `lucas@cortenanavalha.com.br` | `123456` | `/barbeiro/dashboard.php` |
| **🧔 Cliente** | `joao.cliente@gmail.com` | `123456` | `/cliente/dashboard.php` |

> **Dica de Produção:** Para ocultar os botões de acesso rápido de demonstração em ambiente de produção, basta alterar a constante `ACABOUOTESTE` para `true` no arquivo [`config/db.php`](config/db.php).

---

<div align="center">
  Desenvolvido com dedicação para a <strong>Barbearia Corte na Navalha</strong> 💈✂️ 
</div>
