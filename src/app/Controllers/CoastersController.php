<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;
use Config\Services;
use App\Repositories\CoasterRepository;

class CoastersController extends Controller
{
    use ResponseTrait;

    private $validation;
    private CoasterRepository $coasterRepository;

    public function __construct()
    {
        $this->validation = Services::validation();
        $this->coasterRepository = new CoasterRepository();
    }
    public function registerCoaster()
    {
        $this->validation->setRules([
            'liczba_personelu' => 'required|integer|greater_than[0]',
            'liczba_klientow' => 'required|integer|greater_than[0]',
            'dl_trasy' => 'required|integer|greater_than[0]',
            'godziny_od' => 'required|valid_date[H:i]',
            'godziny_do' => 'required|valid_date[H:i]|time_greater_than_field[godziny_od]'
        ]);

        if (!$this->validation->run($this->getData())) {
            return $this->failValidationErrors($this->validation->getErrors());
        }

        return $this->respondCreated([
            'id' => $this->coasterRepository->add($this->validation->getValidated()),
            'message' => 'Kolejka górska została zarejestrowana!'
        ]);
    }

    public function addCart(int $coasterId)
    {
        $this->validation->setRules([
            'ilosc_miejsc' => 'required|integer|greater_than[0]',
            'predkosc_wagonu' => 'required|numeric|greater_than[0]',
        ]);

        if (!$this->coasterRepository->coasterExists($coasterId)) {
            return $this->failNotFound("Kolejka górska o ID $coasterId nie istnieje.");
        }

        if (!$this->validation->run($this->getData())) {
            return $this->failValidationErrors($this->validation->getErrors());
        }

        $cartId = $this->coasterRepository->addCart($coasterId, $this->validation->getValidated());

        return $this->respondCreated(['id' => $cartId, 'message' => 'Wagon został dodany.']);
    }

    public function removeCart(int $coasterId, int $cartId)
    {
        if (!$this->coasterRepository->cartExists($coasterId, $cartId)) {
            return $this->failNotFound("Wagon o ID $cartId nie istnieje.");
        }

        $this->coasterRepository->removeCart($coasterId, $cartId);

        return $this->respondDeleted(['message' => 'Wagon został usunięty.']);
    }

    public function editCoaster(int $coasterId)
    {
        $this->validation->setRules([
            'liczba_personelu' => 'required|integer|greater_than[0]',
            'liczba_klientow' => 'required|integer|greater_than[0]',
            'godziny_od' => 'required|valid_date[H:i]',
            'godziny_do' => 'required|valid_date[H:i]|time_greater_than_field[godziny_od]'
        ]);

        $coaster = $this->coasterRepository->getCoaster($coasterId);

        if (empty($coaster)) {
            return $this->failNotFound("Kolejka górska o ID $coasterId nie istnieje.");
        }

        if (!$this->validation->run($this->getData())) {
            return $this->failValidationErrors($this->validation->getErrors());
        }

        $this->coasterRepository->updateCoaster(
            $coasterId,
            array_merge($coaster, $this->validation->getValidated()),
        );

        return $this->respondUpdated(['message' => "Kolejka $coasterId została zaktualizowana."]);
    }

    private function getData(): array
    {
        return array_merge($this->request->getJSON(true), $this->request->getPost());
    }
}