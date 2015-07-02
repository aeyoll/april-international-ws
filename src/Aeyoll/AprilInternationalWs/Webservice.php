<?php

/*
 * This file is part of the AprilInternationalWs package.
 *
 * (c) Jean-Philippe Bidegain
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Aeyoll\AprilInternationalWs;

/**
 * AprilInternational Webservice wrapper
 *
 * @author  Jean-Philippe Bidegain <jp@bidega.in>
 */
class Webservice
{
    /**
     * Credential informations
     *
     * @var array
     */
    private $credentials = array();

    /**
     * WSDL link
     *
     * @var string
     */
    private $wsdl = 'http://demo.aprilvoyage.com/webservice/services/CoreWebService2?wsdl';

    /**
     * Trip travellers
     *
     * @var array
     */
    private $travellers = array();

    /**
     * Departure date
     *
     * @var DateTime
     */
    private $departureDate;

    /**
     * Arrival date
     *
     * @var DateTime
     */
    private $arrivalDate;

    /**
     * Error code messages
     *
     * @var array
     */
    private $errors = array(
        'User.unknown'                         => 'Utilisateur invalide (login, mot de passe incorrect)',
        'error.nombreDePax'                    => 'aucun assuré',
        'assurIndividuel.error.paxMinGarantie' => 'Pas assez d’assurés pour cette garantie',
        'assurIndividuel.error.paxMaxGarantie' => 'Trop d’assurés pour cette garantie',
        'infoVoyage.error.dureeMaxGarantie'    => 'Durée trop importante pour cette garantie',
        'infoVoyage.error.dureeMinGarantie'    => 'Durée trop courte pour cette garantie',
        'error.departTooLateForProduct'        => 'Départ trop tardif pour cette garantie',
        'error.departSupRetour'                => 'Départ > Retour',
        'error.departTooEarly'                 => 'Départ < date du jour',
        'pays inconnu'                         => 'Pays inconnu',
        'late subscription without comment'    => 'Souscription tardive effectué sans commentaire',
        'QuoteError'                           => 'Erreur de tarification (getDevis ou getTarifs) indépendantes des prix passées en paramètres',
        'OutOfLimitsError'                     => 'Erreur de tarification car les prix proposés sont hors tranches',
        'wrongUser'                            => 'L’utilisateur n’est pas autorisé à modifier ce contrat',
        'notSellingProduct'                    => 'Le client n’est pas autorisé à vendre ce produit',
        'ProblemGeneratingFile'                => 'Probleme de génération du fichier',
        'NoAssistanceForVouche'                => 'Pas d’assistance pour ce contrat'
    );
    /**
     * Constructor
     *
     * @param array $credentials [description]
     */
    public function __construct(array $credentials)
    {
        $this->credentials = $credentials;
    }

    /**
     * Call a SOAP function
     *
     * @param  string $service Name of the method to call
     * @param  array  $params  Parameters of method
     *
     * @return mixed
     */
    private function call($service, $params = array())
    {
        $client = new \SoapClient($this->wsdl);
        $params = $this->credentials + $params;

        try {
            $result = $client->__soapCall($service, $params);
        } catch (\Exception $e) {
            $message = $e->getMessage();

            if (isset($this->errors[$message])) {
                $result = $this->errors[$message];
            } else {
                $result = $message;
            }
        }

        return $result;
    }

    /**
     * Liste des assurances
     *
     * @return array
     */
    public function getAssurances()
    {
        return $this->call('getAssurances');
    }

    /**
     * Liste des formules
     *
     * @param  int $idAssurance
     *
     * @return array
     */
    public function getFormules($idAssurance)
    {
        return $this->call('getFormules', array('id' => $idAssurance));
    }

    /**
     * Add a traveller to the quote
     *
     * @param boolean   $insurred
     * @param string    $lastname
     * @param string    $firstname
     * @param float     $price
     * @param boolean   $subscriber
     *
     * @return self
     */
    public function addTraveler($insurred, $lastname, $firstname, $price, $subscriber)
    {
        $this->travellers[] = array(
            'assure'       => $insurred,
            'nom'          => $lastname,
            'prenom'       => $firstname,
            'prixVoyage'   => $price,
            'souscripteur' => $subscriber
        );

        return $this;
    }

    /**
     * Add a departure date
     *
     * @param \Datetime $date
     *
     * @return self
     */
    public function addDepartureDate(\Datetime $date)
    {
        $this->departureDate = $this->formatDate($date);

        return $this;
    }

    /**
     * Add an arrival date
     *
     * @param \Datetime $date
     *
     * @return self
     */
    public function addArrivalDate(\Datetime $date)
    {
        $this->arrivalDate = $this->formatDate($date);

        return $this;
    }

    /**
     * Format a date for April needs
     *
     * @param  \Datetime $date
     *
     * @return array
     */
    private function formatDate(\Datetime $date)
    {
        return array(
            'annee' => $date->format('Y'),
            'jour'  => $date->format('d'),
            'mois'  => $date->format('m')
        );
    }

    /**
     * Get a quotation
     *
     * @param  int  $idAssurance
     * @param  int  $idFormule
     *
     * @return array
     */
    public function getDevis($idAssurance, $idFormule)
    {
        $params = array(
            'idAssurance'  => $idAssurance,
            'idFormule'    => $idFormule,
            'extensions'   => array(),
            'dateDepart'   => $this->departureDate,
            'dateRetour'   => $this->arrivalDate,
            'idPays'       => 'FR',
            'idTypeVoyage' => 'OTH',
            'assures'      => $this->travellers,
            '', '', '', '', false, ''
        );

        return $this->call('getDevis', $params);
    }

    /**
     * Validate a devis
     *
     * @param  int $idDevis     No de devis
     *
     * @return string           No du contrat
     */
    public function validateDevis($idDevis)
    {
        return $this->call('validerDevis', array('idDevis' => $idDevis));
    }

    /**
     * Generate the contract
     *
     * @param  int $idDevis
     */
    public function getVoucherPdf($idDevis)
    {
        $data = $this->call('getVoucherPdf', array('noDevis' => $idDevis));

        $this->displayPdf($data);
    }

    /**
     * Displays a pdf
     *
     * @param  string $data
     */
    private function displayPdf($data = '')
    {
        if (!headers_sent()) {
            header('Content-type: application/pdf');
            header('Content-Disposition: inline; filename=filename.pdf');
        }

        echo $data;
    }
}
