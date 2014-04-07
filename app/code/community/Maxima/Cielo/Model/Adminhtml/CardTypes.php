<?php

class Maxima_Cielo_Model_Adminhtml_CardTypes
{

    /**
     * Formato vetor de vetores
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array
		(
			array
			(
				'value' 	=> 'visa',
				'label' 	=> Mage::helper('adminhtml')->__('Visa'),
				'image' 	=> 'Visa.png'
			),
			array
			(
				'value' 	=> 'mastercard',
				'label' 	=> Mage::helper('adminhtml')->__('Mastercard'),
				'image' 	=> 'Master.png'
			),
			array
			(
				'value' 	=> 'diners',
				'label' 	=> Mage::helper('adminhtml')->__('Diners Club'),
				'image' 	=> 'Diners.png'
			),
			array
			(
				'value' 	=> 'discover',
				'label' 	=> Mage::helper('adminhtml')->__('Discover'),
				'image' 	=> 'Discover.png'
			),
			array
			(
				'value' 	=> 'elo',
				'label' 	=> Mage::helper('adminhtml')->__('Elo'),
				'image' 	=> 'Elo.png'
			),
			array
			(
				'value' 	=> 'amex',
				'label' 	=> Mage::helper('adminhtml')->__('American Express'),
				'image' 	=> 'Amex.png'
			)
        );
    }

    /**
     * Formato chave-valor
     *
     * @return array
     */
    public function toArray()
    {
        return array
		(
            'visa' 					=> Mage::helper('adminhtml')->__('Visa'),
            'mastercard' 			=> Mage::helper('adminhtml')->__('Mastercard'),
            'diners' 				=> Mage::helper('adminhtml')->__('Diners'),
            'discover' 				=> Mage::helper('adminhtml')->__('Discover'),
            'elo' 					=> Mage::helper('adminhtml')->__('Elo'),
            'amex' 					=> Mage::helper('adminhtml')->__('American Express'),
        );
    }

}