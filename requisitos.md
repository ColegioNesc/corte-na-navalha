# Stack Tecnológico
* Backend: PHP estruturado (com sessões nativas)
* Banco de Dados: MySQL (PDO para segurança)
* Frontend: HTML5, CS5 (usando Bootstrap 5), Javascript

## Regras para Agents de IA
,alarmeadistânciarules
- Use sempre PDO para conexões e queries no MySQL para evitar SQL Injection.
- Mantenha o código limpo e comente apenas lógicas complexas.
- Separe os arquivos de formar lógica: um arquivo para conexão (db.php), scripts de backend isolados e views em HTML/PHP.
- A estrutura de arquivos deve ser feita sempre de forma modular.
- Estilize as telas com Bootstrap 5 de forma responsiv MobileFirts.
- Retorne mensagens de erro claras na interface para o usuário no estilo Toast.
- Trate sempre as mensagens nativas "es,caixas de mensagens com OK" sempre em um modal.

  ### Regras de Negócio CORE
  Senhas devem ser armazenadas com hash seguro (password_hash)
| ID | Descrição | Requisito / Condição |
| :---: | :--- | :--- |
| **RN-01** | **Endereço de Destino** | O usuário deve informar um endereço de destino válido para iniciar o monitoramento. |
| **RN-02** | **Rastreamento em Tempo Real** | O aplicativo deve utilizar o GPS do dispositivo para acompanhar a localização do usuário em tempo real. |
| **RN-03** | **Raio do Alerta** | O usuário deve poder escolher a distância para acionamento do alerta (ex.: 200m ou 500m antes do destino). |
| **RN-04** | **Notificação e Sinal** | O alarme sonoro e a vibração devem ser ativados assim que o usuário entrar no raio de distância configurado. |
| **RN-05** | **Controle do Trajeto** | O usuário deve ter a possibilidade de iniciar, pausar e cancelar o monitoramento do trajeto a qualquer momento. |
| **RN-06** | **Tratamento de GPS Desativado** | Se a localização estiver desativada ou indisponível, o app deve notificar o usuário alertando sobre o impacto no funcionamento. |

 # Documento de Especificação de Requisitos - Alarme a Distância

## 1. Visão Geral do Sistema

O **Alarme a Distância** é um aplicativo mobile que monitora a localização em tempo real do usuário via GPS para emitir alertas sonoros e/ou vibratórios quando o usuário entra no raio de distância configurado de um destino especificado.

## 2. Requisitos Funcionais (RF)

| ID | Descrição | Origem / Fluxo | 
 | ----- | ----- | ----- | 
| **RF01** | O sistema deve obter e monitorar a localização em tempo real do usuário via GPS. | Ação 1-2, 9-10 | 
| **RF02** | O sistema deve permitir que o usuário insira e defina um endereço de destino. | Diagrama / Ação 3-4 | 
| **RF03** | O sistema deve permitir a configuração do raio de distância (raio de cobertura) para acionamento do alarme. | Diagrama / Ação 5-6 | 
| **RF04** | O sistema deve permitir a personalização do tipo de alarme (som, vibração ou som + vibração). | Diagrama / Ação 7-8, 16 | 
| **RF05** | O sistema deve calcular continuamente a distância entre a localização atual e o destino configurado. | Ação 12 | 
| **RF06** | O sistema deve identificar quando a localização do usuário entra na área de raio de distância configurada. | Ação 13 | 
| **RF07** | O sistema deve acionar e emitir o alarme (conforme configurado) imediatamente ao atingir o raio de distância. | Ação 14, 15, 16 | 
| **RF08** | O sistema deve manter o alarme ativo até que o usuário o desligue manualmente. | Ação 18 | 
| **RF09** | O sistema deve permitir que o usuário desligue o alarme, interrompendo o som/vibração e encerrando o monitoramento de localização. | Ação 19-20 | 

## 3. Requisitos Não Funcionais (RNF)

| ID | Categoria | Descrição | 
 | ----- | ----- | ----- | 
| **RNF01** | **Desempenho/Precisão** | O cálculo de distância e a atualização de localização em tempo real devem ocorrer com baixa latência para garantir acionamento no momento exato em que se entra no raio configurado. | 
| **RNF02** | **Disponibilidade** | O monitoramento deve funcionar em segundo plano (background) no dispositivo do usuário enquanto o alarme estiver ativo. | 
| **RNF03** | **Usabilidade** | A interface deve disponibilizar a barra de busca de destino e controles intuitivos para escolha do raio de distância e opções de alarme. | 
| **RNF04** | **Confiabilidade** | O alarme não deve ser interrompido automaticamente antes da ação manual de desligamento pelo usuário. | 

## 4. Casos de Uso e Fluxos

### Caso de Uso: UC01 - Configuração e Execução do Alarme a Distância

* **Identificador:** RF01 / UC01

* **Ator:** Qualquer usuário

* **Pré-condição:** GPS do dispositivo ativado / Permissão de localização concedida.

* **Pós-condição:** Alarme desativado e monitoramento encerrado.

#### Fluxo Principal (Ações do Usuário e Respostas do Sistema)

| Passo | Ação do Usuário | Resposta do Sistema | 
 | ----- | ----- | ----- | 
| 1 | Ativa a localização em tempo real. | Obter a localização do usuário. | 
| 2 | Define o destino na busca. | Registra o destino informado. | 
| 3 | Define a distância para o alarme (raio). | Armazena a distância configurada. | 
| 4 | Seleciona o tipo de alarme. | Armazena a preferência do usuário (Som, Vibração ou Som + Vibração). | 
| 5 | — | Inicia o monitoramento da localização e atualiza a localização do usuário em tempo real. | 
| 6 | Aproxima-se do destino. | Calcula continuamente a distância até o destino. | 
| 7 | — | Identifica quando o usuário entra na distância configurada. | 
| 8 | — | Aciona o alarme no instante em que o usuário atinge a distância/destino. | 
| 9 | Recebe o alarme. | Emite o som, vibração ou som + vibração. | 
| 10 | Chega ao destino. | Mantém o alarme ativo. | 
| 11 | Desliga o alarme. | Interrompe o alarme e encerra o monitoramento. | 


#### Objetivos
Desenvolver um aplicativo chamado **Alarme a Distância**, que permita ao usuário informar seu destino, acompanhar sua localização em tempo real por meio do GPS e definir uma distância para receber o aviso. O sistema deverá calcular a proximidade do destino e ativar um alarme sonoro e vibratório quando o usuário estiver dentro do raio configurado. A interface deve ser simples, intuitiva e funcional, permitindo configurar, iniciar e parar o alarme com facilidade, ajudando a evitar que o usuário perca o ponto de desembarque durante a viagem. 

