<?php /** @noinspection PhpUnusedParameterInspection */
/** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Enlight_Template_Manager;
use MxcDropshipInnocigs\Dropship\Innocigs\Registration;
use MxcDropshipInnocigs\Models\Dropship\Innocigs\Settings;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use Shopware\Models\Article\Detail;

class TemplateRegistration implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginDirectory;

    /**
     * @var Enlight_Template_Manager
     */
    private $templateManager;

    /**
     * @param $pluginDirectory
     * @param Enlight_Template_Manager $templateManager
     */
    public function __construct($pluginDirectory, Enlight_Template_Manager $templateManager)
    {
        $this->pluginDirectory = $pluginDirectory;
        $this->templateManager = $templateManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch' => 'onPreDispatch',
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Article' => 'onBackendArticlePostDispatch',
            'Enlight_Controller_Action_PostDispatch_Backend_ArticleList' => 'onBackendArticleListPostDispatch',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_MxcDsiArticleInnocigs' => 'onGetControllerPathMxcDsiArticleInnocigs',
        ];
    }

    public function onGetControllerBackendPathMxcDsiArticleInnocigs(Enlight_Event_EventArgs $args) {
        return __DIR__.'/../Controllers/Backend/MxcDsiArticleInnocigs.php';
    }

    public function onPreDispatch(Enlight_Event_EventArgs $args)
    {
        $this->templateManager->addTemplateDir($this->pluginDirectory . '/Resources/views');
    }

    /**
     * Overwrite and manage the backend extjs-resources
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onBackendArticlePostDispatch(Enlight_Event_EventArgs $args)
    {
        $view = $args->getSubject()->View();
        $request = $args->getSubject()->Request();

        if ($request->getActionName() === 'load') {

            $view->extendsTemplate(
                'backend/dropship_innocigs/article/model/detail.js'
            );

            $view->extendsTemplate(
                'backend/dropship_innocigs/article/view/window.js'
            );

            $view->extendsTemplate(
                'backend/dropship_innocigs/article/view/variant/detail.js'
            );

//                $view->assign('DCInnoCigs', $this->getLabel() . ' / ' . $source['namelong']);
//                $view->assign('DcOverwritePurchaseprice', Shopware()->Config()->get('dc_overwrite_purchaseprice'));
        }

    }

    public function onBackendArticleListPostDispatch(Enlight_Event_EventArgs $args)
    {
        $view = $args->getSubject()->View();
        $services = MxcDropshipInnocigs::getServices();
        $log = $services->get('logger');

        $actionName = $args->getRequest()->getActionName();
        $log->debug('Action name: ' . $actionName);

        if ($actionName === 'load') {
            $view->extendsTemplate('backend/dropship_innocigs/article/view/list/grid.js');
        }

        if ($actionName == 'filter') {
            $articleList = $view->getAssign('data');
            /** @var Registration $registration */
            $registration = $services->get(Registration::class);
            $manager = $services->get('modelManager');
            $repository = $manager->getRepository(Detail::class);
            $log->debug(var_export($articleList,true));

            // Check if dropship is configured
            foreach ($articleList as &$article) {
                $detailId = $article['Detail_id'];

                if ($article['Attribute_mxcDsiInnocigs'] != null) {
                    $detail = $repository->find($detailId);
                    /** @var Settings $settings */
                    $settings = $registration->getSettings($detail);
                    if (! $settings->isActive()) {
                        $color = 'red';
                    } elseif ($settings->isPreferOwnStock()) {
                        $color = 'orange';
                    } else {
                        $color = 'limegreen';
                    }
                    $article['mxc_dsi_ic_dropship'] = '<div style="width:16px;height:16px;background:' . $color . ';color:white;margin: 0 auto;text-align:center;border-radius: 3px;padding-top: 0px;" title="Dropship-Artikel">&nbsp;</div>';
                }
            }

            // Overwrite position data
            $view->clearAssign('data');
            $view->assign(
                ['data' => $articleList]
            );
        }
    }

}
