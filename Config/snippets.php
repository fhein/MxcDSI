<?php

// Snippets for controller actions

// Replace all commata with dots in price fields to ensure correct casting to float

//            $variants = $this->getManager()->getRepository(Variant::class)->getAllIndexed();
//            /** @var Variant $variant */
//            foreach ($variants as $variant) {
//                $price = str_replace(',', '.', $variant->getRecommendedRetailPrice());
//                $variant->setRecommendedRetailPrice($price);
//                $price = str_replace(',', '.', $variant->getPurchasePrice());
//                $variant->setPurchasePrice($price);
//
//                $price = str_replace(',', '.', $variant->getRecommendedRetailPriceOld());
//                $variant->setRecommendedRetailPriceOld($price);
//                $price = str_replace(',', '.', $variant->getPurchasePriceOld());
//                $variant->setPurchasePriceOld($price);
//            }
//            $this->getManager()->flush();


// recalculate product's seo information

//            $products = $this->getManager()->getRepository(Product::class)->findAll();
//            /** @var \MxcDropshipInnocigs\Mapping\Import\ProductSeoMapper $mapper */
//            $mapper = MxcDropshipInnocigs::getServices()->get(\MxcDropshipInnocigs\Mapping\Import\ProductSeoMapper::class);
//            /** @var Product $product */
//            $model = new Model();
//            foreach ($products as $product) {
//                $mapper->map($model, $product, true);
//            }
//            $mapper->report();
//            $this->getManager()->flush();




