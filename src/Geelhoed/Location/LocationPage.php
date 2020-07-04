<?php
namespace Cyndaron\Geelhoed\Location;

use Cyndaron\Page;

final class LocationPage extends Page
{
    public function __construct(Location $location)
    {
        parent::__construct($location->getName());
        $this->addTemplateVars(['location' => $location]);
    }
}
