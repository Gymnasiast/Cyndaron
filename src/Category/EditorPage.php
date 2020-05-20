<?php
namespace Cyndaron\Category;

use Cyndaron\DBConnection;

class EditorPage extends \Cyndaron\Editor\EditorPage
{
    public const TYPE = 'category';
    public const TABLE = 'categories';
    public const HAS_CATEGORY = true;
    public const SAVE_URL = '/editor/category/%s';

    protected string $template = '';

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
        $options = Category::VIEWMODE_DESCRIPTIONS;
        $selected = $viewMode;

        $this->addTemplateVars(compact('id', 'label', 'options', 'selected'));
    }
}
