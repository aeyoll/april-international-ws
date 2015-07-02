# april-international-ws

PHP wrapper around the AprilInternational insurance API

## Basic Usage

```
$ws = new AprilInternationalWS(array(
    'login'    => 'LOGIN',
    'password' => 'PASSWD'
));

$assurances = $ws->getAssurances();

foreach ($assurances as $assurance) {
    $formules = $ws->getFormules($assurance->id);

    foreach ($formules as $formule) {
        $devis = $ws
            ->addDepartureDate(new \Datetime('1 week'))
            ->addArrivalDate(new \Datetime('2 weeks'))
            ->addTraveler(true, 'lastname', 'firstname', 450, true)
            ->addTraveler(true, 'lastname', 'firstname', 450, false)
            ->getDevis($assurance->id, $formule->id, 450);

        $idContract = $ws->validateDevis($devis->id);
        $ws->getVoucherPdf($devis->id);
        die();
    }
}
```
