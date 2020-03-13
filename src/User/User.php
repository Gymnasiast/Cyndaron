<?php /** @noinspection PhpUnusedParameterInspection */
declare (strict_types = 1);

namespace Cyndaron\User;

use Cyndaron\DBConnection;
use Cyndaron\Model;
use Cyndaron\Setting;
use Cyndaron\Util;
use Exception;
use finfo;

class User extends Model
{
    public const TABLE = 'users';
    public const TABLE_FIELDS = ['username', 'password', 'email', 'level', 'firstName', 'tussenvoegsel', 'lastName', 'role', 'comments', 'avatar', 'hideFromMemberList', 'gender', 'street', 'houseNumber', 'houseNumberAddition', 'postalCode', 'city', 'dateOfBirth', 'notes'];

    public const AVATAR_DIR = 'uploads/user/avatar';

    public string $username;
    public string $password;
    public ?string $email;
    public int $level;
    public string $firstName;
    public string $tussenvoegsel;
    public string $lastName;
    public string $role;
    public string $comments = '';
    public string $avatar;
    public bool $hideFromMemberList;
    public ?string $gender = null;
    public ?string $street = null;
    public ?int $houseNumber = null;
    public ?string $houseNumberAddition = null;
    public ?string $postalCode = null;
    public ?string $city = null;
    public ?string $dateOfBirth = null;
    public string $notes = '';

    public const RESET_PASSWORD_MAIL_TEXT =
        '<p>U vroeg om een nieuw wachtwoord voor %s.</p>

<p>Uw nieuwe wachtwoord is: %s</p>';

    const MAIL_HEADERS = <<<EOT
MIME-Version: 1.0
Content-type: text/html; charset=utf-8
From: %s <%s>
EOT;

    public static function isAdmin(): bool
    {
        return isset($_SESSION['naam']) && $_SESSION['level'] >= 4;
    }

    public static function isLoggedIn(): bool
    {
        return (isset($_SESSION['naam']) && $_SESSION['level'] > 0);
    }

    public static function addNotification(string $content): void
    {
        $_SESSION['meldingen'][] = $content;
    }

    public static function getNotifications(): ?array
    {
        $return = @$_SESSION['meldingen'];
        $_SESSION['meldingen'] = null;
        return $return;
    }

    public static function getLevel(): int
    {
        return (int)@$_SESSION['level'];
    }

    public static function hasSufficientReadLevel(): bool
    {
        $minimumReadLevel = (int)Setting::get('minimumReadLevel');
        return (static::getLevel() >= $minimumReadLevel);
    }

    public function generatePassword(): string
    {
        $newPassword = Util::generatePassword();
        $this->password = password_hash($newPassword, PASSWORD_DEFAULT);
        return $newPassword;
    }

    public function resetPassword(): void
    {
        if ($this->id === null)
        {
            throw new Exception('ID is leeg!');
        }

        $newPassword = $this->generatePassword();

        $pdo = DBConnection::getPdo();
        $prep = $pdo->prepare('UPDATE users SET password=? WHERE id =?');
        $prep->execute([$this->password, $this->id]);

        $this->mailNewPassword($newPassword);
    }

    public function uploadNewAvatar(): void
    {
        Util::createDir(static::AVATAR_DIR);

        $tmpName = $_FILES['avatarFile']['tmp_name'];
        $buffer = file_get_contents($tmpName);
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($buffer);
        switch ($mimeType)
        {
            case 'image/gif':
                $avatarImg = imagecreatefromgif($tmpName);
                break;
            case 'image/jpeg':
                $avatarImg = imagecreatefromjpeg($tmpName);
                break;
            case 'image/png':
                $avatarImg = imagecreatefrompng($tmpName);
                break;
            default:
                die('Ongeldig bestandtype.');
        }

        $filename = static::AVATAR_DIR . "/{$this->id}.png";
        if (file_exists($filename))
        {
            unlink($filename);
        }

        imagepng($avatarImg, $filename);
        unlink($tmpName);

        $this->avatar = basename($filename);
        $this->save();
    }

    public function mailNewPassword(string $password): bool
    {
        $websiteName = Setting::get('siteName');
        $organisation = Setting::get('organisation') ?: Setting::get('siteName');
        $from = Util::getNoreplyAddress();

        return mail(
            $this->email,
            'Nieuw wachtwoord ingesteld',
            sprintf(self::RESET_PASSWORD_MAIL_TEXT, $websiteName, $password),
            sprintf(self::MAIL_HEADERS, $organisation, $from),
            "-f$from"
        );
    }

    public static function getCSRFToken($module, $action): string
    {
        if (empty($_SESSION['token']))
        {
            $_SESSION['token'] = [];
        }
        if (empty($_SESSION['token'][$module]))
        {
            $_SESSION['token'][$module] = [];
        }

        if (empty($_SESSION['token'][$module][$action]))
        {
            $_SESSION['token'][$module][$action] = Util::generateToken(16);
        }

        return $_SESSION['token'][$module][$action];
    }

    public static function checkToken($module, $action, $token): bool
    {
        if (!empty($token) &&
            !empty($_SESSION['token'][$module][$action]) &&
            $token === $_SESSION['token'][$module][$action])
        {
            return true;
        }
        return false;
    }

    public static function create(string $username, string $email, string $password, int $level, string $firstName, string $tussenvoegsel, string $lastName, string $role, string $comments, string $avatar, bool $hideFromMemberList): ?int
    {
        $password = password_hash($password, PASSWORD_DEFAULT);

        $user = new static(null);
        foreach(static::TABLE_FIELDS as $fieldname)
        {
            $user->$fieldname = $$fieldname;
        }
        if ($user->save())
        {
            $user->mailNewPassword($password);
            return $user->id;
        }
        else
        {
            throw new Exception(implode(',', DBConnection::errorInfo()));
        }
    }

    public static function login(string $identification, string $password)
    {
        if (strpos($identification, '@') !== false)
        {
            $query = 'SELECT * FROM users WHERE email=?';
            $updateQuery = 'UPDATE users SET password=? WHERE email=?';
        }
        else
        {
            $query = 'SELECT * FROM users WHERE username=?';
            $updateQuery = 'UPDATE users SET password=? WHERE username=?';
        }

        $userdata = DBConnection::doQueryAndFetchFirstRow($query, [$identification]);

        if (!$userdata)
        {
            throw new IncorrectCredentials('Onbekende gebruikersnaam of e-mailadres.');
        }

        $loginSucceeded = false;
        if (password_verify($password, $userdata['password']))
        {
            $loginSucceeded = true;

            if (password_needs_rehash($userdata['password'], PASSWORD_DEFAULT))
            {
                $password = password_hash($password, PASSWORD_DEFAULT);
                DBConnection::doQuery($updateQuery, [$password, $identification]);
            }
        }

        if (!$loginSucceeded)
        {
            throw new IncorrectCredentials('Verkeerd wachtwoord.');

        }

        $_SESSION['userId'] = $userdata['id'];
        $_SESSION['naam'] = $userdata['username'];
        $_SESSION['email'] = $userdata['email'];
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['level'] = $userdata['level'];

        static::addNotification('U bent ingelogd.');

        if ($_SESSION['redirect'])
        {
            $_SESSION['request'] = $_SESSION['redirect'];
            $_SESSION['redirect'] = null;
        }
        else
        {
            $_SESSION['request'] = '/';
        }
        header('Location: ' . $_SESSION['request']);
    }

    public static function logout(): void
    {
        session_start();
        session_destroy();

        session_start();
        static::addNotification('U bent afgemeld.');
        header('Location: /');
    }

    public function getFullName(): string
    {
        $ret = $this->firstName . ' ' . $this->tussenvoegsel;
        if (substr($this->tussenvoegsel, -1) !== "'")
            $ret .= ' ';
        $ret .= $this->lastName;

        return $ret;
    }

    public function getDateOfBirth(): ?\DateTimeImmutable
    {
        if ($this->dateOfBirth === null || $this->dateOfBirth === '')
        {
            return null;
        }

        return \DateTimeImmutable::createFromFormat('!Y-m-d', $this->dateOfBirth);
    }
}