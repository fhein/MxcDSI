<?php

namespace MxcDropshipIntegrator\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use MxcDropshipIntegrator\Toolbox\Shopware\ArticleTool;
use Shopware\Models\Article\Article;
use Shopware_Controllers_Frontend_Index;

class MainDetailSelector implements SubscriberInterface
{
    // Diese Klasse soll dafür sorgen, dass im Falle, dass die Hauptvariante nicht verfügbar ist,
    // eine verfügbare Variante zur Hauptvariante gemacht wird, so dass wenn man ein Produkt öffnet,
    // eine verfügbare Variante angezeigt wird.
    //
    // Der Wechsel der Hauptvariante funktioniert, die Anzeige der neuen Hauptvariante nicht.
    // Deshalb erst einmal keine Aktion hier.

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        // Abgeschaltet
        return [
            // 'Enlight_Controller_Action_PreDispatch' => 'onPreDispatch',
            // 'Enlight_Controller_Action_PostDispatch' => 'onPostDispatch',
        ];
    }

    public function onPreDispatch(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Index $controller */
        /** @noinspection PhpUndefinedMethodInspection */
        $controller = $args->getSubject();
        $request = $controller->Request();

        // only on detail request
        if ($request->getModuleName() != 'frontend' || $request->getControllerName() != 'detail') return;
        /** @noinspection PhpUndefinedFieldInspection */
        $articleId = (int)$request->sArticle;
        if (empty($articleId)) return;


        /** @var Article $article */

        $mainDetail = ArticleTool::getArticleMainDetailArray($articleId);
        if (empty($mainDetail)) return;

        $instock = $mainDetail['dc_ic_active'] == 1 ? $mainDetail['dc_ic_instock'] : $mainDetail['instock'];
        if ($instock > 0) return;

        $subDetails = ArticleTool::getArticleSubDetailsArray($articleId);

        foreach ($subDetails as $detail) {
            $instock = $detail['dc_ic_active'] == 1 ? $detail['dc_ic_instock'] : $detail['instock'];

            if ($instock > 0) {
                ArticleTool::setArticleMainDetail($articleId, $detail['articledetailsID']);
                $request->setParam('number', $detail['ordernumber']);
                break;
            }
        }
    }

    public function onPostDispatch(Enlight_Event_EventArgs $args)
    {

        /** @var Shopware_Controllers_Frontend_Index $controller */
        /** @noinspection PhpUndefinedMethodInspection */
        $controller = $args->getSubject();
        $request = $controller->Request();
        $view = $controller->View();
        /** @noinspection PhpUndefinedFieldInspection */
        $article = $view->sArticle;

        if($request->getModuleName() != 'frontend' || $request->getControllerName() != 'detail') return;

        $articleId = $article['articleID'];
        $isLastStockArticle = $article['laststock'];
        /** @noinspection PhpUndefinedFieldInspection */
        $sConfigurator = $view->sArticle['sConfigurator'];

        if(empty($sConfigurator)) return;

        // true for configurator type 1, false for standard configurator
        /** @noinspection PhpUndefinedFieldInspection */
        $isStepConfigurator = 1 == $view->sArticle['sConfiguratorSettings']['type'];

        /** @noinspection PhpUndefinedFieldInspection */
        $configuratorType = $view->sArticle['sConfiguratorSettings']['type'];

        if (empty($request->number)) $configuratorType = 0;

        if ($configuratorType == 1) {
            $post = $request->getPost();
            if(!empty($post['group'])) {
                $configuratorType = 0;
            }
        }

        $selectedValues = [];
        $groupIds = [];
        foreach( $sConfigurator as &$group ) {
            if(empty($group['values'])) continue;

            $groupIds[] = $group['groupID'];

            foreach($group['values'] as $value) {
                //Find the selected values
                if(!empty($value['selected'])) {
                    $selectedValues[$value['groupID']] = $value['optionID'];
                }
            }
        }

        $options = [];
        foreach($groupIds as $groupId) {
            $options[$groupId] = [];

            $sql = "SELECT ad.* FROM s_articles_details as ad";

            $i = 1;
            if (!empty($selectedValues)) {
                foreach ($selectedValues as $currentGroupId => $selectedOptionId) {
                    if ($currentGroupId == $groupId) {
                        continue;
                    }

                    $sql .= sprintf("
                        INNER JOIN `s_article_configurator_option_relations` as r%d
                        ON r%d.article_id = ad.id
                        AND r%d.option_id = %d
                    ", $i, $i, $i, $selectedOptionId);

                    $i++;
                }
            }

            $sql .= sprintf("
                WHERE ad.articleID = %d AND ad.active = 1;
            ", $articleId);

            // Fetches all details for this group
            $details = Shopware()->Db()->fetchAll($sql);

            if (!empty($details)) {
                $detailIds = [];
                foreach ($details as $possibleArticle) {
                    $detailIds[] = $possibleArticle['id'];
                }

                $detailIds = implode(',', $detailIds);

                $sql = sprintf("
                    SELECT option_id FROM `s_article_configurator_option_relations` as r
                    INNER JOIN `s_article_configurator_options` as o
                    ON o.id = r.option_id
                    AND o.group_id = %d
                    WHERE r.`article_id` IN (%s)
                ", $groupId, $detailIds);
                $optionIds = Shopware()->Db()->fetchAll($sql);
                if (!empty($optionIds)) {
                    foreach ($optionIds as $optionIdItem) {
                        $options[$groupId][] = $optionIdItem['option_id'];
                    }
                }
            }
        }
    }
}