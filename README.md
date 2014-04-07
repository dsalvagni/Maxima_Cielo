Maxima_Cielo
============

Alteração no código original do Fillipe Almeida Dutra <lawsann@gmail.com>.

* Alteração no fluxo do cancelamento do pagamento na página da Cielo Buy Page Cielo.
Quando o cliente optar por cancelar, na página da Cielo Buy Page Cielo, o pedido é 
cancelado e o cliente redirecionado para o carrinho de compras.

* Inclusão de CronJob para verificar pagamentos junto a Cielo.
Executa a cada 30 min uma verificação no webservice da Cielo dos pedidos realizados com Cartão de Crédito
e com status de pendente. Caso esteja com o status da Cielo: "Erro", cancela o pedido.

--

Qualquer dúvida, crie uma issue ou danielsalvagni@gmail.com
