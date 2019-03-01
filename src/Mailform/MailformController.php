<?php
declare (strict_types = 1);

namespace Cyndaron\Mailform;

use Cyndaron\Controller;
use Cyndaron\Request;
use Cyndaron\Setting;
use Cyndaron\User\UserLevel;
use Cyndaron\VerwerkMailformulierPagina;

class MailformController extends Controller
{
    protected $minLevelPost = UserLevel::ANONYMOUS;

    public function routePost()
    {
        $id = (int)Request::getVar(2);
        try
        {
            $this->process($id);
        }
        catch (\Exception $e)
        {
            $page = new VerwerkMailformulierPagina('Formulier versturen mislukt', $e->getMessage());
            $page->showPrePage();
            $page->showBody();
            $page->showPostPage();
        }
        $page = new VerwerkMailformulierPagina('Formulier verstuurd', 'Het versturen is gelukt.');
        $page->showPrePage();
        $page->showBody();
        $page->showPostPage();

    }

    /**
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    private function process(int $id)
    {
        $form = Mailform::loadFromDatabase($id)->asArray();

        if ($form['naam'])
        {
            if ($form['stuur_bevestiging'] == true && empty(Request::geefPostVeilig('E-mailadres')))
            {
                throw new \Exception('U heeft uw e-mailadres niet of niet goed ingevuld. Klik op Vorige om het te herstellen.');
            }
            elseif (strtolower(Request::geefPostVeilig('antispam')) == strtolower($form['antispamantwoord']))
            {
                $mailBody = '';
                foreach (Request::geefPostArrayVeilig() as $question => $answer)
                {
                    if ($question !== 'antispam')
                    {
                        $mailBody .= $question . ': ' . strtr($answer, ['\\' => '']) . "\n";
                    }
                }
                $recipient = $form['mailadres'];
                $subject = $form['naam'];
                $sender = Request::geefPostVeilig('E-mailadres');

                $server = str_replace("www.", "", $_SERVER['HTTP_HOST']);
                $server = str_replace("http://", "", $server);
                $server = str_replace("https://", "", $server);
                $server = str_replace("/", "", $server);
                $extraHeaders = 'From: noreply@' . $server;

                if ($sender)
                {
                    $extraHeaders .= "\r\n" . 'Reply-To: ' . $sender;
                }

                if (mail($recipient, $subject, $mailBody, $extraHeaders))
                {
                    if ($form['stuur_bevestiging'] == true && $sender)
                    {
                        $extraHeaders = sprintf('From: %s <noreply@%s>', html_entity_decode(Setting::get('websitenaam')), $server);
                        $extraHeaders .= "\r\n" . 'Reply-To: ' . $recipient;
                        mail($sender, 'Ontvangstbevestiging', $form['tekst_bevestiging'], $extraHeaders);
                    }
                    return true;
                }
                else
                {
                    throw new \Exception('Wegens een technisch probleem is het versturen niet gelukt.');
                }
            }
            else
            {
                throw new \Exception('U heeft de antispamvraag niet of niet goed ingevuld. Klik op Vorige om het te herstellen.');
            }
        }
        else
        {
            throw new \Exception('Ongeldig formulier.');
        }
    }
}