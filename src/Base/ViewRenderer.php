<?php

namespace TeamTimer\Pakk\Base;

class ViewRenderer
{
    /**
     * @var $_controller Controller
     */
    private Controller $_controller;

    public function __construct(Controller $controller)
    {
        $this->_controller = $controller;
    }
    public function render($view, $params = [])
    {
        $basePath = ABSPATH.'/src/Resources/views/';
        $controllerID = $this->_controller->id;
        $viewPath = $basePath.$controllerID.'/'.$view.'.php';

        if(!file_exists($viewPath)){
            throw new \Exception('View not found: ' . $viewPath );
        }

        ob_start();
        extract($params);
        require $viewPath;
        $content = ob_get_clean();

        echo $content;
    }
}