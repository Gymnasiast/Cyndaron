<?php
namespace Cyndaron\Category;

use Cyndaron\DBConnection;
use Cyndaron\Util;

/**
 * Class CategoryModel
 * @todo: Omvormen tot echt model.
 */
class CategoryModel
{
    public static function nieuweCategorie($naam, bool $alleentitel = false, string $beschrijving = '', $categorieId = null)
    {
        if ($naam == '')
            throw new \Exception('Empty category name!');

        return DBConnection::doQuery('INSERT INTO categorieen(`naam`,`alleentitel`, `beschrijving`, `categorieid`) VALUES (?,?,?,?);', [$naam, (int)$alleentitel, $beschrijving, $categorieId]);
    }

    public static function wijzigCategorie($id, $naam = null, $alleentitel = null, $beschrijving = null, $categorieId = null)
    {
        if ($naam !== null)
        {
            DBConnection::doQueryAndFetchOne('UPDATE categorieen SET `naam`=? WHERE id=?', [$naam, $id]);
        }
        if ($alleentitel !== null)
        {
            DBConnection::doQueryAndFetchOne('UPDATE categorieen SET `alleentitel`=? WHERE id=?', [(int)(bool)$alleentitel, $id]);
        }
        if ($beschrijving !== null)
        {
            DBConnection::doQueryAndFetchOne('UPDATE categorieen SET `beschrijving`=? WHERE id=?', [$beschrijving, $id]);
        }
        DBConnection::doQueryAndFetchOne('UPDATE categorieen SET `categorieid`=? WHERE id=?', [$categorieId, $id]);
    }

    public static function verwijderCategorie($id)
    {
        DBConnection::doQueryAndFetchOne('DELETE FROM categorieen WHERE id=?;', [$id]);
    }
}