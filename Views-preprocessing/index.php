<?php
class View {
    public function __construct($conf) {
        $this->conf = $conf;
    }
    public function __call($fn, $args) {
        $this->conf[$fn]($args[0]);
    }
}

abstract class Controller {

    protected $_rendering = false;
    protected $_views = array();
    protected $_viewsByHook = array();

    protected function _includeView($view)
    {
        return include 'views/' . $view . '.phtml';
    }

    protected function _viewsIterate($hook)
    {
        foreach($this->_viewsByHook[$hook] as $view) { // $callback
            $view->$hook($this);
        }
        return $this;
    }

    protected function _beforeRender() {
        $this->beforeRender();
        return $this;
    }

    protected function _afterRender() {
        $this->beforeRender();
        return $this;
    }

    public function render() {

        // render step
        $this->_rendering = true;

        // processing sbeforeRender
        $this->_beforeRender()
             // processing beforeRender callbacks
             ->_viewsIterate('beforeRender');

        // processing tree views
        include 'layout/default.phtml';

        // processing afterRender
        $this->_afterRender()
            // processing afterRender callbacks
             ->_viewsIterate('afterRender');

        return $this;
    }

    public function prepareView($viewName)
    {
        if(array_key_exists($viewName, $this->_views)) return $this;

        $this->_views[$viewName] = new View($hooks = $this->_includeView($viewName));
        if($hooks !== false) {
            foreach($hooks as $hook => $callback) {
                $this->_viewsByHook[$hook][$viewName] = $this->_views[$viewName];
            }
        }


        // 'prepare' in 'rendering' step
        $this->_rendering
        && $this->processView($viewName, 'beforeRender');

        return $this;
    }

    public function prepareViews($views)
    {
        foreach($views as $view) {
            $this->prepareView($view);
        }
        return $this;
    }

    public function processView($view, $hook = 'render')
    {
         if(!empty($this->_viewsByHook[$hook][$view])) {
             $this->_viewsByHook[$hook][$view]->$hook($this);
         } else {
             $this->prepareView($view)
                  ->processView($view, 'beforeRender')
                  ->processView($view);
         }
        return $this;
    }

    public function beforeRender() {}
    public function afterRender() {}
}


class MyController extends Controller {
    //public function defaultAction()
    //{
        // .. HTTP packaging ...
    //}
}

header('Content-Type: text/html; charset=UTF-8');
if($forp = extension_loaded("forp")) forp_start();

// rendering part of action processing
$c = new MyController;
$c->prepareViews(array('demo_0', 'demo_2'))
  ->render();


if($forp) {
    forp_end();
    echo "<pre>";
    //var_dump(forp_dump());
    forp_print();
    echo "</pre>";
}