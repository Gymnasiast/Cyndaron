<?php
declare(strict_types=1);

namespace Cyndaron\Module;

interface Routes
{
    /**
     * Additional routes in the form of
     * ['route' => Controller::class]
     */
    public function routes(): array;
}