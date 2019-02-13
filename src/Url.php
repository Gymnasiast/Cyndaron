<?php
namespace Cyndaron;

class Url
{
    private $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function geefFriendly(): string
    {
        if ($friendly = DBConnection::geefEen('SELECT naam FROM friendlyurls WHERE doel=?', [$this->url]))
        {
            return $friendly;
        }
        else
        {
            return $this->url;
        }
    }

    public function geefUnfriendly(): string
    {
        if ($unfriendly = DBConnection::geefEen('SELECT doel FROM friendlyurls WHERE naam=?', [$this->url]))
        {
            return $unfriendly;
        }
        else
        {
            return $this->url;
        }
    }

    public function isGelijkAan(Url $andereUrl): bool
    {
        $url1 = $this->geefUnfriendly();
        $url2 = $andereUrl->geefUnfriendly();

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

    public function maakFriendly(string $name)
    {
        if ($name == '' || $this->url == '')
            throw new \Exception('Cannot create friendly URL with no name or no URL!');
        DBConnection::maakEen('INSERT INTO friendlyurls(naam,doel) VALUES (?,?)', [$name, $this->url]);
    }

    public static function verwijderFriendlyUrl(string $naam)
    {
        DBConnection::maakEen('DELETE FROM friendlyurls WHERE naam=?', [$naam]);
    }

    public function geefPaginanaam(): string
    {
        $link = $this->geefUnfriendly();
        $pos = strrpos($link, '/', -1);
        $laatstedeel = substr($link, $pos);
        $split = explode('?', $laatstedeel);
        $vars = @explode('&', $split[1]);
        $values = null;
        foreach ($vars as $var)
        {
            $temp = explode('=', $var);
            $values[$temp[0]] = @$temp[1];
        }
        switch ($split[0])
        {
            case 'toonsub.php':
                $sql = 'SELECT naam FROM subs WHERE id=?';
                break;
            case 'tooncategorie.php':
                if ($values['id'] == 'fotoboeken')
                {
                    return 'Fotoalbums';
                }
                else
                {
                    $sql = 'SELECT naam FROM categorieen WHERE id=?';
                }
                break;
            case 'toonfotoboek.php':
                $sql = 'SELECT naam FROM fotoboeken WHERE id=?';
                break;
            default:
                return $link;
        }
        if ($naam = DBConnection::geefEen($sql, [$values['id']]))
        {
            return $naam;
        }
        elseif ($naam = DBConnection::geefEen('SELECT naam FROM friendlyurls WHERE link=?', [$link]))
        {
            return $naam;
        }
        else
        {
            return $link;
        }
    }
}