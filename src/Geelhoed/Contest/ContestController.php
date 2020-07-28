<?php
namespace Cyndaron\Geelhoed\Contest;

use Cyndaron\Controller;
use Cyndaron\Geelhoed\Member\Member;
use Cyndaron\Geelhoed\PageManagerTabs;
use Cyndaron\Geelhoed\Sport;
use Cyndaron\Page;
use Cyndaron\PlainTextMail;
use Cyndaron\Request\RequestParameters;
use Cyndaron\Setting;
use Cyndaron\Template\ViewHelpers;
use Cyndaron\User\User;
use Cyndaron\User\UserLevel;
use Cyndaron\Util;
use PhpOffice\PhpSpreadsheet\Shared\Date as PHPSpreadsheetDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Safe\DateTime;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use function Safe\error_log;
use function Safe\sprintf;
use function Safe\strtotime;

final class ContestController extends Controller
{
    protected array $getRoutes = [
        'contestantsList' => ['level' => UserLevel::ADMIN, 'right' => Contest::RIGHT_MANAGE, 'function' => 'contestantsList'],
        'contestantsListExcel' => ['level' => UserLevel::ADMIN, 'right' => Contest::RIGHT_MANAGE, 'function' => 'contestantsListExcel'],
        'editSubscription' => ['level' => UserLevel::LOGGED_IN, 'function' => 'editSubscriptionPage'],
        'manageOverview' => ['level' => UserLevel::ADMIN, 'right' => Contest::RIGHT_MANAGE, 'function' => 'manageOverview'],
        'myContests' => ['level' => UserLevel::LOGGED_IN, 'function' => 'myContests'],
        'overview' => ['level' => UserLevel::ANONYMOUS, 'function' => 'overview'],
        'parentAccounts' => ['level' => UserLevel::ADMIN, 'right' => Contest::RIGHT_MANAGE, 'function' => 'parentAccounts'],
        'payFullDue' => ['level' => UserLevel::LOGGED_IN, 'function' => 'payFullDue'],
        'subscriptionList' => ['level' => UserLevel::ADMIN, 'right' => Contest::RIGHT_MANAGE, 'function' => 'subscriptionList'],
        'subscriptionListExcel' => ['level' => UserLevel::ADMIN, 'right' => Contest::RIGHT_MANAGE, 'function' => 'subscriptionListExcel'],
        'view' => ['level' => UserLevel::ANONYMOUS, 'function' => 'view'],
    ];

    protected array $postRoutes = [
        'addAttachment' => ['level' => UserLevel::ADMIN, 'right' => Contest::RIGHT_MANAGE, 'function' => 'addAttachment'],
        'deleteAttachment' => ['level' => UserLevel::ADMIN, 'right' => Contest::RIGHT_MANAGE, 'function' => 'deleteAttachment'],
        'deleteDate' => ['level' => UserLevel::ADMIN, 'right' => Contest::RIGHT_MANAGE, 'function' => 'deleteDate'],
        'editSubscription' => ['level' => UserLevel::LOGGED_IN, 'function' => 'editSubscription'],
        'subscribe' => ['level' => UserLevel::LOGGED_IN, 'function' => 'subscribe'],
    ];

    protected array $apiPostRoutes = [
        'addDate' => ['level' => UserLevel::ADMIN, 'right' => Contest::RIGHT_MANAGE, 'function' => 'addDate'],
        'edit' => ['level' => UserLevel::ADMIN, 'right' => Contest::RIGHT_MANAGE, 'function' => 'createOrEdit'],
        'delete' => ['level' => UserLevel::ADMIN, 'right' => Contest::RIGHT_MANAGE, 'function' => 'delete'],
        'mollieWebhook' => ['level' => UserLevel::ANONYMOUS, 'function' => 'mollieWebhook'],
        'updatePaymentStatus' => ['level' => UserLevel::ADMIN, 'right' => Contest::RIGHT_MANAGE, 'function' => 'updatePaymentStatus'],
        'removeSubscription' => ['level' => UserLevel::ADMIN, 'right' => Contest::RIGHT_MANAGE, 'function' => 'removeSubscription'],
    ];

    public function checkCSRFToken(string $token): bool
    {
        // Mollie webhook does not need a CSRF token.
        // It only notifies us of a status change and it's up to us to check with them what that status is.
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $this->action === 'mollieWebhook')
        {
            return true;
        }

        return parent::checkCSRFToken($token);
    }

    public function overview(): Response
    {
        $page = new OverviewPage();
        return new Response($page->render());
    }

    public function view(): Response
    {
        $id = $this->queryBits->getInt(2);
        if ($id < 1)
        {
            return new JsonResponse(['error' => 'Incorrect ID!'], Response::HTTP_BAD_REQUEST);
        }
        $contest = Contest::loadFromDatabase($id);
        if ($contest === null)
        {
            $page = new Page('Onbekende wedstrijd', 'Kon de wedstrijd niet vinden');
            return new Response($page->render(), Response::HTTP_NOT_FOUND);
        }

        $page = new ContestViewPage($contest);
        return new Response($page->render());
    }

    public function subscribe(RequestParameters $post): Response
    {
        $id = $this->queryBits->getInt(2);
        if ($id < 1)
        {
            return new JsonResponse(['error' => 'Incorrect ID!'], Response::HTTP_BAD_REQUEST);
        }
        $contest = Contest::loadFromDatabase($id);
        if ($contest === null)
        {
            $page = new Page('Onbekende wedstrijd', 'Kon de wedstrijd niet vinden');
            return new Response($page->render(), Response::HTTP_NOT_FOUND);
        }

        $memberId = $post->getInt('memberId');
        $member = Member::loadFromDatabase($memberId);
        if ($member === null)
        {
            $page = new Page('Onbekend lid', 'Kon het lid niet vinden.');
            return new Response($page->render(), Response::HTTP_NOT_FOUND);
        }
        $controlledMemberIds = array_map(static function(Member $member) { return $member->id; }, Member::fetchAllByLoggedInUser());
        if (!in_array($memberId, $controlledMemberIds, true))
        {
            $page = new Page('Fout', 'U mag dit lid niet beheren.');
            return new Response($page->render(), Response::HTTP_FORBIDDEN);
        }

        assert($contest->id !== null);
        assert($member->id !== null);
        $contestMember = new ContestMember();
        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $contestMember->contestId = $contest->id;
        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $contestMember->memberId = $member->id;
        $contestMember->graduationId = $post->getInt('graduationId');
        $contestMember->weight = $post->getInt('weight');
        $contestMember->comments = $post->getSimpleString('comments');
        $contestMember->isPaid = false;
        if (!$contestMember->save())
        {
            $page = new Page('Fout bij inschrijven', 'Kon de inschrijving niet opslaan!');
            return new Response($page->render(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

//        // No need to pay, so just redirect.
//        if ($contest->price <= 0.00)
//        {
//            return new RedirectResponse("/contest/view/{$contest->id}");
//        }
//
//        try
//        {
//            $baseUrl = "https://{$_SERVER['HTTP_HOST']}";
//            $redirectUrl = "{$baseUrl}/contest/view/{$contest->id}";
//            $response = $this->doMollieTransaction([$contestMember], "Inschrijving {$contest->name}", $contest->price, $redirectUrl);
//        }
//        catch (\Exception $e)
//        {
//            User::addNotification('Je inschrijving is opgeslagen, maar de betaling is mislukt!');
//            $response = new RedirectResponse("/contest/view/{$contest->id}");
//        }

//        return $response;
        User::addNotification('Let op: de inschrijving is pas definitief wanneer u heeft betaald.');
        return new RedirectResponse("/contest/view/{$contest->id}");
    }

    /**
     * @param ContestMember[] $contestMembers
     * @param string $description
     * @param float $price
     * @param string $redirectUrl
     * @throws \Cyndaron\Error\ImproperSubclassing
     * @throws \Mollie\Api\Exceptions\ApiException
     * @return Response
     */
    private function doMollieTransaction(array $contestMembers, string $description, float $price, string $redirectUrl): Response
    {
        $apiKey = Setting::get('mollieApiKey');
        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setApiKey($apiKey);

        $formattedAmount = number_format($price, 2, '.', '');
        $baseUrl = "https://{$_SERVER['HTTP_HOST']}";

        $payment = $mollie->payments->create([
            'amount' => [
                'currency' => 'EUR',
                'value' => $formattedAmount,
            ],
            'description' => $description,
            'redirectUrl' => $redirectUrl,
            'webhookUrl' => "{$baseUrl}/api/contest/mollieWebhook",
        ]);

        if (empty($payment->id))
        {
            $page = new Page('Fout bij inschrijven', 'Betaling niet gevonden!');
            return new Response($page->render(), Response::HTTP_NOT_FOUND);
        }

        foreach ($contestMembers as $contestMember)
        {
            $contestMember->molliePaymentId = $payment->id;
            if (!$contestMember->save())
            {
                $page = new Page('Fout bij inschrijven', 'Kon de betalings-ID niet opslaan!');
                return new Response($page->render(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $redirectUrl = $payment->getCheckoutUrl();
        if ($redirectUrl === null)
        {
            User::addNotification('Bedankt voor je inschrijving! Helaas lukte het doorsturen naar de betaalpagina niet.');
            return new RedirectResponse('/');
        }

        User::addNotification('Bedankt voor de betaling! Het kan even duren voordat deze geregistreerd is.');
        return new RedirectResponse($redirectUrl);
    }

    public function mollieWebhook(RequestParameters $post): Response
    {
        $apiKey = Setting::get('mollieApiKey');
        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setApiKey($apiKey);

        $id = $post->getUnfilteredString('id');
        $payment = $mollie->payments->get($id);
        $contestMembers = ContestMember::fetchAll(['molliePaymentId = ?'], [$id]);

        if (count($contestMembers) === 0)
        {
            $message = sprintf('Poging tot updaten van transactie met id %s mislukt.', $id);
            $message .= ' $contestMembers is leeg.';

            /** @noinspection ForgottenDebugOutputInspection */
            error_log($message);
            return new JsonResponse(['error' => 'Could not find payment!'], Response::HTTP_NOT_FOUND);
        }

        $savesSucceeded = true;
        $paidStatus = false;

        if ($payment->isPaid() && !$payment->hasRefunds() && !$payment->hasChargebacks())
        {
            $paidStatus = true;
        }

        foreach ($contestMembers as $contestMember)
        {
            $contestMember->isPaid = $paidStatus;
            $savesSucceeded = $savesSucceeded && $contestMember->save();
        }

        if (!$savesSucceeded)
        {
            return new JsonResponse(['error' => 'Could not update payment information for all subscriptions!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse();
    }

    public function manageOverview(): Response
    {
        $contests = PageManagerTabs::contestsTab();
        $page = new Page('Overzicht wedstrijden', $contests);
        $page->addScript('/src/Geelhoed/Contest/js/ContestManager.js');
        return new Response($page->render());
    }

    public function subscriptionList(): Response
    {
        $id = $this->queryBits->getInt(2);
        if ($id < 1)
        {
            return new Response('Incorrect ID!', Response::HTTP_BAD_REQUEST);
        }
        $contest = Contest::loadFromDatabase($id);
        if ($contest === null)
        {
            return new Response('Kon de wedstrijd niet vinden!', Response::HTTP_NOT_FOUND);
        }
        $page = new SubscriptionListPage($contest);
        return new Response($page->render());
    }

    public function subScriptionListExcel(): Response
    {
        $id = $this->queryBits->getInt(2);
        if ($id < 1)
        {
            return new JsonResponse(['error' => 'Incorrect ID!'], Response::HTTP_BAD_REQUEST);
        }
        $contest = Contest::loadFromDatabase($id);
        if ($contest === null)
        {
            throw new \Exception('Wedstrijd niet gevonden!');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['Naam', 'Geslacht', 'Adres', 'Postcode', 'Woonplaats', 'Geboortedatum', 'Leeftijd', 'Band', 'Gewicht', 'JBN-nummer', 'Betaald', 'Transactie-ID', 'Opmerkingen'];
        foreach ($headers as $key => $value)
        {
            $column = chr(ord('A') + $key);
            $sheet->setCellValue("{$column}1", $value);
        }

        $row = 2;
        foreach ($contest->getContestMembers() as $contestMember)
        {
            $member = $contestMember->getMember();
            $profile = $member->getProfile();

            $sheet->setCellValue("A{$row}", $profile->getFullName());
            $sheet->setCellValue("B{$row}", $profile->getGenderDisplay());
            $sheet->setCellValue("C{$row}", "{$profile->street} {$profile->houseNumber} {$profile->houseNumberAddition}");
            $sheet->setCellValue("D{$row}", $profile->postalCode);
            $sheet->setCellValue("E{$row}", $profile->city);
            if ($profile->dateOfBirth !== null)
            {
                $dobExcel = PHPSpreadsheetDate::PHPToExcel(date($profile->dateOfBirth));
                $sheet->setCellValue("F{$row}", $dobExcel);
                $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('dd-mm-yyyy');
            }
            else
            {
                $sheet->setCellValue("F{$row}", '');
            }

            $firstDate = $contest->getFirstDate();
            $contestDateObject = null;
            if ($firstDate !== null)
            {
                $contestDateObject = new DateTime($firstDate);
            }

            $sheet->setCellValue("G{$row}", $profile->getAge($contestDateObject));
            $sheet->setCellValue("H{$row}", $contestMember->getGraduation()->name);
            $sheet->setCellValue("I{$row}", $contestMember->weight);
            $sheet->setCellValue("J{$row}", $member->jbnNumber);
            $sheet->setCellValue("K{$row}", ViewHelpers::boolToText($contestMember->isPaid));
            $sheet->setCellValue("L{$row}", $contestMember->molliePaymentId ?? '');
            $sheet->setCellValue("M{$row}", $contestMember->comments);

            $row++;
        }
        for ($i = 0, $numHeaders = count($headers); $i < $numHeaders; $i++)
        {
            $column = chr(ord('A') + $i);
            $dimension = $sheet->getColumnDimension($column);
            $dimension->setAutoSize(true);
        }

        $firstDate = $contest->getFirstDate();
        $date = $firstDate !== null ? date('Y-m-d', strtotime($firstDate)) : 'onbekende datum';
        $httpHeaders = Util::spreadsheetHeadersForFilename("Deelnemers {$contest->name} ($date).xlsx");

        return new Response(ViewHelpers::spreadsheetToString($spreadsheet), Response::HTTP_OK, $httpHeaders);
    }

    public function removeSubscription(RequestParameters $post): JsonResponse
    {
        $id = $post->getInt('id');
        $contestMember = ContestMember::loadFromDatabase($id);
        if ($contestMember === null)
        {
            return new JsonResponse(['error' => 'Contest member does not exist!'], Response::HTTP_NOT_FOUND);
        }

        $contestMember->delete();

        return new JsonResponse();
    }

    public function delete(RequestParameters $post): JsonResponse
    {
        $id = $post->getInt('id');
        $contest = Contest::loadFromDatabase($id);
        if ($contest === null)
        {
            return new JsonResponse(['error' => 'Contest does not exist!'], Response::HTTP_NOT_FOUND);
        }

        $contest->delete();

        return new JsonResponse();
    }

    public function createOrEdit(RequestParameters $post): JsonResponse
    {
        $id = $post->getInt('id');
        if ($id > 0)
        {
            $contest = Contest::loadFromDatabase($id);
            if ($contest === null)
            {
                return new JsonResponse(['error' => 'Contest does not exist!'], Response::HTTP_NOT_FOUND);
            }
        }
        else
        {
            $contest = new Contest();
        }

        $contest->name = $post->getHTML('name');
        $contest->description = $post->getHTML('description');
        $contest->location = $post->getHTML('location');
        $contest->sportId = $post->getInt('sportId');
        $contest->registrationDeadline = $post->getDate('registrationDeadline');
        $contest->registrationChangeDeadline = $post->getDate('registrationChangeDeadline');
        $contest->price = $post->getFloat('price');
        if (!$contest->save())
        {
            return new JsonResponse(['error' => 'Could not save contest!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse();
    }

    public function updatePaymentStatus(RequestParameters $post): JsonResponse
    {
        $id = $post->getInt('id');
        $contestMember = ContestMember::loadFromDatabase($id);
        if ($contestMember === null)
        {
            return new JsonResponse(['error' => 'Contest member does not exist!'], Response::HTTP_NOT_FOUND);
        }

        $contestMember->isPaid = $post->getBool('isPaid');
        $contestMember->save();

        return new JsonResponse();
    }

    public function contestantsList(): Response
    {
        $page = new ContestantsListPage();
        return new Response($page->render());
    }

    public function contestantsListExcel(): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $headers = ['Naam', 'Geslacht', 'Adres', 'Postcode', 'Woonplaats', 'Geboortedatum', 'Banden', 'JBN-nummer'];
        foreach ($headers as $key => $value)
        {
            $column = chr(ord('A') + $key);
            $sheet->setCellValue("{$column}1", $value);
        }

        $contestants = Member::fetchAll(['isContestant = 1'], [], 'ORDER BY lastName,tussenvoegsel,firstName');
        $sports = Sport::fetchAll();
        $row = 2;
        foreach ($contestants as $member)
        {
            $profile = $member->getProfile();

            $sheet->setCellValue("A{$row}", $profile->getFullName());
            $sheet->setCellValue("B{$row}", $profile->getGenderDisplay());
            $sheet->setCellValue("C{$row}", "{$profile->street} {$profile->houseNumber} {$profile->houseNumberAddition}");
            $sheet->setCellValue("D{$row}", $profile->postalCode);
            $sheet->setCellValue("E{$row}", $profile->city);
            if ($profile->dateOfBirth !== null)
            {
                $dobExcel = PHPSpreadsheetDate::PHPToExcel(date($profile->dateOfBirth));
                $sheet->setCellValue("F{$row}", $dobExcel);
                $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('dd-mm-yyyy');
            }
            else
            {
                $sheet->setCellValue("F{$row}", '');
            }
            $graduations = [];
            foreach ($sports as $sport)
            {
                $highest = $member->getHighestGraduation($sport);
                if ($highest !== null)
                {
                    $graduations[] = "{$sport->name}: {$highest->name}";
                }
            }
            $sheet->setCellValue("G{$row}", implode("\r\n", $graduations));
            $sheet->setCellValue("H{$row}", $member->jbnNumber);

            $row++;
        }
        for ($i = 0, $numHeaders = count($headers); $i < $numHeaders; $i++)
        {
            $column = chr(ord('A') + $i);
            $dimension = $sheet->getColumnDimension($column);
            $dimension->setAutoSize(true);
        }

        $date = date('Y-m-d');
        $httpHeaders = Util::spreadsheetHeadersForFilename("Wedstrijdjudoka's (uitvoer {$date}).xlsx");

        return new Response(ViewHelpers::spreadsheetToString($spreadsheet), Response::HTTP_OK, $httpHeaders);
    }

    public function myContests(): Response
    {
        $page = new MyContestsPage();
        return new Response($page->render());
    }

    public function payFullDue(): Response
    {
        $user = User::fromSession();
        assert($user !== null);

        [$due, $contestMembers] = Contest::getTotalDue($user);
        if ($due === 0.00)
        {
            return new Response('Er staan geen betalingen open.');
        }

        try
        {
            $redirectUrl = "https://{$_SERVER['HTTP_HOST']}/contest/myContests";
            $response = $this->doMollieTransaction($contestMembers, 'Inschrijving wedstrijdjudo Sportschool Geelhoed', $due, $redirectUrl);
        }
        catch (\Exception $e)
        {
            User::addNotification('De betaling is mislukt!');
            $response = new RedirectResponse("/contest/myContests");
            /** @noinspection ForgottenDebugOutputInspection */
            error_log($e->getMessage());
        }

        return $response;
    }

    public function addAttachment(): Response
    {
        $id = $this->queryBits->getInt(2);
        if ($id < 1)
        {
            return new Response('Incorrect ID!', Response::HTTP_BAD_REQUEST);
        }
        $contest = Contest::loadFromDatabase($id);
        if ($contest === null)
        {
            return new Response('Wedstrijd bestaat niet!', Response::HTTP_NOT_FOUND);
        }

        $dir = Util::UPLOAD_DIR . '/contest/' . $contest->id . '/attachments';
        Util::ensureDirectoryExists($dir);

        $filteredParams = new RequestParameters($_FILES['newFile']);
        $filename = $dir . '/' . basename($filteredParams->getFilename('name'));
        if (move_uploaded_file($_FILES['newFile']['tmp_name'], $filename))
        {
            User::addNotification('Bijlage geüpload');
        }
        else
        {
            User::addNotification('Bijlage kon niet naar de uploadmap worden verplaatst.');
        }

        return new RedirectResponse('/contest/view/' . $contest->id);
    }

    protected function deleteAttachment(RequestParameters $post): Response
    {
        $id = $this->queryBits->getInt(2);
        if ($id < 1)
        {
            return new Response('Incorrect ID!', Response::HTTP_BAD_REQUEST);
        }
        $contest = Contest::loadFromDatabase($id);
        if ($contest === null)
        {
            return new Response('Wedstrijd bestaat niet!', Response::HTTP_NOT_FOUND);
        }

        $dir = Util::UPLOAD_DIR . '/contest/' . $contest->id . '/attachments';
        $filename = $post->getFilename('filename');
        $fullPath = "$dir/$filename";
        if (file_exists($fullPath))
        {
            if (Util::deleteFile($fullPath))
            {
                User::addNotification('Bestand verwijderd.');
            }
            else
            {
                User::addNotification('Bestand kon niet worden verwijderd.');
            }
        }
        else
        {
            User::addNotification('Bestand bestaat niet.');
        }

        return new RedirectResponse('/contest/view/' . $contest->id);
    }

    public function addDate(RequestParameters $post): JsonResponse
    {
        $contestId = $post->getInt('contestId');
        if ($contestId < 1)
        {
            return new JsonResponse(['error' => 'Incorrect ID!'], Response::HTTP_BAD_REQUEST);
        }
        $contest = Contest::loadFromDatabase($contestId);
        if ($contest === null)
        {
            return new JsonResponse(['error' => 'Wedstrijd bestaat niet!'], Response::HTTP_NOT_FOUND);
        }

        $contestDate = new ContestDate();
        $contestDate->contestId = $contestId;
        $contestDate->datetime = $post->getDate('date') . ' ' . $post->getDate('time') . ':00';
        $contestDate->save();

        $contestDateId = $contestDate->id;
        if ($contestDateId === null)
        {
            return new JsonResponse(['error' => 'Kon de datum niet opslaan!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $classes = ContestClass::fetchAll();
        foreach ($classes as $class)
        {
            if ($post->getBool('class-' . $class->id))
            {
                $contestDate->addClass($class);
            }
        }

        return new JsonResponse(['status' => 'ok']);
    }

    public function deleteDate(): Response
    {
        $contestDateId = $this->queryBits->getInt(2);
        if ($contestDateId < 1)
        {
            return new Response('Incorrect ID!', Response::HTTP_BAD_REQUEST);
        }
        $contestDate = ContestDate::loadFromDatabase($contestDateId);
        if ($contestDate === null)
        {
            return new Response('Wedstrijddatum bestaat niet!', Response::HTTP_NOT_FOUND);
        }

        $contest = $contestDate->getContest();
        $contestDate->delete();
        return new RedirectResponse('/contest/view/' . $contest->id);
    }

    public function editSubscription(RequestParameters $post): Response
    {
        $id = $this->queryBits->getInt(2);
        $subscription = ContestMember::loadFromDatabase($id);
        if ($subscription === null)
        {
            return new Response('Record bestaat niet!', Response::HTTP_NOT_FOUND);
        }

        $user = User::fromSession();
        if ($user === null)
        {
            return new Response('U moet ingelogd zijn!', Response::HTTP_UNAUTHORIZED);
        }
        if (!$user->hasRight(Contest::RIGHT_MANAGE))
        {
            $memberId = $subscription->getMember()->id;
            $controlledMemberIds = array_map(static function(Member $member) { return $member->id; }, Member::fetchAllByLoggedInUser());
            if (!in_array($memberId, $controlledMemberIds, true))
            {
                return new Response('U mag deze judoka niet beheren!', Response::HTTP_FORBIDDEN);
            }
        }

        if (!$subscription->canBeChanged($user))
        {
            return new Response('De deadline voor aanpassingen is verlopen!', Response::HTTP_BAD_REQUEST);
        }

        $subscription->weight = $post->getInt('weight');
        $subscription->graduationId = $post->getInt('graduationId');
        if ($subscription->save())
        {
            User::addNotification('Wijzigingen opgeslagen.');
            $mailText = "{{ {$subscription->getMember()->getProfile()->getFullName()} heeft zijn/haar inschrijving voor {$subscription->getContest()->name} gewijzigd. Het gewicht is nu {$subscription->weight} kg en de graduatie is: {$subscription->getGraduation()->name}.";
            $to = Setting::get('geelhoed_contestMaintainerMail');
            $mail = new PlainTextMail($to, 'Wijziging inschrijving', $mailText);
            $mail->send();
        }

        return new RedirectResponse('/contest/myContests');
    }

    public function editSubscriptionPage(): Response
    {
        $id = $this->queryBits->getInt(2);
        $subscription = ContestMember::loadFromDatabase($id);
        if ($subscription === null)
        {
            return new Response('Record bestaat niet!', Response::HTTP_NOT_FOUND);
        }

        $user = User::fromSession();
        if ($user === null)
        {
            return new Response('U moet ingelogd zijn!', Response::HTTP_UNAUTHORIZED);
        }
        if (!$user->hasRight(Contest::RIGHT_MANAGE))
        {
            $memberId = $subscription->getMember()->id;
            $controlledMemberIds = array_map(static function(Member $member) { return $member->id; }, Member::fetchAllByLoggedInUser());
            if (!in_array($memberId, $controlledMemberIds, true))
            {
                return new Response('U mag deze judoka niet beheren!', Response::HTTP_FORBIDDEN);
            }
        }

        if (!$subscription->canBeChanged($user))
        {
            return new Response('De deadline voor aanpassingen is verlopen!', Response::HTTP_BAD_REQUEST);
        }

        $page = new EditSubscriptionPage($subscription);
        return new Response($page->render());
    }

    public function parentAccounts(): Response
    {
        $page = new ParentAccountsPage();
        return new Response($page->render());
    }
}
