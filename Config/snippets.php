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

// log products with main detail out of stock
//$manager = $this->getManager();
//$articles = $manager->getRepository(Article::class)->findAll();
///** @var Article $article */
//$services = MxcDropshipInnocigs::getServices();
//$log = $services->get('logger');
//foreach ($articles as $article) {
//    /** @noinspection PhpUndefinedMethodInspection */
//    if ($article->getMainDetail()->getAttribute()->getDcIcInstock() > 0) continue;
//    $details = $article->getDetails();
//    /** @var Detail $detail */
//    foreach ($details as $detail) {
//        if ($detail->getKind() === 1 || empty($detail->getActive())) continue;
//        /** @noinspection PhpUndefinedMethodInspection */
//        if ($detail->getAttribute()->getDcIcInstock() > 0) {
//            $log->debug('Product with main detail out of stock: ' . $article->getName());
//            break;
//        }
//    }
//}
//
//$this->view->assign([ 'success' => true, 'message' => 'Development 1 slot is currently free.' ]);


// set associated products for SC Easy 3
//$repo = $this->getManager()->getRepository(Product::class);
//$easy3 = $repo->findOneBy(['name' => 'SC - Easy 3 - E-Zigarette']);
///** @var Article $easy */
//$easy = $easy3->getArticle();
//$easyRel = $easy->getRelated();
//$easyRel->clear();
//$caps = $repo->findBy(['type' => 'EASY3_CAP']);
///** @var Product $cursor */
///** @var Product $cap */
//foreach ($caps as $cursor) {
//    /** @var Article $act */
//    $act = $cursor->getArticle();
//    if ($act === null) continue;
//    $easyRel->add($act);
//    $similar = $act->getSimilar();
//    $similar->clear();
//    foreach ($caps as $cap) {
//        if ($cap->getIcNumber() === $cursor->getIcNumber()) continue;
//        $sim = $cap->getArticle();
//        if ($sim === null) continue;
//        $similar->add($sim);
//    }
//    $related = $act->getRelated();
//    $related->clear();
//    $related->add($easy);
//}
//$this->getManager()->flush();

