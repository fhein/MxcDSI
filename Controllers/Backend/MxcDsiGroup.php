<?php /** @noinspection PhpUnhandledExceptionInspection */

use MxcCommons\Plugin\Controller\BackendApplicationController;
use MxcDropshipIntegrator\Mapping\ProductMapper;
use MxcDropshipIntegrator\Models\Group;
use MxcDropshipIntegrator\Models\Option;
use MxcDropshipIntegrator\Models\Product;
use MxcDropshipIntegrator\MxcDropshipIntegrator;

class Shopware_Controllers_Backend_MxcDsiGroup extends BackendApplicationController
{
    protected $model = Group::class;
    protected $alias = 'innocigs_group';

    public function indexAction()
    {
        $log = MxcDropshipIntegrator::getServices()->get('logger');
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
        $log = MxcDropshipIntegrator::getServices()->get('logger');
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


    protected function getOldOptionValues($data) {
        $group = isset($data['id']) ? $this->getRepository()->find($data['id']) : null;
        if (! $group) return [null, null];
        $options = $group->getOptions();
        $optionValue = [];
        /** @var Option $option */
        foreach ($options as $option) {
            $optionValue[$option->getName()] = $option->isAccepted();
        }
        return [$group, $optionValue];
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
        $optionIds = [];
        foreach ($options as $option) {
            if (! $groupChanged && ($option->isAccepted() === $oldOptionValue[$option->getName()])) {
                continue;
            }
            $optionIds[] = $option->getId();
        }
        return $repository->getLinkedProductsByOptionIds($optionIds);
    }

    public function save($data)
    {
        $services = MxcDropshipIntegrator::getServices();
        $log = $services->get('logger');
        $log->enter();

        list($group, $oldOptionValues) = $this->getOldOptionValues($data);

        if (! $group) {
            return [ 'success' => false, 'message' => 'Creation of new groups via GUI is not supported.'];
        }
        $sAccepted = $group->isAccepted();

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

        $this->getManager()->flush();
        $this->getManager()->clear();

        $productUpdates = $this->getLinkedProductsHavingChangedOptions($group->getId(), $groupChanged, $oldOptionValues);
        $services->get(ProductMapper::class)->controllerUpdateArticles($productUpdates, false);
        $this->getManager()->flush();

        $detail = $this->getDetail($group->getId());
        $log->leave();
        return ['success' => true, 'data' => $detail['data']];
    }

    protected function handleException(Throwable $e, bool $rethrow = false) {
        $log = MxcDropshipIntegrator::getServices()->get('logger');
        $log->except($e, true, $rethrow);
        $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
    }

}
