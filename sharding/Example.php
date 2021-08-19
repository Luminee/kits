<?php

namespace App\Models\User;

use App\Models\PartitionModel;

class _Example extends PartitionModel
{
    protected $softDeleted = true;

    protected $table = 'example';

    protected $parKey = 'related_id';

    protected $parRule = ['ope' => '%', 'v' => 10, 'effect' => [0]];

}
