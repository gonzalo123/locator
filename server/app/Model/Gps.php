<?php

namespace App\Model;

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

class Gps
{
    private $database;

    private const FIREBASE_CONF = __DIR__ . '/../../conf/firebase.json';

    public function __construct()
    {
        $serviceAccount = ServiceAccount::fromJsonFile(self::FIREBASE_CONF);
        $firebase       = (new Factory)
            ->withServiceAccount($serviceAccount)
            ->create();

        $this->database = $firebase->getDatabase();
    }

    public function getLast()
    {
        $value = $this->database->getReference('gps/poi')
            ->orderByKey()
            ->limitToLast(1)
            ->getValue();

        $out                 = array_values($value)[0];
        $out['formatedDate'] = \DateTimeImmutable::createFromFormat('YmdHis', $out['date'])->format('d/m/Y H:i:s');

        return $out;
    }

    public function persistsData(array $data)
    {
        return $this->database
            ->getReference('gps/poi')
            ->push($data);
    }
}
