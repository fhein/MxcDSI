<?php

use Mxc\Shopware\Plugin\Controller\BackendApplicationController;
use MxcDropshipInnocigs\Import\ImportClient;
use MxcDropshipInnocigs\Models\Group;

class Shopware_Controllers_Backend_MxcDsiGroup extends BackendApplicationController
{
    protected $model = Group::class;
    protected $alias = 'innocigs_group';

    public function indexAction()
    {
        $this->log->enter();
        /**
         * @var \Shopware\Components\Model\ModelManager $modelManager
         */
        try {
            $this->services->get(ImportClient::class)->import();
            parent::indexAction();
        } catch (Throwable $e) {
            $this->log->except($e);
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
        if (!empty($data['id'])) {
            // this is a request to update an existing group
            $group = $this->getRepository()->find($data['id']);
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
        if (isset($data['options']) && empty($data['options'])) {
            unset($data['options']);
        } else {
            $this->log->debug('Group Detail: ' . var_export($data['options'], true));
        }

        // hydrate (new or existing) group from UI data
        $data = $this->resolveExtJsData($data);
        $group->fromArray($data);

        // updated $accepted state
        $uAccepted = $group->isAccepted();

        if ($uAccepted !== $sAccepted) {
            // propagate state change to all options belonging to this group
            /** @var \MxcDropshipInnocigs\Models\Option $option */
            foreach ($group->getOptions() as $option) {
                $option->setAccepted($uAccepted);
                $variants = $option->getVariants();
                // propagate state change to all variants employing this option
                /** @var \MxcDropshipInnocigs\Models\Variant $variant */
                foreach ($variants as $variant) {
                    $variant->setAccepted($uAccepted);
                }
            }
        }
        // Our customization ends here.
        // The rest below is default Shopware behaviour copied from parent implementation
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

        $detail = $this->getDetail($group->getId());
        $this->log->leave();
        return ['success' => true, 'data' => $detail['data']];
    }
}
