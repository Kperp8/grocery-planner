<?php

declare(strict_types=1);

namespace App\Controller;

final class DefaultController extends AppController
{
    public function index(): void
    {
        $this->render('home', [
            'pageTitle' => 'Moja aplikacja',
        ]);
    }

    public function planner(): void
    {
        $this->render('planner', ['pageTitle' => 'Planer']);
    }

    public function recipes(): void
    {
        $this->render('recipes', ['pageTitle' => 'Przepisy']);
    }

    public function pantry(): void
    {
        $this->render('pantry', ['pageTitle' => 'Spiżarnia']);
    }

    public function notFound(): void
    {
        $this->render('not-found', [
            'pageTitle' => 'Nie znaleziono strony',
        ]);
    }
}