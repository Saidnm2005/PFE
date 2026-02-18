<?php

namespace App\Modules\Routing\Contracts;

interface RouteOptimizerInterface
{
    public function optimize(array $stations, array $start): array;
}
