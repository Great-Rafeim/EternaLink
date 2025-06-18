<?php

namespace App\View\Components;

use Illuminate\View\Component;

class FuneralBookingsSection extends Component
{
    public $icon;
    public $color;
    public $title;
    public $subtitle;
    public $bookings;
    public $statusBadge;
    public $actions;
    public $modal;
    public $dateLabel;

    public function __construct($icon, $color, $title, $subtitle, $bookings, $statusBadge = null, $actions = [], $modal = false, $dateLabel = 'Requested On')
    {
        $this->icon = $icon;
        $this->color = $color;
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->bookings = $bookings;
        $this->statusBadge = $statusBadge;
        $this->actions = $actions;
        $this->modal = $modal;
        $this->dateLabel = $dateLabel;
    }

    public function render()
    {
        return view('components.funeral-bookings-section');
    }
}
