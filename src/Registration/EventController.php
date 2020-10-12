<?php
declare(strict_types=1);

namespace Cyndaron\Registration;

use Cyndaron\Routing\Controller;
use Cyndaron\DBConnection;
use Cyndaron\User\UserLevel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class EventController extends Controller
{
    protected array $getRoutes = [
        'getInfo' => ['level' => UserLevel::ANONYMOUS, 'function' => 'getEventInfo'],
        'register' => ['level' => UserLevel::ANONYMOUS, 'function' => 'register'],
        'viewRegistrations' => ['level' => UserLevel::ADMIN, 'function' => 'viewRegistrations'],
    ];

    protected function getEventInfo(): JsonResponse
    {
        $eventId = $this->queryBits->getInt(2);
        if ($eventId < 1)
        {
            return new JsonResponse(['error' => 'Incorrect ID!'], Response::HTTP_BAD_REQUEST);
        }
        $event = new Event($eventId);
        $event->load();
        $ticketTypes = DBConnection::doQueryAndFetchAll('SELECT * FROM registration_tickettypes WHERE eventId=? ORDER BY price DESC', [$eventId]);

        $answer = [
            'registrationCost0' => $event->registrationCost0,
            'registrationCost1' => $event->registrationCost1,
            'registrationCost2' => $event->registrationCost2,
            'lunchCost' => $event->lunchCost,
            'tickettypes' => $ticketTypes,
        ];

        return new JsonResponse($answer);
    }

    protected function register(): Response
    {
        $id = $this->queryBits->getInt(2);
        if ($id < 1)
        {
            return new JsonResponse(['error' => 'Incorrect ID!'], Response::HTTP_BAD_REQUEST);
        }
        $event = Event::loadFromDatabase($id);
        if ($event === null)
        {
            return new JsonResponse(['error' => 'Event does not exist!'], Response::HTTP_NOT_FOUND);
        }
        $page = new RegistrationPage($event);
        return new Response($page->render());
    }

    protected function viewRegistrations(): Response
    {
        $id = $this->queryBits->getInt(2);
        if ($id < 1)
        {
            return new JsonResponse(['error' => 'Incorrect ID!'], Response::HTTP_BAD_REQUEST);
        }
        $event = Event::loadFromDatabase($id);
        if ($event === null)
        {
            return new JsonResponse(['error' => 'Event does not exist!'], Response::HTTP_NOT_FOUND);
        }
        $page = new EventRegistrationOverviewPage($event);
        return new Response($page->render());
    }
}
