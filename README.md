# Alarme a Distância 🚨📍

O **Alarme a Distância** é um aplicativo web responsivo (*Mobile-First*) desenvolvido para ajudar usuários a não perderem o ponto de descida durante viagens em transporte público ou trajetos rodoviários. O usuário informa seu destino, define a distância de antecedência (raio de cobertura) e o sistema acompanha o deslocamento em tempo real via GPS, disparando um alarme contínuo (som, vibração ou ambos) no momento exato em que o usuário ingressa no raio configurado.

---

## 🚀 Stack Tecnológica

* **Backend:** PHP estruturado modular (com sessões nativas `session_start()`)
* **Banco de Dados:** MySQL / MariaDB (com conexões e queries 100% via **PDO** para proteção contra SQL Injection)
* **Frontend:** HTML5, CSS3, JavaScript moderno e **Bootstrap 5** (Mobile First)
* **Mapas & Geolocalização:** Leaflet.js, OpenStreetMap, Geocodificação Nominatim e Geolocation API com alta precisão
* **Áudio & Alerta:** Web Audio API (gerador de tons sintetizados autônomos de sirene, bips e toques) e Vibration API

---

## 📋 Atendimento aos Requisitos (`requisitos.md`)

### Regras para Agents de IA (`,alarmeadistânciarules`)
- [x] **PDO Obrigatório:** Todas as operações de banco utilizam PDO e prepared statements (`config/db.php`, `api/`, `includes/`).
- [x] **Código Limpo e Modular:** Separação entre conexão (`config/db.php`), scripts de backend isolados (`api/*.php`), utilitários (`includes/*.php`) e views (`*.php`).
- [x] **Design Mobile-First com Bootstrap 5:** Interface responsiva desenhada prioritariamente para telas de smartphones.
- [x] **Notificações em Toast:** Mensagens de feedback, erros e confirmações rápidas são apresentadas no padrão Toast do Bootstrap.
- [x] **Modais para Caixas de Mensagem com OK:** Tratamento de confirmações e alertas críticos realizado via Modais customizados do Bootstrap (`assets/js/ui.js`), substituindo pop-ups nativos do navegador (`alert`/`confirm`).
- [x] **Senhas Seguras:** Criptografia e validação com `password_hash()` e `password_verify()`.

---

### Regras de Negócio CORE
| ID | Descrição | Implementação no Sistema |
| :---: | :--- | :--- |
| **RN-01** | **Endereço de Destino** | Busca preditiva com geocodificação Nominatim/OSM, clique direto no mapa interativo ou seleção a partir dos locais favoritos. |
| **RN-02** | **Rastreamento em Tempo Real** | Utilização da Geolocation API com `watchPosition` e `enableHighAccuracy: true` para acompanhamento contínuo da posição. |
| **RN-03** | **Raio do Alerta** | Controle deslizante (slider) e botões de atalho rápido (200m, 300m, 500m, 1 km) com renderização visual do círculo no mapa. |
| **RN-04** | **Notificação e Sinal** | Alarme sonoro sintetizado em tempo real (Web Audio API) e vibração contínua (`navigator.vibrate`) disparados assim que `distância <= raio`. |
| **RN-05** | **Controle do Trajeto** | Controles completos para **Iniciar**, **Pausar**, **Retomar** e **Cancelar** o monitoramento a qualquer instante. |
| **RN-06** | **Tratamento de GPS Desativado** | Identificação imediata de erros de permissão ou falta de sinal de GPS, exibindo Modal explicativo com instruções de ativação. |

---

### Requisitos Funcionais (RF)
- **RF01:** Obtenção e monitoramento da localização GPS em tempo real.
- **RF02:** Definição do destino via campo de busca ou clique no mapa.
- **RF03:** Configuração do raio de cobertura do alerta em metros.
- **RF04:** Personalização do tipo de alarme: *Som*, *Vibração* ou *Som + Vibração*, além de seleção de toques (Sirene, Bip rápido ou Melodia).
- **RF05:** Cálculo contínuo da distância geográfica utilizando a **Fórmula de Haversine**.
- **RF06:** Detecção instantânea de entrada no raio configurado.
- **RF07:** Acionamento imediato do alarme sonoro, vibração e notificações push do navegador.
- **RF08:** Persistência do alarme ativo até que o usuário o desligue manualmente.
- **RF09:** Tela de alerta urgente com botão destacado **"DESLIGAR ALARME / CHEGUEI"** para interromper o som/vibração e concluir o monitoramento.

---

### Requisitos Não Funcionais (RNF)
- **RNF01 (Desempenho/Precisão):** Baixa latência no cálculo de distância local no cliente (`app.js`) com espelhamento assíncrono para a API.
- **RNF02 (Disponibilidade / Segundo Plano):** Suporte à **Screen Wake Lock API** para evitar suspensão da tela do smartphone durante a viagem, e Web Notifications para avisos em segundo plano.
- **RNF03 (Usabilidade):** Interface limpa, intuitiva e orientada ao toque.
- **RNF04 (Confiabilidade):** Bloqueio de auto-encerramento do alarme; apenas o clique manual do passageiro desliga o alarme.

---

## 📂 Estrutura de Diretórios

```text
alarme-a-distancia/
├── api/
│   ├── auth.php            # Endpoint de login, cadastro, logout e checagem de sessão
│   ├── favoritos.php       # Endpoint para gerenciar locais salvos (Casa, Trabalho)
│   └── trajeto.php         # Endpoint para registrar viagens e cálculo de distância
├── assets/
│   ├── css/
│   │   └── style.css       # Estilos Mobile-First, animações de radar e overlay de alarme
│   └── js/
│       ├── app.js          # GPS, Leaflet, Fórmula de Haversine, Wake Lock e controle
│       ├── audio.js        # Síntese de áudio (Web Audio API) e vibração contínua
│       └── ui.js           # Gerenciador de Toasts e Modais Bootstrap 5
├── config/
│   └── db.php              # Conexão segura MySQL com PDO
├── includes/
│   ├── auth.php            # Funções de sessão nativa e autenticação
│   ├── footer.php          # Rodapé, containers de toasts, modais e scripts
│   ├── functions.php       # Utilitários (Haversine PHP, sanitização de HTML)
│   └── header.php          # Cabeçalho, metatags mobile e navbar
├── sql/
│   └── schema.sql          # Estrutura do banco de dados (tabelas usuarios, viagens, favoritos)
├── historico.php           # Histórico de viagens anteriores com opção de repetir trajeto
├── index.php               # Painel principal do aplicativo (Monitor de Trajeto)
├── login.php               # Tela de autenticação
├── logout.php              # Encerramento de sessão
├── perfil.php              # Gerenciamento de preferências e destinos favoritos
├── registro.php            # Criação de nova conta
└── requisitos.md           # Especificação original dos requisitos
```

---

## ⚙️ Como Executar o Projeto

### Pré-requisitos
- PHP 8.1+ com extensões `pdo_mysql` e `mbstring`
- MySQL ou MariaDB

### 1. Configurar o Banco de Dados
Importe o arquivo [`sql/schema.sql`](sql/schema.sql) para o seu servidor MySQL/MariaDB:
```bash
mariadb -u root -p < sql/schema.sql
```
As credenciais padrão configuradas em [`config/db.php`](config/db.php) são:
* **Banco:** `alarme_db`
* **Usuário:** `alarme_user`
* **Senha:** `alarme_pass123`

### 2. Iniciar o Servidor Web
Você pode utilizar o servidor embutido do PHP:
```bash
php -S 0.0.0.0:8000
```
Ou acessar através do Apache em:
```text
http://localhost/alarme-a-distancia/
```
