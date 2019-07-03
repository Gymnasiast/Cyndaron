<?php
namespace Cyndaron\Category;

use Cyndaron\DBConnection;

class EditorPage extends \Cyndaron\Editor\EditorPage
{
    const TYPE = 'category';
    const TABLE = 'categorieen';
    const HAS_CATEGORY = true;
    const SAVE_URL = '/editor/category/%s';

    protected $template = '';

    protected function prepare()
    {
        $viewMode = 0;
        if ($this->id)
        {
            $this->content = DBConnection::doQueryAndFetchOne('SELECT description FROM categories WHERE id=?', [$this->id]);
            $this->contentTitle = DBConnection::doQueryAndFetchOne('SELECT name FROM categories WHERE id=?', [$this->id]);
            $viewMode = (int)DBConnection::doQueryAndFetchOne('SELECT viewMode FROM categories WHERE id=?', [$this->id]);
        }

        $id = 'viewMode';
        $label = 'Weergave';
        $options = [0 => 'Samenvatting', 1 => 'Alleen titels', 2 => 'Blog', 3 => 'Portfolio'];
        $selected = $viewMode;

        $this->twigVars = array_merge($this->twigVars, compact('id', 'label', 'options', 'selected'));
    }

    protected function showContentSpecificButtons()
    {
    }
}