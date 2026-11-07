# Regras
Ainda sim seguindo os requisitos.md para construção e correção, vamos elencar todos os tópicos encontrados que devem ter correções.
o plano de implementação deve ser criado como implamentation_plan.md e detalhar cada passo que deve ser tomado de forma logica  para que não tenhamos que ficar refazendo modulos.
o plano de implementação deve ser criado pensando no plano free do antigravity pois cada passo que vamos implementar não pode extrapolar os tokens de IA.
# Objetivos
- deixar os usuários teste como estão, vamos usar a palavra chave "ACABOUOTESTE" para quando for colocado em produção for removido os atalhos de login de teste e deixar o mais limpo possível para a por em produção.
- todos os usuários criados no sistema devem sempre ser usuários comum.
- um usuário dono é o único que pode criar usuários de equipes, gerenciar, deletar, inativar ou modificar.
- cada login deve respeitar seus cargos que o dono pode delimitar as permissões
- todo usuário tem sua home principal, exemplo o usuário comum veem os serviços, o dono vê seu dashboard e configurações pertinentes a barbearia, a recepção veem os agendamentos e etc. dos barbeiros, os barbeiros veem os agendamentos que os usuários fizeram com eles e seus resultados financeiros.
- o catalogo de serviços pode ser tanto tabelado pelo dono como o dono pode liberar catálogos individuais para cada barbeiro no sistema, isso pode ser uma regra global ou individualizada o dono que decide.
- na gestão de pagamentos o dono pode usar tanto o pix fixo quanto poder configurar suas chaves do mercadopago para receber pagamento ou pagar as comissões de barbeiros, sendo assim se for usado o pix fixo todo o financeiro é controlado de forma manual, se for habilitado o gateway de pagamento as baixas de pagamentos são pelo webhook e a possibilidade de fazer o pagamento de comissão se for possível tambem, mas o pagamento de comissão fica a criterio se o dono deixa o sistema fazer ou vai ser feito manualmente e apenas dado baixa.
- nos pagamentos em dinheiro deve ter um tipo caixa onde a recepção ou o funcionário responsável por receber pode dizer o quanto recebeu em dinheiro para poder dar o troco correto.
- toda transação deve ser registrada como se fosse um comprovante de pagamento.
- devemos ter um lugar onde podemos configurar nosso email ou hospedado ou usar o próprio gmail como configuração pop e smtp para o sistema poder enviar mensagem de comprovantes, redefinição de senhas e etc. cada area que necessite do email deve ter um lugar onde o dono pode personalizar cada tipo de mensagem e essa personalização deve da a possibilidade de formatar o texto como se fosse no word.
