<?php
namespace Cyndaron\Category;

use Cyndaron\DBConnection;
use Cyndaron\Page;
use Cyndaron\Photoalbum\Photoalbum;
use Cyndaron\Request;
use Cyndaron\StaticPage\StaticPageModel;
use Cyndaron\Url;
use Cyndaron\Util;

class CategoryPage extends Page
{
    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct($id)
    {
        if ($id === '0' || $id == 'fotoboeken')
        {
            $this->templateVars['type'] = 'photoalbums';
            $this->showPhotoalbumsIndex();
        }
        elseif ($id == 'tag')
        {
            $this->templateVars['type'] = 'tag';
            $this->showTagIndex(Request::getVar(2));
        }
        else
        {
            if ($id < 0)
            {
                header("Location: /error/404");
                die('Incorrecte parameter ontvangen.');
            }
            $this->templateVars['type'] = 'subs';
            $this->showCategoryIndex(intval($id));
        }
    }

    private function showCategoryIndex(int $id)
    {
        $this->model = new Category($id);
        $this->model->load();

        $controls = sprintf('<a href="/editor/category/%d" class="btn btn-outline-cyndaron" title="Deze categorie bewerken" role="button"><span class="glyphicon glyphicon-pencil"></span></a>', $id);
        parent::__construct($this->model->name);
        $this->setTitleButtons($controls);
        $this->showPrePage();

        $this->templateVars['model'] = $this->model;

        $tags = [];
        $subs = StaticPageModel::fetchAll(['categoryId= ?'], [$id], 'ORDER BY id DESC');
        foreach ($subs as $sub)
        {
            $tagList = $sub->getTagList();
            if (count($tagList) > 0)
            {
                $tags += $tagList;
            }
        }
        $this->templateVars['pages'] = $subs;
        $this->templateVars['viewMode'] = $this->model->viewMode;
        $this->templateVars['tags'] = $tags;

        if ($this->model->viewMode == Category::VIEWMODE_PORTFOLIO)
        {
            $portfolioContent = [];
            $subCategories = Category::fetchAll(['categoryId = ?'], [$id]);
            foreach ($subCategories as $subCategory)
            {
                $subs = StaticPageModel::fetchAll(['categoryId = ?'], [$subCategory->id], 'ORDER BY id DESC');
                $portfolioContent[$subCategory->name] = $subs;
            }
            $this->templateVars['portfolioContent'] = $portfolioContent;
        }

        $this->showPostPage();
    }

    private function showPhotoalbumsIndex()
    {
        parent::__construct('Fotoalbums');
        $this->showPrePage();
        $photoalbums = Photoalbum::fetchAll(['hideFromOverview = 0'], [], 'ORDER BY id DESC');
        $this->templateVars['pages'] = $photoalbums;
        $this->templateVars['viewMode'] = 1;

        $this->showPostPage();
    }

    private function showTagIndex($tag)
    {
        parent::__construct(ucfirst($tag));
        $this->showPrePage();

        $tags = [];
        $pages = [];
        $subs = StaticPageModel::fetchAll([], [], 'ORDER BY id DESC');
        foreach ($subs as $sub)
        {
            $tagList = $sub->getTagList();
            if ($tagList)
            {
                $tags += $tagList;
                if (in_array(strtolower($tag), $tagList))
                {
                    $pages[] = $sub;
                }
            }
        }
        $this->templateVars['pages'] = $pages;
        $this->templateVars['tags'] = $tags;
        $this->templateVars['viewMode'] = 2;

        $this->showPostPage();
    }
}