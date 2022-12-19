<?php

namespace Contracts;

interface BillerInterface {
    public function bill(int $accountId, int $amount): void;
}