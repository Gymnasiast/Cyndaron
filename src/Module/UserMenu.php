<?php
declare(strict_types=1);

namespace Cyndaron\Module;

interface UserMenu
{
    public function getUserMenuItems(): array;
}
