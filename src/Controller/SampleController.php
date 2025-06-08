<?php

namespace Anil\FastApiCrud\Controller;

class SampleController extends BaseController
{
    public function __construct()
    {
        $this->with = [
            'user1',
            'user2' => function ($query) {
                $query->where('name', 'like', '%user2%');
            },
            'user3' => function ($query) {
                $query->where('name', 'like', '%user3%');
            },
            'user4' => function ($query) {
                $query->where('name', 'like', '%user4%');
            },
            'user5' => [
                'user6',
                'user7' => function ($query) {
                    $query->where('name', 'like', '%user7%');
                },
            ],
        ];
    }
}
