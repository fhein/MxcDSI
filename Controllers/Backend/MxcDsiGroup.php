<?php

use Mxc\Shopware\Plugin\Controller\BackendApplicationController;
use MxcDropshipInnocigs\Models\Group;
use MxcDropshipInnocigs\Models\Option;
use MxcDropshipInnocigs\Models\Product;

class Shopware_Controllers_Backend_MxcDsiGroup extends BackendApplicationController
{
    protected $model = Group::class;
    protected $alias = 'innocigs_group';

    public function indexAction()
    {
        $log = $this->getLog();
        $log->enter();
        try {
            parent::indexAction();
        } catch (Throwable $e) {
            $log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
        $log->leave();
    }

    public function updateAction()
    {
        $log = $this->getLog();
        $log->enter();
        try {
            parent::updateAction();
        } catch (Throwable $e) {
            $log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
        $log->leave();
    }

    protected function getAdditionalDetailData(array $data)
    {
        $data['options'] = [];
        return $data;
    }

    public function save($data)
    {
        $log = $this->getLog();
        $log->enter();
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
            $log->leave();
            return ['success' => false, 'violations' => $errors];
        }
        $modelManager = $this->getManager();

        /** @noinspection PhpUnhandledExceptionInspection */
        $modelManager->flush();
        //$modelManager->clear();

        $productMapper = $this->getServices()->get(ProductMapper::class);

        $productUpdates = $this->getLinkedProductsHavingChangedOptions($group->getId(), $groupChanged, $oldOptionValues);
        /** @noinspection PhpUnhandledExceptionInspection */
        $productMapper->updateArticles($productUpdates);

        $detail = $this->getDetail($group->getId());
        $log->leave();
        return ['success' => true, 'data' => $detail['data']];
    }

    /**
     * Get all Products having related Articles which are involved with the group update.
     * If $groupChanged is true, all Products having variants using any of the group's options
     * are added to the list. Otherwise only Products having Variants using Options with changed value
     * are added to the list.
     *
     * @param int $groupId
     * @param bool $groupChanged
     * @param array $oldOptionValue
     * @return array
     */
    protected function getLinkedProductsHavingChangedOptions(int $groupId, bool $groupChanged, array $oldOptionValue): array
    {
        $group = $this->getRepository()->find($groupId);
        $options = $group->getOptions();
        $repository = $this->getManager()->getRepository(Product::class);

        // get all Products which are linked to Articles
        /** @var Option $option */
        $relevantOptionIds = [];
        $time = - microtime(true);
        foreach ($options as $option) {
            if (! $groupChanged && ($option->isAccepted() === $oldOptionValue[$option->getName()])) {
                continue;
            }
            $relevantOptionIds[] = $option->getId();
        }
        $products = $repository->getLinkedProductsHavingOptions($relevantOptionIds);
        $time +=microtime(true);
        $this->getLog()->debug('Using SQL query: ' . sprintf('%f', $time));

        return $products;
    }
}
