<?php

// Snippets for controller actions

// Replace all commata with dots in price fields to ensure correct casting to float
//
// try {
//$variants = $this->getManager()->getRepository(Variant::class)->getAllIndexed();
///** @var Variant $variant */
//foreach ($variants as $variant) {
//    $price = str_replace(',', '.', $variant->getRecommendedRetailPrice());
//    $variant->setRecommendedRetailPrice($price);
//    $price = str_replace(',', '.', $variant->getPurchasePrice());
//    $variant->setPurchasePrice($price);
//
//    $price = str_replace(',', '.', $variant->getRecommendedRetailPriceOld());
//    $variant->setRecommendedRetailPriceOld($price);
//    $price = str_replace(',', '.', $variant->getPurchasePriceOld());
//    $variant->setPurchasePriceOld($price);
//}
//$this->getManager()->flush();
//$this->view->assign([ 'success' => true, 'message' => 'Development 3 slot is currently free.' ]);
//} catch (Throwable $e) {
//    $this->handleException($e);
//}



