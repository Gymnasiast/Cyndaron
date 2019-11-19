<?php
declare (strict_types = 1);

namespace Cyndaron\Photoalbum;

use Cyndaron\Page;
use Cyndaron\Template\Template;
use Cyndaron\Widget\Button;

class PhotoalbumPage extends Page
{
    public function __construct(Photoalbum $album, $viewMode = 0)
    {
        $id = $album->id;
        $this->model = $album;
        $this->model->load();
        parent::__construct($this->model->name);

        if ($viewMode == 0)
        {
            $controls = new Button('edit', '/editor/photoalbum/' . $id, 'Dit fotoalbum bewerken');
            $this->setTitleButtons((string)$controls);
            $this->addScript('/sys/js/lightbox.min.js');

            $photos = Photo::fetchAllByAlbum($this->model);
            $this->templateVars['model'] = $this->model;
            $this->templateVars['photos'] = $photos;
        }
    }

    public function drawSlider(Photoalbum $album)
    {
        $photos = Photo::fetchAllByAlbum($album);
        return (new Template())->render('Photoalbum/Photoslider', compact('album', 'photos'));
    }
}