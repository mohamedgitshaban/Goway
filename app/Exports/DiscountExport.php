<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class DiscountExport implements FromView
{
    protected $items;
    protected $view;

    public function __construct($items, $view)
    {
        $this->items = $items;
        $this->view = $view;
    }

    public function view(): View
    {
        return view($this->view, [
            'items' => $this->items
        ]);
    }
}
