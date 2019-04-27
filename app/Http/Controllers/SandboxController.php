<?php
/**
 * Created by PhpStorm.
 * User: mpemburn
 * Date: 4/26/19
 * Time: 9:20 AM
 */

namespace App\Http\Controllers;

use App\Services\LifeListService;

class SandboxController extends Controller
{
    public function lifeList()
    {
        $ll = new LifeListService('Life List 2019-04', 'Maine Trip 2018', true);
    }
}