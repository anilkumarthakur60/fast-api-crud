<?php

namespace Anil\FastApiCrud\Controller;

class SampleController extends Controller
{
    public function __construct()
    {
        $this->load = [
            'user1',
        ];
    }
}
