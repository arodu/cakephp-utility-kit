<?php
declare(strict_types=1);

namespace UtilityKit\Controller\Component;

/**
 * Trait providing common logic for handling controller actions.
 *
 * @property \Cake\Controller\Component $this
 * @method \Cake\Controller\Controller getController()
 * @method mixed getConfig(string $key, mixed $default = null)
 */
trait AjaxHandlerTrait
{
    /**
     * Checks if the component should process the current controller action.
     *
     * The component will process the action if the 'actions' config is empty,
     * or if the current action is present in the 'actions' config array.
     * If the 'actions' config is set to '*' or contains '*', it will handle all actions.
     *
     * @return bool True if the action should be handled, false otherwise.
     */
    protected function _isActionHandled(): bool
    {
        $handledActions = $this->getConfig('actions', null);

        if (empty($handledActions)) {
            return false;
        }

        if ($handledActions === '*' || in_array('*', $handledActions, true)) {
            return true;
        }

        $currentAction = $this->getController()->getRequest()->getParam('action');

        return in_array($currentAction, $handledActions);
    }
}
