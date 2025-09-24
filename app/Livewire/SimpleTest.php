<?php

namespace App\Livewire;

use Livewire\Component;

class SimpleTest extends Component
{
    public $message = 'Hello World';
    public $count = 0;

    public function increment()
    {
        $this->count++;
    }

    public function render()
    {
        return view('livewire.simple-test');
    }
}