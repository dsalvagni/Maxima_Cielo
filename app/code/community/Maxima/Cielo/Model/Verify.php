<?php
/**
 * Observer que verifica o status do pagamento com cartão de crédito.
 * Altera o status do pedido e envia e-mail avisando o cliente.
 * @package Maxima
 * @author  Daniel Salvagni <danielsalvagni@gmail.com>
 */
class Maxima_Cielo_Model_Verify
{
	/**
	 * Método chamado pelo Cron Job
	 * Lista os pedidos Pendentes
	 * Filtra pela forma de pagamento "Cartão de Crédito"
	 * Filtra pelo status do pagamento como 0 (Criada), 1 (Em andamento) e 10 (Em autenticação)
	 * @return void
	 */
	public function verify()
	{
		$orders = Mage::getModel('sales/order')
				->getCollection()
				->addAttributeToSelect('*')
				->addFieldToFilter('status','pending');

		if(!$orders->getItems())
		{
			Mage::log('[Cielo::Verify] Nenhum pedido pendente nesta verificação');
			return false;
		}

		foreach($orders as $order):
		
			$payment = $order->getPayment();

			/**
			 * Verifica se existe um pagamento para este pedido.
			 * Pedidos novos também são marcados como pendentes (pending).
			 * Caso não haja pagamento, pula este item.
			 */
			if(!$payment) continue;

			/**
			 * Verifica qual foi a forma de pagamento utilizada
			 * Caso não seja cartão de crédito, pula este item.
			 */
			if($payment->getMethodInstance()->getCode() != "Maxima_Cielo_Cc") continue;

			/**
			 * Verifica se ocorreu um erro durante a transação
			 * Caso tenha acontecido, pula este item.
			 */
			if($payment->getAdditionalInformation('Cielo_error')) continue;

			/**
			 * Verifica se existe um ID de transação.
			 * Caso não exista, pula este item.
			 */
			if(!$payment->getAdditionalInformation('Cielo_tid')) continue;

			/**
			 * Verifica se o status da transação esta como: Criada; Em andamento; ou Em autenticação;
			 * Caso não esteja entre esses, pula este item.
			 */
			if(!in_array($payment->getAdditionalInformation('Cielo_status'),array(0,1,10)))
				continue;

			/**
			 * Executa a verificação do pagamento para o pedido.
			 */
			$this->execute($order, $payment->getAdditionalInformation('Cielo_tid'));

		endforeach;
	}

	/**
	 * Método interno que executa as tarefas de verificar o status do pagamento,
	 * alterar o status do pedido e enviar e-mail para o cliente.
	 *
	 * @param Sales_Order $order Pedido que terá o status de pagamento verificado.
	 * @return  void
	 */
	private function execute($order, $tid)
	{

		$payment = $order->getPayment();
		
		/**
		 * Cria uma nova requisição de transação para consultar o status.
		 */
		$methodCode 		= $order->getPayment()->getMethodInstance()->getCode();
		$cieloNumber 		= Mage::getStoreConfig('payment/' . $methodCode . '/cielo_number');
		$cieloKey 			= Mage::getStoreConfig('payment/' . $methodCode . '/cielo_key');
		$environment		= Mage::getStoreConfig('payment/' . $methodCode . '/environment');
		$sslFile			= Mage::getStoreConfig('payment/' . $methodCode . '/ssl_file');
		$autoCapture 		= Mage::getStoreConfig('payment/Maxima_Cielo_Cc/auto_capture');

		$webServiceOrder = Mage::getModel('Maxima_Cielo/webServiceOrder', array('enderecoBase' => $environment, 'caminhoCertificado' => $sslFile));
		/**
		 * Atribui a chave e token da Cielo.
		 * Atribui o ID da transação.
		 */
		$webServiceOrder->tid 			= $tid;
		$webServiceOrder->cieloNumber 	= $cieloNumber;
		$webServiceOrder->cieloKey 		= $cieloKey;

		/**
		 * Executa a consulta
		 */
		$status = $webServiceOrder->requestConsultation();
		$xml = $webServiceOrder->getXmlResponse();
		$eci = (isset($xml->autenticacao->eci)) ? ((string) $xml->autenticacao->eci) : "";

		/**
		 * Altera as informações do pagamento
		 */
		$payment->setAdditionalInformation('Cielo_status', $status);
		$payment->setAdditionalInformation('Cielo_eci', $eci);
		$payment->save();

		/**
		 * Salva o pedido
		 */
		$order->save();

		Mage::log("[Cielo] STATUS: {$status}");
		
		/**
		 * Verifica o status do pagamento consultado
		 * 6 - Transação concluída - Pagamento Realizado
		 */
		if($status == 6)
		{
			if(!$autoCapture && $payment->getMethodInstance()->getCode() == "Maxima_Cielo_Cc")
			{
				Mage::log("[Cielo] Pedido foi capturado, enquanto o flag indicava que nao deveria ter sido.");
			}
			else
			{
				if($order->canInvoice() && !$order->hasInvoices())
				{
					$order->setState('processing', 'processing', '', true);
					$order->setStatus('processing');
					$order->sendOrderUpdateEmail(true);
					$order->setEmailSent(true);
		        	$order->save();
		        	
					$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice(); 
					$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
					$invoice->sendEmail(true);
					$invoice->setEmailSent(true);
					$invoice->register();

					$transactionSave = Mage::getModel('core/resource_transaction')
					->addObject($invoice)
					->addObject($invoice->getOrder());
					  
					$transactionSave->save();

					
				} 
			}
		}
		/**
		 * Cancelada ou Não autorizada
		 * Compra cancelada
		 */
		else if($status == 9 || $status == 5)
		{
			/**
			 * Se for possível, cancela a compra
			 */
			if($order->canCancel())
			{
	            $order->cancel();
		        $order->setStatus('canceled');
		        $order->save();
		    }
		    /**
		     * Envia e-mail avisando que a compra foi cancelada
		     */
			$order->sendOrderUpdateEmail(true);
			$order->setEmailSent(true);
			$order->save();
		}
	}
}