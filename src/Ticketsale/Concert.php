<?php
declare (strict_types = 1);

namespace Cyndaron\Ticketsale;

use Cyndaron\DBConnection;
use Cyndaron\Model;
use Exception;

class Concert extends Model
{
    const TABLE = 'ticketsale_concerts';
    const TABLE_FIELDS = ['name', 'openForSales', 'description', 'descriptionWhenClosed', 'deliveryCost', 'forcedDelivery', 'hasReservedSeats', 'reservedSeatCharge', 'reservedSeatsAreSoldOut', 'numFreeSeats', 'numReservedSeats'];

    public $name = '';
    public $openForSales = true;
    public $description = '';
    public $descriptionWhenClosed = '';
    public $deliveryCost = 1.50;
    public $forcedDelivery = true;
    public $hasReservedSeats = true;
    public $reservedSeatCharge = 5.00;
    public $reservedSeatsAreSoldOut = false;
    public $numFreeSeats = 250;
    public $numReservedSeats = 270;

    /**
     * @param int $numTickets
     * @param $orderId
     * @return array|null Which seats were reserved, if there were enough, null otherwise
     * @throws Exception
     */
    public function reserveSeats(int $orderId, int $numTickets): ?array
    {
        if (!$this->id)
        {
            throw new Exception('No ID!');
        }

        $foundEnoughSeats = false;
        $reservedSeats = [];

        $reservedSeatsPerOrder = DBConnection::doQueryAndFetchAll('SELECT * FROM ticketsale_reservedseats WHERE orderId IN (SELECT id FROM ticketsale_orders WHERE concertId=?)', [$this->id]);
        foreach ($reservedSeatsPerOrder as $reservedSeatsForThisOrder)
        {
            for ($i = $reservedSeatsForThisOrder['eerste_stoel']; $i <= $reservedSeatsForThisOrder['laatste_stoel']; $i++)
            {
                $reservedSeats[$i] = true;
            }
        }

        $firstSeat = 0;
        $lastSeat = 0;

        $adjacentFreeSeats = 0;
        for ($stoel = 1; $stoel <= $this->numReservedSeats; $stoel++)
        {
            if (isset($reservedSeats[$stoel]) && $reservedSeats[$stoel] == true)
            {
                $adjacentFreeSeats = 0;
            }
            else
            {
                $adjacentFreeSeats++;
            }

            if ($adjacentFreeSeats == $numTickets)
            {
                $foundEnoughSeats = true;
                $firstSeat = $stoel - $numTickets + 1;
                $lastSeat = $stoel;
                break;
            }
        }

        if ($foundEnoughSeats)
        {
            DBConnection::doQuery('INSERT INTO ticketsale_reservedseats(`orderId`, `rij`, `eerste_stoel`, `laatste_stoel`) VALUES(?, \'A\', ?, ?)', [$orderId, $firstSeat, $lastSeat]);
            return range($firstSeat, $lastSeat);
        }
        else
        {
            return null;
        }
    }
}