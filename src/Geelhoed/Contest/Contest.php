<?php
namespace Cyndaron\Geelhoed\Contest;

use Cyndaron\Geelhoed\Member\Member;
use Cyndaron\Geelhoed\Sport;
use Cyndaron\Model;

class Contest extends Model
{
    public const TABLE = 'geelhoed_contests';
    public const TABLE_FIELDS = ['name', 'description', 'location', 'sportId', 'date', 'registrationDeadline', 'price'];

    public const RIGHT = 'geelhoed_manage_contests';

    public string $name = '';
    public string $description = '';
    public string $location = '';
    public int $sportId = 0;
    public string $date = '';
    public string $registrationDeadline = '';
    public float $price;

    /**
     * @return ContestMember[]
     */
    public function getContestMembers(): array
    {
        return ContestMember::fetchAll(['contestId = ?'], [$this->id]);
    }

    public function getSport(): Sport
    {
        return Sport::loadFromDatabase($this->sportId);
    }

    public function hasMember(Member $member): bool
    {
        foreach ($this->getContestMembers() as $contestMember)
        {
            if ($contestMember->getMember()->id === $member->id)
                return true;
        }

        return false;
    }
}