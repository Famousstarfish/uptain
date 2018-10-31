<?php

namespace UptainConnectNoIO\Providers;


use Plenty\Plugin\Templates\Twig;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Plugin\ConfigRepository;

use Plenty\Modules\Account\Contact\Contracts\ContactRepositoryContract;
use Plenty\Modules\Frontend\Services\AccountService;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;

class UptainSnippetProvider
{

	private static function getBasket()
	{
		$basket = pluginApp(BasketRepositoryContract::class)->load();
		
		return $basket;
	}
	
	private static function getContactId($accountService)
	{
		return $accountService->getAccountContactId();
	}
	
	private static function getContact($accountService)
	{
		$contactId = UptainSnippetProvider::getContactId($accountService);
		
		if ($contactId == 0) {
			return null;
		}
		
		return pluginApp(ContactRepositoryContract::class)->findContactById($contactId);
	}

    public function call(Twig $twig, AccountService $accountService, OrderRepositoryContract $orderRepository)
    {

        $contactId = UptainSnippetProvider::getContactId($accountService);
        
        if ($contactId == 0) {
        	$orders = array();
        } else {
        	$orders = $orderRepository->allOrdersByContact(
            	$contactId,
            	1,
            	100
	        )->getResult();
        }

        $orderList = [];
        foreach ($orders as $element) {
        	if ($element->paymentStatus == 'fullyPaid') {
        		$orderList[] = $element;
        	}
			
        }

        $voucherCode = "";
        $basket = UptainSnippetProvider::getBasket();
        
        if ($basket) {
        	$voucherCode = $basket->couponCode;
	    	$scv = $basket->basketAmount;
        	$currency = $basket->currency;
        }
        
        $contact = UptainSnippetProvider::getContact($accountService);
        
        if (!$contact) {
        	$contact = '';
        }
        
        try {
            $renderResult = $twig->render('UptainConnectNoIO::Snippet', [
                "voucherCode" => $voucherCode,
                "hasOrder" => count($orderList),
	            "contact" => $contact,
	            "scv" => $scv,
	            "currency" => $currency
	            ]);
        } catch (\Exception $err) {
            $renderResult = "";
        }
        return $renderResult;
    }
}