<?php
namespace Cyndaron\Geelhoed\Member;

use Cyndaron\Controller;
use Cyndaron\Geelhoed\Hour\Hour;
use Cyndaron\Geelhoed\MemberGraduation;
use Cyndaron\Request\RequestParameters;
use Cyndaron\User\User;
use Cyndaron\User\UserLevel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MemberController extends Controller
{
    protected array $apiGetRoutes = [
        'get' => ['level' => UserLevel::ADMIN, 'function' => 'get'],
    ];
    protected array $apiPostRoutes = [
        'removeGraduation' => ['level' => UserLevel::ADMIN, 'function' => 'removeGraduation'],
        'save' => ['level' => UserLevel::ADMIN, 'function' => 'save']
    ];

    public function get(): JsonResponse
    {
        $id = $this->queryBits->getInt(2);
        if ($id < 1)
        {
            return new JsonResponse(['error' => 'Incorrect ID!'], Response::HTTP_BAD_REQUEST);
        }
        $ret = [];

        if ($member = Member::loadFromDatabase($id))
        {
            $ret = array_merge($member->asArray(), $member->getProfile()->asArray());
            foreach ($member->getHours() as $hour)
            {
                $ret["hour-{$hour->id}"] = true;
            }

            $list = [];
            foreach ($member->getMemberGraduations() as $memberGraduation)
            {
                $graduation = $memberGraduation->getGraduation();
                $description = "{$graduation->getSport()->name}: {$graduation->name} ({$memberGraduation->date})";
                $list[] = sprintf('<li id="member-graduation-%d">%s <a class="btn btn-sm btn-danger remove-member-graduation" data-id="%d"><span class="glyphicon glyphicon-trash"></span></a></li>', $memberGraduation->id, $description, $memberGraduation->id);
            }

            $ret['graduationList'] = implode($list);
        }

        return new JsonResponse($ret);
    }

    public function removeGraduation(): JsonResponse
    {
        $id = $this->queryBits->getInt(2);
        if ($id < 1)
        {
            return new JsonResponse(['error' => 'Incorrect ID!'], Response::HTTP_BAD_REQUEST);
        }
        MemberGraduation::deleteById($id);

        return new JsonResponse();
    }

    public function save(RequestParameters $post): JsonResponse
    {
        $memberId = $post->getInt('id');

        // Edit existing
        if ($memberId > 0)
        {
            $member = Member::loadFromDatabase($memberId);
            if ($member === null)
            {
                throw new \Exception('Member not found!');
            }
            $user = $member->getProfile();
        }
        else
        {
            $user = new User();
            $user->level = UserLevel::LOGGED_IN;
            $user->password = '';
            $member = new Member();
        }

        $user = $this->updateUserFields($user, $post);
        if (!$user->save())
        {
            return new JsonResponse(['error' => 'Error saving user record!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $member = $this->updateMemberFields($user, $member, $post);
        if (!$member->save())
        {
            return new JsonResponse(['error' => 'Error saving member record!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $newGraduationId = $post->getInt('new-graduation-id');
        $newGraduationDate = $post->getDate('new-graduation-date');
        if ($newGraduationId && $newGraduationDate)
        {
            $mg = new MemberGraduation();
            /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
            $mg->memberId = $member->id;
            $mg->graduationId = $newGraduationId;
            $mg->date = $newGraduationDate;
            $mg->save();
        }

        $hours = [];
        foreach (Hour::fetchAll() as $hour)
        {
            if ($post->getBool("hour-{$hour->id}"))
            {
                $hours[] = $hour;
            }
        }
        $member->setHours($hours);

        return new JsonResponse();
    }

    /**
     * @param User $user
     * @param RequestParameters $post
     * @return User
     */
    private function updateUserFields(User $user, RequestParameters $post): string
    {
        $user->username = $post->getSimpleString('username');
        $user->email = $post->getEmail('email') ?: null;
        $user->firstName = $post->getSimpleString('firstName');
        $user->tussenvoegsel = $post->getTussenvoegsel('tussenvoegsel');
        $user->lastName = $post->getSimpleString('lastName');
        $user->role = $post->getSimpleString('role');
        $user->comments = $post->getHTML('comments');
        // Skipping avatar, hideFromMemberList
        $user->gender = $post->getSimpleString('gender');
        $user->street = $post->getSimpleString('street');
        $user->houseNumber = $post->getInt('houseNumber');
        $user->houseNumberAddition = $post->getSimpleString('houseNumberAddition');
        $user->postalCode = $post->getPostcode('postalCode');
        $user->city = $post->getSimpleString('city');
        $user->dateOfBirth = $post->getDate('dateOfBirth');
        $user->notes = $post->getHTML('notes');

        return $user;
    }

    /**
     * @param $user
     * @param $member
     * @param RequestParameters $post
     * @return Member
     */
    private function updateMemberFields(User $user, Member $member, RequestParameters $post): Member
    {
        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $member->userId = $user->id;
        $member->parentEmail = $post->getEmail('parentEmail');
        $member->phoneNumbers = $post->getSimpleString('phoneNumber');
        $member->isContestant = $post->getBool('isContestant');
        $member->paymentMethod = $post->getSimpleString('paymentMethod');
        $member->iban = $post->getSimpleString('iban');
        $member->paymentProblem = $post->getBool('paymentProblem');
        $member->paymentProblemNote = $post->getHTML('paymentProblem');
        $member->freeParticipation = $post->getBool('freeParticipation');
        $member->temporaryStop = $post->getBool('temporaryStop');
        if ($joinedAt = $post->getDate('joined'))
        {
            $member->joinedAt = $joinedAt;
        }
        $member->jbnNumber = $post->getAlphaNum('jbnNumber');
        $member->jbnNumberLocation = $post->getSimpleString('jbnNumberLocation');

        return $member;
    }
}
