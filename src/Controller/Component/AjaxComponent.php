<?php
declare(strict_types=1);

namespace UtilityKit\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;

/**
 * AjaxComponent component
 *
 * Facilitates handling standard AJAX requests that expect an HTML response.
 * It ensures requests are made via AJAX and sets the appropriate AjaxView class
 * for rendering view templates without a layout.
 */
class AjaxComponent extends Component
{
    use AjaxHandlerTrait;

    protected array $_defaultConfig = [
        // A list of actions that this component should apply to.
        // If empty, it will apply to all actions in the controller.
        'actions' => [],
        // If true, an exception will be thrown if the request is not an AJAX request.
        'ajaxRequired' => true,
        // The name of the View class to use for AJAX rendering (e.g., 'Ajax').
        'ajaxClassName' => 'Ajax',
    ];

    /**
     * @param \Cake\Event\EventInterface $event The event instance.
     * @return void
     * @throws \Cake\Http\Exception\BadRequestException if ajaxRequired is true and request is not ajax.
     */
    public function beforeFilter(EventInterface $event): void
    {
        if (!$this->_isActionHandled()) {
            return;
        }

        $controller = $this->getController();

        if ($this->getConfig('ajaxRequired') && !$controller->getRequest()->is('ajax')) {
            throw new BadRequestException('This action must be accessed via an AJAX request.');
        }

        $controller->viewBuilder()->setClassName($this->getConfig('ajaxClassName'));
    }
}
