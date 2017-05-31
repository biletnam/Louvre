<?php
/**
 * Created by PhpStorm.
 * User: Emilien
 * Date: 17/05/2017
 * Time: 11:17
 */

namespace EL\BookingBundle\Managers;


use Doctrine\ORM\EntityManager;
use EL\BookingBundle\Entity\Ticket;
use EL\BookingBundle\Services\MuseumPolicy;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;


class TicketManager
{
    private $session;
    private $doctrine;
    private $request;
    private $policy;
    private $tools;

    public function __construct(
        Session       $session,
        EntityManager $doctrine,
        RequestStack  $request,
        MuseumPolicy  $policy,
        Tools         $tools


    )
    {
        $this->session  = $session;
        $this->doctrine = $doctrine;
        $this->request  = $request;
        $this->policy   = $policy;
        $this->tools    = $tools;
    }

    /**
     * @param $name
     * @param $surname
     * @param $dob
     * @param $discount
     * @param $time_access
     * @return mixed
     */
    public function createOrder($name,$surname,$dob,$discount,$time_access)
    {
        //fetch date and order_token into session
        $date = $this->session->get('user_date');
        $order_token = $this->session->get('temp_order_token');
        //initialise requested classes
        $ticket = new Ticket();
        //->price  : age + discount + museum time access => ticket price
        $age   = $this->tools->getAge($dob);
        $price = $this->tools->getPriceRange($age,$discount);
        $ticket_price = $this->tools->getTicketPrice($time_access,$price);
        $ticket->setDate($date)->getDate();
        $ticket->setName($name)->getName();
        $ticket->setSurname($surname)->getSurname();
        $ticket->setDob($dob)->getDob();
        $ticket->setDiscount($discount)->getDiscount();
        $ticket->setToken($name,$surname)->getToken();
        $ticket->setTimeAccess($time_access)->getTimeAccess($display = true);
        $ticket->setPrice($ticket_price)->getPrice();
        $ticket->setPriceType($dob);
        $ticket->setTimeAccessType($time_access)->getTimeAccessType();
        $ticket->setOrderToken($order_token);
        return $ticket;
    }

    public function isSessionSet()
    {
        if(!$this->session->has('order'))
        {
            $this->session->set('order', array());
        }

        return $this->session->get('order');
    }

    public function addToOrder($ticket)
    {
        $order = $this->isSessionSet();
        $order[]=array($ticket);
        $this->buildOrder($order);
        return $this->session->set('order',$order);
    }

    /**
     * @param $order
     * @return array
     */
    public function buildOrder($order)
    {
        $number_of_tickets = count($order);
        $total = 0;

        foreach ($order as $key)
        {
            foreach ($key as $ticket)
            {
                $total += $ticket->getPrice();
            }
        }
        $order = array('total'             => $total,
                       'number_of_tickets' => $number_of_tickets
        );

        $this->session->set('total',$order['total']);
        $this->session->set('tickets',$order['number_of_tickets']);

        return $order;
    }

    /**
     * @param $billing
     * @return mixed
     */
    public function getTickets($billing)
    {
        //fetch order, order_token, and date into session
        $order_token = $this->session->get('temp_order_token');
        $order       = $this->session->get('order');
        $date        = $this->session->get('user_date');
        //get date from session and turn it into a "datetime format"
        $date_time = $this->tools->formatDate($date);
        foreach ($order as $key)
        {
            foreach ($key as $ticket)
            {
                $ticket->setDate(\DateTime::createFromFormat('m-d-Y H:i:s', $date_time));
                $ticket->getName();
                $ticket->getSurname();
                $ticket->getDiscount();
                $ticket->getPriceType();
                $ticket->setOrderToken($order_token);
                $ticket->getTimeAccess();
                $ticket->getPrice();
                $ticket->getDob();
                $ticket->setBilling($billing);
            }
        }
        return $ticket;
    }

    /**
     * @param $query
     * @param $session_name
     * @return mixed
     */
    public function deleteTicketFromOrderInProgress($query,$session_name)
    {
       $id = $this->request->getCurrentRequest()->query->get($query);
       $order = $this->session->get($session_name);
       unset($order[$id]);
       $this->session->set($session_name,$order);
       $updated_session = $this->session->get($session_name);
       $this->buildOrder($updated_session);
       return $updated_session;
    }

    /**
     * @param $query
     * @param $session_name
     * @return mixed
     */
    public function getTicketToModify($query,$session_name)
    {
        $id = $this->request->getCurrentRequest()->query->get($query);
        $order = $this->session->get($session_name);
        $ticket_to_modify = $order[$id];
        return $ticket_to_modify;
    }

    /**
     * @param $query
     * @param $session_name
     * @param $name
     * @param $surname
     * @param $dob
     * @param $discount
     * @param $time_access
     * @return mixed
     */
    public function modifyTicket($query,$session_name,$name,$surname,$dob,$discount,$time_access)
    {
        //fetch date and order_token into session
        $date = $this->session->get('user_date');
        $order_token = $this->session->get('temp_order_token');
        //initialise requested classes
        $ticket = new Ticket();
        //->price type : age + discount + museum time access => ticket price
        $age   = $this->tools->getAge($dob);
        $price = $this->tools->getPriceRange($age,$discount);
        $ticket_price = $this->tools->getTicketPrice($time_access,$price);
        $ticket->setDate($date)->getDate();
        $ticket->setName($name)->getName();
        $ticket->setSurname($surname)->getSurname();
        $ticket->setDob($dob)->getDob();
        $ticket->setDiscount($discount)->getDiscount();
        $ticket->setToken($name,$surname)->getToken();
        $ticket->setTimeAccess($time_access)->getTimeAccess($display = true);
        $ticket->setPrice($ticket_price)->getPrice();
        $ticket->setPriceType($dob);
        $ticket->setTimeAccessType($time_access)->getTimeAccessType();
        $ticket->setOrderToken($order_token);

        $id = $this->request->getCurrentRequest()->query->get($query);
        $order = $this->session->get($session_name);
        $order[$id][]= $ticket;
        array_shift($order[$id]);
        $this->session->set($session_name,$order);
        $updated_session = $this->session->get($session_name);
        $this->buildOrder($updated_session);
        return $updated_session;
    }
}