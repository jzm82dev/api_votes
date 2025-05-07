<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contestants extends Model
{
    public $name;
    public $lastname;

    public function __construct($name, $lastname)
    {
        $this->name = $name;
        $this->lastname = $lastname;
    }

    public function getName(){
        return $this->name;
    }

    public function getLastname(){
        return $this->lastname;
    }
}
