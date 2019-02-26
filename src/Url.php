<?php
namespace Cyndaron;

class Url
{
    private $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function getFriendly(): string
    {
        if ($friendly = DBConnection::doQueryAndFetchOne('SELECT naam FROM friendlyurls WHERE doel=?', [$this->url]))
        {
            return '/' . $friendly;
        }
        else
        {
            return $this->url;
        }
    }

    public function getUnfriendly(): string
    {
        if ($unfriendly = DBConnection::doQueryAndFetchOne('SELECT doel FROM friendlyurls WHERE naam=?', [$this->url]))
        {
            return $unfriendly;
        }
        else
        {
            return $this->url;
        }
    }

    public function equals(Url $otherUrl): bool
    {
        $url1 = $this->getUnfriendly();
        $url2 = $otherUrl->getUnfriendly();

        if ($url1 == $url2)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function __toString(): string
    {
        return $this->url;
    }

    public function createFriendly(string $name)
    {
        if ($name == '' || $this->url == '')
            throw new \Exception('Cannot create friendly URL with no name or no URL!');
        DBConnection::doQuery('INSERT INTO friendlyurls(naam,doel) VALUES (?,?)', [$name, $this->url]);
    }

    public static function deleteFriendlyUrl(string $naam)
    {
        DBConnection::doQuery('DELETE FROM friendlyurls WHERE naam=?', [$naam]);
    }

    public function getPageTitle(): string
    {
        $link = trim($this->getUnfriendly(), '/');
        $linkParts = explode('/', $link);

        switch ($linkParts[0])
        {
            case 'sub':
                $sql = 'SELECT naam FROM subs WHERE id=?';
                break;
            case 'category':
                if ($linkParts[1] == 'fotoboeken')
                {
                    return 'Fotoalbums';
                }
                else
                {
                    $sql = 'SELECT naam FROM categorieen WHERE id=?';
                }
                break;
            case 'photoalbum':
                $sql = 'SELECT naam FROM fotoboeken WHERE id=?';
                break;
            default:
                return $link;
        }
        if ($name = DBConnection::doQueryAndFetchOne($sql, [$linkParts[1]]))
        {
            return $name;
        }
        elseif ($name = DBConnection::doQueryAndFetchOne('SELECT naam FROM friendlyurls WHERE doel=?', [$link]))
        {
            return $name;
        }
        else
        {
            return $link;
        }
    }
}