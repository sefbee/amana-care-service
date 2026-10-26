<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Datatable extends Component
{
    public $id;

    public $class;

    public $columns;

    public $url;

    public $buttons;

    public $searching;

    public $order;

    public $paging;

    public $noDataInit; // if true then initially load datatable with no data

    /**
     * Create a new component instance.
     */
    public function __construct($columns, $url, $id = 'dt-table', $class = '', $buttons = false, $searching = true, $paging = true, $noDataInit = false, $order = 'desc')
    {
        $this->id = $id;
        $this->columns = $columns;
        $this->class = $class;
        $this->url = $url;

        $this->buttons = $buttons;
        $this->searching = $searching;
        $this->paging = $paging;
        $this->noDataInit = $noDataInit;
        $this->order = $order;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.datatable');
    }
}
