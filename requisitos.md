# Stack Tecnológico
" Backend: PHP estruturado (com sessões nativas)
" Banco de Dados: MySQL (PIX para segurança)
" Frontend: HTML5, CSS (usando BOOTstrap 5), JavaScript

## Regras para Angents de IA
.cortenanavalharules
- Use sempre PDO para conexões e queries no MySQL para evitar SQL Injection.
- Mantenha o código limpo e comente apenas lógicas complexas.
- Separe os arquivos de forma lógica: umarquivo para conexão (db.php), scripts de backend isolados a views em HTML/PHP.
- A estrutura de arquivos deve ser feita sempre de forma modular.
- Estilize as telas com BooTstrap 5 de forma responsiva MobileFirst.
- Retorne mensagens de erro claras na interface para usuario no estilo Toast.
- Trate sempre as mensagens nativas "ex. caixas de mensagens com OK" sempre em um modal.

## Regras de Negócio CORE
Senhas devem ser armazenadas com hash seguro (password_hash.)
* Caso de uso	Fazendo o cadastro pela primeira vez
Usuario	Cliente
Pré condição	Ter um gmail valido e um CPF tambem
	
usuario	Resposta do sistema
Criar conta	
	Abrir tela de cadastro (nome gmail CPF)
Fazer loguin	
	Abre tela de loguin para colocar o nome e a senha
Visualizar serviços	
	Abrir menu de serviço
Escolher o corte	
	Abrir menu de serviço e escolher o corte ou barba ou os 2
Pagamento	
	Gerar o QR code de pagamento

* Caso de uso	Fazendo o cadastro pela primeira vez
Usuario	Cliente
Pré condição	Ter um gmail valido e um CPF tambem
	
usuario	Resposta do sistema
Criar conta	
	Abrir tela de cadastro (nome gmail CPF)
Fazer loguin	
	Abre tela de loguin para colocar o nome e a senha
Visualizar serviços	
	Abrir menu de serviço
Escolher o corte	
	Abrir menu de serviço e escolher o corte ou barba ou os 2
Pagamento	
	Gerar o QR code de pagamento
  Pagar na Hora para a recepsionista

  * Caso de uso	Ver os agendamentos
Usuario	Barbeiro
Pré condição	Ter um cadastro valido
	
Barbeiro	Resposta do sistema
Ver o corte escolhido pelo usuario	
	Abir o sistema e ver o corte 
Agendar o atendimento	
	Entrar no sistema e escolher qual cabelo cortar
Pedir o feedback do usuario	
	a o finalizar o corte pedir uma avaliação do usuario no app

  *Caso de uso	Se cadastrar pela primeira vez
Usuario	Dono
Pré condição	Ter um dev para programar o sistema
	
Dono	Resposta do sistema
Abrir a tela de cadastro	
	Fazer um cadastro como dono da empresa
Cadastrar a empresa	
	Abrir o sistema e cadastrar a empressa e seus funcionarios
Pagar os funcionarios	
	Entrar no sistema e pagar pela chave pix deles

  ### Objetivos
  * Que ela estabeleça uma melhora no aplicativo e agilize o processo
  * criar o codigo de forma limpa e objetiva
  
