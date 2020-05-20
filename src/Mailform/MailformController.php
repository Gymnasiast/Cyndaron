<?php
declare (strict_types = 1);

namespace Cyndaron\Mailform;

use Cyndaron\Controller;
use Cyndaron\Error\IncompleteData;
use Cyndaron\Page;
use Cyndaron\Request\RequestParameters;
use Cyndaron\Setting;
use Cyndaron\User\UserLevel;
use Cyndaron\Util;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MailformController extends Controller
{
    protected array $postRoutes = [
        'process' => ['level' => UserLevel::ANONYMOUS, 'function' => 'process'],
        'process-ldbf' => ['level' => UserLevel::ANONYMOUS, 'function' => 'processLDBF'],
    ];

    public function checkCSRFToken(string $token): bool
    {
        if (in_array($this->action, ['process', 'process-ldbf']))
        {
            return true;
        }

        return parent::checkCSRFToken($token);
    }

    /**
     * @param RequestParameters $post
     * @return Response
     * @throws Exception
     */
    public function process(RequestParameters $post): Response
    {
        $id = $this->queryBits->getInt(2);
        if ($id < 1)
        {
            return new JsonResponse(['error' => 'Incorrect ID!'], Response::HTTP_BAD_REQUEST);
        }
        $form = Mailform::loadFromDatabase($id);

        try
        {
            if ($form === null)
            {
                throw new IncompleteData('Formulier niet gevonden!');
            }
            $this->processHelper($form, $post);
            $page = new Page('Formulier verstuurd', 'Het versturen is gelukt.');
            return new Response($page->render());
        }
        catch (Exception $e)
        {
            $page = new Page('Formulier versturen mislukt', $e->getMessage());
            return new Response($page->render());
        }
    }

    public function processLDBF(RequestParameters $post): Response
    {
        try
        {
            $this->processLDBFHelper($post);

            $page = new Page('Formulier verstuurd', 'Het versturen is gelukt.');
            return new Response($page->render());
        }

        catch (Exception $e)
        {
            $page = new Page('Formulier versturen mislukt', $e->getMessage());
            return new Response($page->render());
        }
    }

    private function processLDBFHelper(RequestParameters $post): bool
    {
        if ($post->isEmpty())
        {
            throw new IncompleteData('Ongeldig formulier.');
        }
        if (empty($post->getEmail('E-mailadres')))
        {
            throw new IncompleteData('U heeft uw e-mailadres niet of niet goed ingevuld. Klik op Vorige om het te herstellen.');
        }

        $mailForm = new MailFormLDBF();
        $mailForm->fillMailTemplate($post);
        $mailSent = $mailForm->sendMail($post->getEmail('E-mailadres'));

        if (!$mailSent)
        {
            throw new Exception('Wegens een technisch probleem is het versturen van de e-mail niet gelukt.');
        }

        return true;
    }

    /**
     * @param Mailform $form
     * @param RequestParameters $post
     * @return bool
     * @throws Exception
     */
    public function processHelper(Mailform $form, RequestParameters $post): bool
    {
        if ($form === null || !$form->name)
        {
            throw new IncompleteData('Ongeldig formulier.');
        }

        if ($form->sendConfirmation && empty($post->getEmail('E-mailadres')))
        {
            throw new IncompleteData('U heeft uw e-mailadres niet of niet goed ingevuld. Klik op Vorige om het te herstellen.');
        }

        if (strcasecmp($post->getAlphaNum('antispam'), $form->antiSpamAnswer) !== 0)
        {
            throw new IncompleteData('U heeft de antispamvraag niet of niet goed ingevuld. Klik op Vorige om het te herstellen.');
        }

        $mailBody = '';
        foreach ($post->getKeys() as $question)
        {
            if ($question !== 'antispam')
            {
                $answer = $post->getHTML($question);
                $mailBody .= $question . ': ' . strtr($answer, ['\\' => '']) . "\n";
            }
        }
        $recipient = $form->email;
        $subject = $form->name;
        $sender = $post->getEmail('E-mailadres');

        $fromAddress = Util::getNoreplyAddress();
        $fromName = html_entity_decode(Setting::get('organisation') ?: Setting::get('siteName'));
        $extraHeaders = 'From: ' . $fromAddress;

        if ($sender)
        {
            $extraHeaders .= "\r\n" . 'Reply-To: ' . $sender;
        }

        if (mail($recipient, $subject, $mailBody, $extraHeaders, "-f$fromAddress"))
        {
            if ($form->sendConfirmation && $sender)
            {
                $extraHeaders = sprintf('From: %s <%s>', $fromName, $fromAddress);
                $extraHeaders .= "\r\n" . 'Reply-To: ' . $recipient;
                mail($sender, 'Ontvangstbevestiging', $form->confirmationText, $extraHeaders, "-f$fromAddress");
            }
            return true;
        }

        throw new Exception('Wegens een technisch probleem is het versturen niet gelukt.');
    }
}