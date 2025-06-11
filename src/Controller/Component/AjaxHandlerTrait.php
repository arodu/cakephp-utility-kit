<?php
declare(strict_types=1);

namespace UtilityKit\Controller\Component;

use Cake\Controller\Component;

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
     *
     * @return bool True if the action should be handled, false otherwise.
     */
    protected function _isActionHandled(): bool
    {
        $handledActions = (array)$this->getConfig('actions', []);
        if (empty($handledActions)) {
            // If the 'actions' array is empty, handle all actions by default.
            return true;
        }

        $currentAction = $this->getController()->getRequest()->getParam('action');

        return in_array($currentAction, $handledActions);
    }
}