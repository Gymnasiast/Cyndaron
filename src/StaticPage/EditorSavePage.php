<?php
namespace Cyndaron\StaticPage;

use Cyndaron\Request\RequestParameters;
use Cyndaron\User\User;

class EditorSavePage extends \Cyndaron\Editor\EditorSavePage
{
    public const TYPE = 'sub';

    protected function prepare(RequestParameters $post)
    {
        $titel = $post->getHTML('titel');
        $text = $this->parseTextForInlineImages($post->getHTML('artikel'));
        $enableComments = $post->getBool('enableComments');
        $showBreadcrumbs = $post->getBool('showBreadcrumbs');
        $tags = trim($post->getSimpleString('tags'), "; \t\n\r\0\x0B");

        $model = new StaticPageModel($this->id);
        $model->loadIfIdIsSet();
        $model->name = $titel;
        $model->image = $post->getUrl('image');
        $model->previewImage = $post->getUrl('previewImage');
        $model->blurb = $post->getHTML('blurb');
        $model->text = $text;
        $model->enableComments = $enableComments;
        $model->showBreadcrumbs = $showBreadcrumbs;
        $model->tags = $tags;
        $this->saveCategories($model, $post);

        if ($model->save())
        {
            $this->id = $model->id;

            User::addNotification('Pagina bewerkt.');
            $this->returnUrl = '/sub/' . $this->id;
        }
        else
        {
            User::addNotification('Pagina opslaan mislukt');
        }
    }
}
