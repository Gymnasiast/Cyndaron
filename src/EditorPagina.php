<?php
namespace Cyndaron;

require_once __DIR__ . '/../check.php';
require_once __DIR__ . '/../functies.db.php';
require_once __DIR__ . '/../functies.url.php';

abstract class EditorPagina extends Pagina
{
    protected $id = null;
    protected $heeftTitel = true;
    protected $vorigeversie = null;
    protected $vvstring = '';
    protected $content;
    protected $titel;
    protected $type;
    protected $saveUrl;

    public function __construct()
    {
        $this->id = geefGetVeilig('id');
        $this->vorigeversie = geefGetVeilig('vorigeversie');
        $this->vvstring = $this->vorigeversie ? 'vorige' : '';
        $this->connectie = newPDO();

        $this->prepare();

        $_SESSION['referrer'] = htmlentities($_SERVER['HTTP_REFERER'], ENT_QUOTES, 'UTF-8');

        // Zorgen voor juiste codering
        $content = !empty($content) ? htmlentities($content, ENT_QUOTES, 'UTF-8') : '';

        if (empty($titel))
            $titel = '';

        $dir = dirname($_SERVER['PHP_SELF']);
        if ($dir == '/')
            $dir = '';

        parent::__construct('Editor');
        $this->maakNietDelen(true);
        $this->voegScriptToe('ckeditor/ckeditor.js');
        $this->voegScriptToe('sys/js/editor.js');
        $this->toonPrePagina();

        $unfriendlyUrl = 'toon' . $this->type . '.php?id=' . $this->id;
        $friendlyUrl = geefFriendlyUrl($unfriendlyUrl);
        if ($unfriendlyUrl == $friendlyUrl)
        {
            $friendlyUrl = "";
        }
        $saveUrl = sprintf($this->saveUrl, $this->id ? (string)$this->id : '');
        ?>

        <form name="bewerkartikel" method="post" action="<?=$saveUrl;?>" class="form-horizontal">

            <?php
            if ($this->heeftTitel === true):
                ?>
                <div class="form-group">
                    <label class="col-sm-2 control-label" for="titel">Titel: </label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" id="titel" name="titel" value="<?=$titel;?>" />
                    </div>
                </div>
                <?php
            endif;
            ?>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="friendlyUrl">Friendly URL: </label>
                <div class="col-sm-5">
                    <div class="input-group">
                        <span class="input-group-addon">http://<?=$_SERVER['HTTP_HOST'] . $dir;?>/</span>
                        <input type="text" class="form-control" id="friendlyUrl" name="friendlyUrl" value="<?=$friendlyUrl;?>" />
                    </div>

                </div>
            </div>

            <textarea class="ckeditor" name="artikel" rows="25" cols="125"><?=$content; ?></textarea>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="verwijzing">Interne link maken: </label>
                <div class="col-sm-5">
                    <select id="verwijzing" class="form-control form-control-inline">
                        <?php
                        $connectie = newPDO();
                        $sql = "
    SELECT * FROM (SELECT CONCAT('toonsub.php?id=', id) AS link, CONCAT('Statische pag.: ', naam) AS naam FROM subs ORDER BY naam ASC) AS twee
    UNION
    SELECT * FROM (SELECT CONCAT('tooncategorie.php?id=', id) AS link, CONCAT('Categorie: ', naam) AS naam FROM categorieen ORDER BY naam ASC) AS drie
    UNION
    SELECT * FROM (SELECT CONCAT('toonfotoboek.php?id=', id) AS link, CONCAT('Fotoboek: ', naam) AS naam FROM fotoboeken ORDER BY naam ASC) AS vijf;";

                        $links = $connectie->prepare($sql);
                        $links->execute();

                        foreach ($links->fetchAll() as $link)
                        {
                            echo '<option value="' . $link['link'] . '">' . $link['naam'] . '</option>';
                        }
                        ?>
                    </select>
                    <input type="button" id="plaklink" class="btn btn-default" value="Invoegen"/>
                </div>
            </div>

            <?php
            toonSpecifiekeKnoppen();
            ?>
            <input type="submit" value="Opslaan" class="btn btn-primary"/>
            <a role="button" class="btn btn-default" href="<?=$_SESSION['referrer'];?>">Annuleren</a>

        </form>
        <?php
        $this->toonPostPagina();

    }

    abstract protected function prepare();

    abstract protected function toonSpecifiekeKnoppen();
}