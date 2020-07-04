<?php
namespace Cyndaron\Geelhoed\Contest;

use Cyndaron\Page;

final class OverviewPage extends Page
{
    public function __construct()
    {
        $contests = Contest::fetchAll(['date >= ?'], [date('Y-m-d H:i:s')], 'ORDER BY date');
        parent::__construct('Overzicht wedstrijden');
        $this->addTemplateVars(['contests' => $contests]);
    }
}
