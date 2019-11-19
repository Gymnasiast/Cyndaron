<?php
namespace Cyndaron\Editor;

use Cyndaron\Category\Category;
use Cyndaron\DBConnection;
use Cyndaron\Model;
use Cyndaron\Page;
use Cyndaron\Request;
use Cyndaron\Setting;
use Cyndaron\Url;
use Cyndaron\User\User;

abstract class EditorPage extends Page
{
    const TYPE = null;
    const TABLE = null;
    const HAS_TITLE = true;
    const HAS_CATEGORY = false;
    const SAVE_URL = '';

    protected $id = null;

    protected $vorigeversie = false;
    protected $vvstring = '';
    protected $content;
    protected $contentTitle = '';
    /** @var Model */
    protected $model = null;
    protected $template = 'EditorPageBase.twig';

    public function __construct()
    {
        $this->id = (int)Request::getVar(2);
        $this->vorigeversie = Request::getVar(3) === 'previous';
        $this->vvstring = $this->vorigeversie ? 'vorige' : '';

        $this->prepare();

        parent::__construct('Editor');
        $this->addScript('/contrib/ckeditor/ckeditor.js');
        $this->addScript('/sys/js/editor.js');
        $this->showPrePage();

        $unfriendlyUrl = new Url('/' . static::TYPE . '/' . $this->id);
        $friendlyUrl = new Url($unfriendlyUrl->getFriendly());

        if ($unfriendlyUrl->equals($friendlyUrl))
        {
            $friendlyUrl = "";
        }

        $saveUrl = sprintf(static::SAVE_URL, $this->id ? (string)$this->id : '');
        $this->templateVars['id'] = $this->id;
        $this->templateVars['saveUrl'] = $saveUrl;
        $this->templateVars['articleType'] = static::TYPE;
        $this->templateVars['hasTitle'] = static::HAS_TITLE;
        $this->templateVars['hasCategory'] = static::HAS_CATEGORY;
        $this->templateVars['contentTitle'] = $this->contentTitle;
        $this->templateVars['friendlyUrl'] = trim($friendlyUrl, '/');
        $this->templateVars['friendlyUrlPrefix'] = "https://{$_SERVER['HTTP_HOST']}/";
        $this->templateVars['article'] = $this->content;
        $this->templateVars['model'] = $this->model;

        $sql = "SELECT * FROM (
            SELECT * FROM (SELECT CONCAT('/sub/', id) AS link, CONCAT('Statische pag.: ', name) AS name FROM subs) AS one
            UNION
            SELECT * FROM (SELECT CONCAT('/category/', id) AS link, CONCAT('Categorie: ', name) AS name FROM categories) AS two
            UNION
            SELECT * FROM (SELECT CONCAT('/photoalbum/', id) AS link, CONCAT('Fotoalbum: ', name) AS name FROM photoalbums) AS three
            UNION
            SELECT * FROM (SELECT CONCAT('/concert/order/', id) AS link, CONCAT('Concert: ', name) AS name FROM  ticketsale_concerts) AS four
            ) as zero ORDER BY name;";

        $this->templateVars['internalLinks'] = DBConnection::doQueryAndFetchAll($sql);

        if (static::HAS_CATEGORY)
        {
            if ($this->id)
            {
                $this->templateVars['categoryId'] = DBConnection::doQueryAndFetchOne('SELECT categoryId FROM ' . static::TABLE . ' WHERE id= ?', [$this->id]);
            }
            else
            {
                $this->templateVars['categoryId'] = Setting::get('defaultCategory');
            }

            $this->templateVars['categories'] = Category::fetchAll([], [], 'ORDER BY name');

            $showBreadcrumbs = false;
            if ($this->id)
            {
                $showBreadcrumbs = (bool)DBConnection::doQueryAndFetchOne('SELECT showBreadcrumbs FROM ' . static::TABLE . ' WHERE id=?', [$this->id]);
            }

            $this->templateVars['showBreadcrumbs'] = $showBreadcrumbs;
        }
        $this->showContentSpecificButtons();

        $this->showPostPage();
    }

    abstract protected function prepare();

    abstract protected function showContentSpecificButtons();

    protected function showCheckbox(string $id, string $description, bool $checked)
    {
        ?>
        <div class="form-group form-check">
            <input type="checkbox" class="form-check-input" id="<?=$id?>" name="<?=$id?>" <?=$checked ? 'checked' : ''?> value="1">
            <label class="form-check-label" for="<?=$id?>"><?=$description?></label>
        </div>
        <?php
    }
}