<?php

namespace Solspace\Freeform\Services;

use craft\base\Component;
use Solspace\Freeform\Events\Forms\FormValidateEvent;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Composer\Components\Fields\CheckboxField;
use Solspace\Freeform\Library\Composer\Components\Form;
use Solspace\Freeform\Library\Connections\ConnectionInterface;

class ConnectionsService extends Component
{
    /**
     * @param FormValidateEvent $event
     */
    public function validateConnections(FormValidateEvent $event)
    {
        $form = $event->getForm();

        $list = $form->getConnectionProperties()->getList();
        foreach ($list as $connection) {
            $keyValuePairs = $this->getKeyValuePairs($form, $connection);

            $result = $connection->validate($keyValuePairs);
            if (!$result->isSuccessful()) {
                foreach ($result->getFormErrors() as $error) {
                    $form->addError($error);
                }

                foreach ($result->getFieldErrors() as $fieldHandle => $errors) {
                    $field = $form->get($fieldHandle);
                    if ($field) {
                        $field->addErrors($errors);
                    }
                }
            }
        }
    }

    /**
     * @param Form $form
     */
    public function connect(Form $form)
    {
        $list = $form->getConnectionProperties()->getList();
        foreach ($list as $connection) {
            $keyValuePairs = $this->getKeyValuePairs($form, $connection);

            $result = $connection->connect($keyValuePairs);
            if (!$result->isSuccessful()) {
                Freeform::getInstance()->logger->error(
                    $result->getAllErrorJson(),
                    'freeform_connections'
                );
            }
        }
    }

    /**
     * @param Form                $form
     * @param ConnectionInterface $connection
     *
     * @return array
     */
    private function getKeyValuePairs(Form $form, ConnectionInterface $connection): array
    {
        $keyValuePairs = [];

        foreach ($connection->getMapping() as $craftFieldName => $freeformFieldName) {
            $field = $form->get($freeformFieldName);
            if (!$field) {
                continue;
            }

            if ($field instanceof CheckboxField) {
                $value = $field->getValueAsString();
            } else {
                $value = $field->getValue();
            }

            $keyValuePairs[$craftFieldName] = $value;
        }

        return $keyValuePairs;
    }

}
