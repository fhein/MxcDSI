<?php

use Mxc\Shopware\Plugin\Controller\BackendApplicationController;
use Mxc\Shopware\Plugin\Database\SchemaManager;
use MxcDropshipInnocigs\Import\ImportModifier;
use MxcDropshipInnocigs\Import\InnocigsClient;
use MxcDropshipInnocigs\Import\InnocigsUpdater;
use MxcDropshipInnocigs\Mapping\ArticleMapper;
use MxcDropshipInnocigs\Models\Current\Article;

class Shopware_Controllers_Backend_MxcDsiArticle extends BackendApplicationController
{
    protected $model = Article::class;
    protected $alias = 'innocigs_article';

    public function indexAction() {
        $this->log->enter();
        /**
         * @var \Shopware\Components\Model\ModelManager $modelManager
         */
        try {
            $this->services->get(InnocigsClient::class)->import();
            parent::indexAction();
        } catch (Throwable $e) {
            $this->log->except($e);
        }
        $this->log->leave();
    }

    public function updateAction()
    {
        $this->log->enter();
        try {
            parent::updateAction();
        } catch (Throwable $e) {
            $this->log->except($e);
        }
        $this->log->leave();
    }

    public function importAction()
    {
        $this->log->enter();
        try {
            $sm = $this->services->get(SchemaManager::class);
            $client = $this->services->get(InnocigsClient::class);

            // $client->createConfiguratorConfiguration();

            // drop all database tables and remove all attributes
            // created by this plugin
            $sm->drop();
            // recreate database tables and attributes
            $sm->create();
            // import items from InnoCigs
            $client->import();
        } catch (Throwable $e) {
            $this->log->except($e);
        }
        $this->log->leave();
    }

    public function filterAction() {
        $this->log->enter();
        try {
            $importModifier = $this->services->get(ImportModifier::class);
            $importModifier->apply();
        } catch (Throwable $e) {
            $this->log->except($e);
        }
        $this->log->leave();
    }

    public function synchronizeAction() {
        $this->log->enter();
        $this->services->get(InnocigsUpdater::class);
        $this->log->leave();
    }

    protected function getAdditionalDetailData(array $data) {
        $data['variants'] = [];
        return $data;
    }

    public function save($data) {
        /** @var Article $model */
        $sActive = false;
        if (! empty($data['id'])) {
            $model = $this->getRepository()->find($data['id']);
            $sActive = $model->isActive();
        } else {
            $model = new $this->model();
            $this->getManager()->persist($model);
        }

        if (isset($data['variants']) && empty($data['variants'])) {
            unset($data['variants']);
        }
        $data = $this->resolveExtJsData($data);
        $model->fromArray($data);

        $uActive = $model->isActive();

        $articleMapper = $this->services->get(ArticleMapper::class);
        if ($uActive !== $sActive) {
            if (! $articleMapper->handleActiveStateChange($model)) {
                return [
                    'success' => false,
                    'message' => 'Shopware article not created because it failed to validate.',
                ];
            }
        }

        $violations = $this->getManager()->validate($model);
        $errors = [];
        /** @var Symfony\Component\Validator\ConstraintViolation $violation */
        foreach ($violations as $violation) {
            $errors[] = [
                'message' => $violation->getMessage(),
                'property' => $violation->getPropertyPath(),
            ];
        }

        if (!empty($errors)) {
            return ['success' => false, 'violations' => $errors];
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->getManager()->flush();

        $detail = $this->getDetail($model->getId());

        return ['success' => true, 'data' => $detail['data']];
    }
}
