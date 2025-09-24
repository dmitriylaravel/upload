<?php

namespace App\Livewire;

use Livewire\Component;

class SuperMinimalTest extends Component
{
    public $count = 0;

    public function increment()
    {
        $this->count++;
    }

    public function render()
    {
        return '<div><h1>Count: {{ $count }}</h1><button wire:click="increment">+</button></div>';
    }
}