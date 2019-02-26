<?php
namespace Cyndaron\Category;

use Cyndaron\DBConnection;
use Cyndaron\Pagina;
use Cyndaron\Url;
use Cyndaron\Util;

class CategoryPage extends Pagina
{
    public function __construct($id)
    {
        $this->connection = DBConnection::getPDO();

        if ($id != 'fotoboeken')
        {
            $this->toonCategorieIndex(intval($id));
        }
        else
        {
            $this->toonFotoalbumsIndex();
        }
    }

    private function toonCategorieIndex(int $id)
    {
        if ($id < 0)
        {
            header("Location: /404");
            die('Incorrecte parameter ontvangen.');
        }

        $naam = DBConnection::doQueryAndFetchOne("SELECT naam FROM categorieen WHERE id= ?;", [$id]);
        $alleentitel = DBConnection::doQueryAndFetchOne("SELECT alleentitel FROM categorieen WHERE id=?", [$id]);
        $controls = sprintf('<a href="/editor/category/%d" class="btn btn-outline-cyndaron" title="Deze categorie bewerken" role="button"><span class="glyphicon glyphicon-pencil"></span></a>', $id);

        parent::__construct($naam);
        $this->setTitleButtons($controls);
        $this->showPrePage();

        $beschrijving = DBConnection::doQueryAndFetchOne('SELECT beschrijving FROM categorieen WHERE id= ?', [$id]);
        echo $beschrijving;
        $paginas = $this->connection->prepare('SELECT * FROM subs WHERE categorieid= ? ORDER BY id DESC');
        $paginas->execute([$id]);

        if ($alleentitel)
        {
            echo '<ul class="zonderbullets">';
        }

        foreach ($paginas->fetchAll() as $pagina)
        {
            $url = new Url('/sub/' . $pagina['id']);
            $link = $url->getFriendly();
            if ($alleentitel)
            {
                echo '<li><h3><a href="' . $link . '">' . $pagina['naam'] . '</a></h3></li>';
            }
            else
            {
                echo "\n<p><h3><a href=\"" . $link . '">' . $pagina['naam'] . "</a></h3>\n";
                echo Util::wordlimit(trim($pagina['tekst']), 30, "...") . '<a href="' . $link . '"><br /><i>Meer lezen...</i></a></p>';
            }
        }
        if ($alleentitel)
        {
            echo '</ul>';
        }

        $this->showPostPage();
    }

    private function toonFotoalbumsIndex()
    {
        parent::__construct('Fotoalbums');
        $this->showPrePage();
        $fotoboeken = $this->connection->prepare('SELECT * FROM fotoboeken ORDER BY id DESC');
        $fotoboeken->execute([]);

        echo '<ul class="zonderbullets">';
        foreach ($fotoboeken->fetchAll() as $fotoboek)
        {
            $url = new Url('/photoalbum/' . $fotoboek['id']);
            $link = $url->getFriendly();
            echo '<li><h3><a href="' . $link . '">' . $fotoboek['naam'] . '</a></h3></li>';
        }
        echo '</ul>';

        $this->showPostPage();
    }
}