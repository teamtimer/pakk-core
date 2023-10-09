<?php

namespace TeamTimer\Pakk\Base;

abstract class Controller
{
    public $id;

    private $_renderer;

    abstract public static function definitions();

    public function __construct()
    {
        $shortName = (new \ReflectionClass($this))->getShortName();
        $this->id = strtolower(str_replace('Controller', '', $shortName));
        add_action('admin_menu', [$this, 'wp_admin_menu']);
    }

    public function wp_admin_menu()
    {
        $definitions = static::definitions();

        foreach ($definitions as $key => $definition) {
            add_menu_page(
                $key,
                $key,
                null,
                $key,
                null,
                $definition['icon'],
                1000
            );

            foreach ($definition['items'] as $item) {
                add_submenu_page(
                    $key,
                    $item['title'],
                    $item['title'],
                    'manage_options',
                    $item['slug'],
                    [$this, $item['action']],
                );
            }
        }
    }

    protected function render($view, $params = [])
    {
        if(!$this->_renderer){
            $this->_renderer = new ViewRenderer($this);
        }

        $this->_renderer->render($view, $params);
    }
}