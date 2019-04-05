<?php

use Mxc\Shopware\Plugin\Controller\BackendApplicationController;
use MxcDropshipInnocigs\Mapping\ArticleMapper;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Group;
use MxcDropshipInnocigs\Models\Option;

class Shopware_Controllers_Backend_MxcDsiGroup extends BackendApplicationController
{
    protected $model = Group::class;
    protected $alias = 'innocigs_group';

    public function indexAction()
    {
        $this->log->enter();
        try {
            parent::indexAction();
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
        $this->log->leave();
    }

    public function updateAction()
    {
        $this->log->enter();
        try {
            parent::updateAction();
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
        $this->log->leave();
    }

    protected function getAdditionalDetailData(array $data)
    {
        $data['options'] = [];
        return $data;
    }

    public function save($data)
    {
        $this->log->enter();
        /** @var Group $group */
        $oldOptionValues = [];
        if (!empty($data['id'])) {
            // this is a request to update an existing group
            $group = $this->getRepository()->find($data['id']);
            $options = $group->getOptions();
            foreach ($options as $option) {
                $oldOptionValues[$option->getName()] = $option->isAccepted();
            }
            // currently stored $accepted state
            $sAccepted = $group->isAccepted();
        } else {
            // this is a request to create a new group (not supported via our UI)
            $group = new $this->model();
            $this->getManager()->persist($group);
            // default $Accepted state
            $sAccepted = true;
        }
        // Option data is empty only if the request comes from the list view (not the detail view)
        // We prevent storing an group with empty variant list by unsetting empty variant data.
        if ($data['options'] && empty($data['options'])) {
            unset($data['options']);
        }

        // hydrate (new or existing) group from UI data
        $data = $this->resolveExtJsData($data);
        $group->fromArray($data);

        // updated $accepted state
        $uAccepted = $group->isAccepted();
        $groupChanged = $uAccepted !== $sAccepted;

        $violations = $this->getManager()->validate($group);
        $errors = [];
        /** @var Symfony\Component\Validator\ConstraintViolation $violation */
        foreach ($violations as $violation) {
            $errors[] = [
                'message'  => $violation->getMessage(),
                'property' => $violation->getPropertyPath(),
            ];
        }

        if (!empty($errors)) {
            $this->log->leave();
            return ['success' => false, 'violations' => $errors];
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->getManager()->flush();

        // Important!
        $this->getManager()->clear();

        $articleMapper = $this->getServices()->get(ArticleMapper::class);

        $articleUpdates = $this->getLinkedArticlesHavingChangedOptions($group->getId(), $groupChanged, $oldOptionValues);
        /** @noinspection PhpUnhandledExceptionInspection */
        $articleMapper->processStateChangesArticleList($articleUpdates);

        $detail = $this->getDetail($group->getId());
        $this->log->leave();
        return ['success' => true, 'data' => $detail['data']];
    }


    /**
     * Get all InnoCigs article having related Shopware Articles which are involved with the group
     * update. If $groupChanged is true, all articles having variants using any of the group's options
     * are added to the list. Otherwise only articles having variants using options with changed value
     * are added to the list.
     *
     * @param int $groupId
     * @param bool $groupChanged
     * @param array $oldOptionValue
     * @return array
     */
    protected function getLinkedArticlesHavingChangedOptions(int $groupId, bool $groupChanged, array $oldOptionValue): array
    {
        $group = $this->getRepository()->find($groupId);
        $options = $group->getOptions();
        $repository = $this->getManager()->getRepository(Article::class);

        // get all InnoCigs articles which are linked to Shopware articles
        /** @var Option $option */
        $relevantOptionIds = [];
        $time = - microtime(true);
        foreach ($options as $option) {
            if (!$groupChanged && ($option->isAccepted() === $oldOptionValue[$option->getName()])) {
                continue;
            }
            $relevantOptionIds[] = $option->getId();
        }
        $articles = $repository->getLinkedArticlesHavingOptions($relevantOptionIds);
        $time +=microtime(true);
        $this->log->debug('Using SQL query: ' . sprintf('%f', $time));

        return $articles;
    }
}
