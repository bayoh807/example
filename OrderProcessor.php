<?php

namespace App;

use App\Models\Order;
use DB;

class OrderProcessor  {

    private $biller;

    public function setBiller(BillerInterface $biller) : self{
        $this->biller = $biller;
        return $this;
    }

    public function process(Order $order) : Order
    {
        if($this->hasRecentOrder($order)) {
            throw new Exception('Duplicate order likely.');
        }

        return DB::transaction(function () use ($order) {
            // 確定建立好新訂單在做金流請款
            if($newOrder = $this->toCreateOrder($order)) {
                $this->toBill($newOrder);
                return $newOrder;
            }
        });
     
    }

    protected function toCreateOrder(Order $order) {
        return Order::create([
            'account' => optional($order->account)->id,
            'amount' => $order->amount
        ]);
    }

    protected function toBill(Order $order) : void  {
        $this->biller->bill($order->account->id, $order->amount);
    }

    protected function getRecentOrderCount(Order $order,int $minutes = 5) : int
    {
        $timestamp = Carbon::now()->subMinutes($minutes);

        return Order::where('account', $order->account->id)
            ->where('created_at', '>=', $timestamp)
            ->count();
    }

    protected function hasRecentOrder(Order $order,int $minutes = 5) : bool {

        return $this->getRecentOrderCount($order)  > 0 ;
    }

}