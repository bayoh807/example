<?php

namespace App;

use App\Models\Order;
use DB;
use Exception;

use Contracts\BillerInterface;

class OrderProcessor  {

    private $biller;


    public function process(BillerInterface $biller,Order $order) : Order
    {
        $this->setBiller($biller);
        if($this->hasRecentOrder($order)) {
            throw new Exception('Duplicate order likely.');
        }

        return DB::transaction(function () use ($order) {
            // 確定建立好新訂單，再做金流請款
            if($newOrder = $this->toCreateOrder($order)) {
                $this->toBill($newOrder);
                return $newOrder;
            }
        });
    }
    private function setBiller(BillerInterface $biller) : self
    {
        $this->biller = $biller;
        return $this;
    }

    protected function hasRecentOrder(Order $order) : bool 
    {
        return $this->getRecentOrderCount($order)  > 0 ;
    }
    
    protected function getRecentOrderCount(Order $order) : int
    {
        $timestamp = Carbon::now()->subMinutes(5);

        return Order::where('account', $order->account->id)
            ->where('created_at', '>=', $timestamp)
            ->count();
    }

    protected function toCreateOrder(Order $order) : Order 
    {
        return Order::create([
            'account' => optional($order->account)->id,
            'amount' => $order->amount
        ]);
    }

    private function toBill(Order $order) : void  
    {
        $this->biller->bill($order->account->id, $order->amount);
    }
}