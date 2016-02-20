<?php namespace KouTsuneka\Navigation;

class NavigationBuilder
{
    /**
     * @var string
     */
    protected $acronym = '';

    /**
     * @var string
     */
    protected $page_title = '';

    /**
     * @var bool
     */
    protected $sort = false;
    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var array
     */
    protected $crumbs = [];

    /**
     * @var array
     */
    protected $breadcrumb = [];

    /**
     * @var string
     */
    protected $page_heading;

    /**
     *
     */
    function __construct() {
        $this->acronym = config('navigation.acronym');
        $this->page_title = config('navigation.page_title');
        $this->items = array_merge($this->items, config('navigation.navigation'));
        $this->crumbs = array_merge($this->crumbs, config('navigation.crumbs'));
    }

    /**
     * @param $name
     * @param $text
     * @param bool|false $active
     * @param null $action
     * @param int $order
     * @param string $icon_class
     * @param null $items
     * @return $this
     */
    public function set($name, $text, $active = false, $action = null, $order = 0, $icon_class = '', $items = null, $hidden = null)
    {
        $this->items[$name] = [
            'text' => $text,
            'icon_class' => $icon_class,
            'order' => $order,
            'active' => $active,
            'items' => $items,
            'hidden' => $hidden
        ];
        if($action != null)
            $this->items[$name]['action'] = $action;

        return $this;
    }

    /**
     * @param $name
     * @param $sub_name
     * @param $text
     * @param bool|false $active
     * @param int $order
     * @param null $action
     * @return $this
     */
    public function set_sub($name, $sub_name, $text, $active = false, $order = 0, $action = null, $hidden = null)
    {
        if(array_has($this->items, $name))
        {
            $this->items[$name]['items'][$sub_name] = [
                'text' => $text,
                'active' => $active,
                'order' => $order
            ];
            if($action)
                $this->items[$name]['items'][$sub_name]['action'] = $action;

            if($hidden)
                $this->items[$name]['items'][$sub_name]['hidden'] = $hidden;
        }

        return $this;
    }

    /**
     * @param $name
     * @param $sub_name
     * @return $this
     */
    public function activate($name, $sub_name = null)
    {
        if(array_has($this->items, $name))
        {
            $this->items[$name]['active'] = true;
            if(isset($this->items[$name]['items']) && array_has($this->items[$name]['items'], $sub_name))
                $this->items[$name]['items'][$sub_name]['active'] = true;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function breadcrumb()
    {
        if(count($this->breadcrumb) > 0)
            $this->breadcrumb[count($this->breadcrumb)-1]['active'] = true;

        return [
            'page_heading' => $this->page_heading,
            'breadcrumb' => $this->breadcrumb
        ];
    }

    /**
     * @return $this
     */
    public function set_breadcrumb()
    {
        $numargs = func_num_args();
        $args = func_get_args();
        $breadcrumb = [];
        for ($i = 0; $i < $numargs; $i++) {
            if(is_string($args[$i])) {
                if (array_key_exists($args[$i], $this->crumbs))
                    $breadcrumb[] = $this->crumbs[$args[$i]];
                continue;
            }
            if(is_array($args[$i]))
            {
                $val = reset($args[$i]);
                $key = key($args[$i]);
                if (array_key_exists($key, $this->crumbs))
                    $breadcrumb[] = array_merge($this->crumbs[$key], $val);
                else
                    $breadcrumb[$key] = $val;
                continue;
            }
        }
        $this->breadcrumb = $breadcrumb;
        return $this;
    }

    /**
     * @param string $page_heading
     * @return $this
     */
    public function set_page_heading($page_heading)
    {
        $this->page_heading = $page_heading;
        return $this;
    }

    /**
     * @return bool
     */
    public function has_page_heading()
    {
        return !empty($this->breadcrumb) || !empty($this->page_heading);
    }

    /**
     * @return array
     */
    public function get_navigation()
    {
        return [
            'acronym' => $this->acronym,
            'menu_items' =>  $this->reconstruct()
        ];
    }

    /**
     * @param $value
     * @return $this
     */
    public function set_sort($value)
    {
        $this->sort = $value;
        return $this;
    }

    /**
     * @param $page_title
     */
    public function set_page_title($page_title)
    {
        $this->page_title = $page_title;
    }

    /**
     * @return string
     */
    public function get_page_title()
    {
        return $this->page_title;
    }

    /**
     * @return array
     */
    private function reconstruct()
    {
        $items = $this->items;

        if($this->sort)
            $items = $this->sortItems();

        $items = $this->hideItems($items);

        return $items;
    }

    /**
     * @param $items
     * @return mixed
     */
    protected function hideItems($items)
    {
        foreach($items as $key => &$it)
        {
            if($this->supposeToBeHidden($it))
                array_pull($items, $key);

            if(isset($it['items']))
            {
                foreach($it['items'] as $sub_key => $sub_it)
                    if($this->supposeToBeHidden($sub_it))
                        array_pull($it['items'], $sub_key);
                if(empty($it['items']) && !isset($it['action']))
                    array_pull($items, $key);
            }
        }
        return $items;
    }

    /**
     * @param $item
     * @return bool
     */
    protected function supposeToBeHidden($item)
    {
        if(isset($item['hidden']) && (
                (is_bool($item['hidden']) && $item['hidden']) || (is_callable($item['hidden']) && call_user_func($item['hidden']))
            ))
        {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    protected function sortItems()
    {
        $items = array_sort($this->items, function ($value) {
            return $value['order'];
        });
        foreach ($items as $key => $it) {
            if (!isset($it['items']))
                continue;
            $items[$key]['items'] = array_sort($it['items'], function ($value) {
                return $value['order'];
            });
        }
        return $items;
    }
}