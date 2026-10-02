# Stack Tecnológico
* Backend: PHP estruturado (com sessões nativas)
* Banco de Dados: MySQL (PDO para segurança)
* Frontend: HTML5, CSS (usando Bootstrap 5), JavaScript

## Regras para Agents de IA
.cortenavalharules
- Use sempre PDO para conexões e queries no MySQL para evitar SQL Injection.
- Mantenha o código limpo e comente apenas lógicas complexas.
- Separe os arquivos de forma lógica: um arquivo para conexão (db.php), scripts de backend isolados e views em HTML/PHP.
- A Estrutura de arquivos deve ser feita sempre de forma modular.
- Estilize as telas com Bootstrap 5 de forma responsiva MobileFirst.
- Retorne mensagens de erro claras na interface para o usuário no estilo Toast.
- Trate sempre as mensagens nativas "ex. caixas de mensagens com OK" sempre em um modal.

### Regras de Negócio CORE
Senhas devem ser armazenadas com hash seguro (password_hash).

#### Objetivos

